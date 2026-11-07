#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# CER Digital — Script de Deploy Automatizado (Hostinger VPS / CloudPanel)
# Uso:
#   ./deploy.sh              -> Atualiza pela branch main
#   ./deploy.sh v1.7.0       -> Faz deploy de uma tag específica (Release)
# ==============================================================================

TARGET_REF="${1:-main}"

echo "🚀 Iniciando deploy do CER Digital (Ref: ${TARGET_REF})..."

# ------------------------------------------------------------------------------
# Funções Auxiliares de Segurança e Integridade
# ------------------------------------------------------------------------------

# Calcula o checksum SHA-256 do arquivo informado
calcular_checksum_env() {
    local target="${1:-.env}"
    if [ ! -f "${target}" ]; then
        echo ""
        return
    fi
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "${target}" | awk '{print $1}'
    elif command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "${target}" | awk '{print $1}'
    elif command -v openssl >/dev/null 2>&1; then
        openssl dgst -sha256 "${target}" | awk '{print $NF}'
    else
        cksum "${target}" | awk '{print $1}'
    fi
}

# Valida se a variável APP_KEY existe e não está vazia no .env (sem exibir seu valor)
validar_app_key() {
    local key_val
    key_val="$(grep -E '^APP_KEY=' .env 2>/dev/null | head -n 1 | cut -d '=' -f2- | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's/^["'"'"']//' -e 's/["'"'"']$//' || true)"
    if [ -z "${key_val}" ]; then
        return 1
    fi
    return 0
}

# ------------------------------------------------------------------------------
# 1. Hardening e Validações de Segurança Pré-Deploy
# ------------------------------------------------------------------------------
echo "🔒 Verificando pré-requisitos de segurança do ambiente..."

# 1.1 Verifica existência do arquivo .env
if [ ! -f .env ]; then
    echo "❌ ERRO CRÍTICO: Arquivo de configuração .env não encontrado!"
    echo "O deploy foi interrompido imediatamente para evitar indisponibilidade."
    echo "Crie e configure o arquivo .env de produção antes de executar o deploy."
    exit 1
fi

# 1.2 Interrompe imediatamente se o .env estiver sendo rastreado pelo Git
if git ls-files --error-unmatch .env >/dev/null 2>&1; then
    echo "❌ ERRO CRÍTICO DE SEGURANÇA: O arquivo .env está sendo rastreado pelo Git!"
    echo "O deploy foi interrompido para evitar vazamento ou sobrescrita acidental de credenciais."
    echo "Remova-o do índice imediatamente com 'git rm --cached .env' antes de continuar."
    exit 1
fi

# 1.3 Valida se APP_KEY está configurada e não vazia (sem exibir seu valor)
if ! validar_app_key; then
    echo "❌ ERRO CRÍTICO: Variável APP_KEY ausente ou vazia no arquivo .env!"
    echo "O deploy foi interrompido para proteger a integridade criptográfica da aplicação."
    echo "Configure uma chave válida no .env antes de executar o deploy."
    exit 1
fi

# 1.4 Registra checksum SHA-256 do .env original e cria backup seguro com permissão restrita (600)
ENV_GUARD_FILE=".env.deploy_guard.bak"
ENV_ORIGINAL_CHECKSUM="$(calcular_checksum_env .env)"
cp -p .env "${ENV_GUARD_FILE}"
chmod 600 "${ENV_GUARD_FILE}" 2>/dev/null || true

# 1.5 Função e trap de recuperação em caso de falha durante a execução
restaurar_env_em_caso_de_erro() {
    if [ -f "${ENV_GUARD_FILE}" ]; then
        local cur_chk
        cur_chk="$(calcular_checksum_env .env)"
        if [ ! -f .env ] || [ "${cur_chk}" != "${ENV_ORIGINAL_CHECKSUM}" ]; then
            echo "⚠️ RECUPERAÇÃO: Restaurando .env original a partir do backup de segurança..."
            cp -fp "${ENV_GUARD_FILE}" .env
            chmod 600 .env 2>/dev/null || true
        fi
    fi
    rm -f "${ENV_GUARD_FILE}" 2>/dev/null || true
    php artisan up || true
}

trap 'restaurar_env_em_caso_de_erro' ERR

echo "✅ Pré-requisitos de segurança validados (.env e APP_KEY ativos, checksum SHA-256 registrado)."

# ------------------------------------------------------------------------------
# 2. Ativa modo de manutenção
# ------------------------------------------------------------------------------
php artisan down --retry=15 || true

# ------------------------------------------------------------------------------
# 3. Sincronização Segura com o Repositório Remoto
# ------------------------------------------------------------------------------
# Ignora diferenças de permissão (chmod) no Git e sincroniza tags forçadas
git config core.fileMode false
git fetch --all --tags --force --prune

if [[ "${TARGET_REF}" == v* ]]; then
    echo "📌 Fazendo checkout forçado da tag ${TARGET_REF}..."
    git checkout -f "tags/${TARGET_REF}"
else
    echo "📌 Sincronizando branch ${TARGET_REF} com origin/${TARGET_REF}..."
    git checkout -f "${TARGET_REF}"
    git reset --hard "origin/${TARGET_REF}"
fi

# 3.1 Verifica se o .env foi removido ou alterado (comparando checksum SHA-256)
CURRENT_ENV_CHECKSUM="$(calcular_checksum_env .env)"
if [ ! -f .env ] || [ "${CURRENT_ENV_CHECKSUM}" != "${ENV_ORIGINAL_CHECKSUM}" ]; then
    echo "⚠️ ALERTA: O arquivo .env foi removido ou alterado durante a sincronização Git!"
    echo "Restaurando cópia original do backup seguro..."
    cp -fp "${ENV_GUARD_FILE}" .env
    chmod 600 .env 2>/dev/null || true
    echo "✅ Arquivo .env restaurado com sucesso (integridade SHA-256 restabelecida)."
fi

# 3.2 Valida novamente a APP_KEY após a sincronização / restauração
if ! validar_app_key; then
    echo "❌ ERRO CRÍTICO: APP_KEY ausente ou vazia no .env após a sincronização Git!"
    echo "Tentando restaurar forçadamente a partir do backup de segurança..."
    cp -fp "${ENV_GUARD_FILE}" .env
    chmod 600 .env 2>/dev/null || true
    if ! validar_app_key; then
        echo "❌ ERRO FATAL: Falha irrecuperável na validação da APP_KEY!"
        exit 1
    fi
fi

chmod +x deploy.sh

# ------------------------------------------------------------------------------
# 4. Instala dependências PHP de produção
# ------------------------------------------------------------------------------
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# ------------------------------------------------------------------------------
# 5. Executa migrations pendentes, publica assets e limpa cache de permissões
# ------------------------------------------------------------------------------
php artisan migrate --force
php artisan livewire:publish --assets
php artisan permission:cache-reset

# ------------------------------------------------------------------------------
# 6. Compila assets Frontend (Tailwind CSS v4 + Alpine.js 3 + Vite)
# ------------------------------------------------------------------------------
if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
fi

# ------------------------------------------------------------------------------
# 7. Otimiza caches do Laravel 13 (config, routes, views, events)
# ------------------------------------------------------------------------------
php artisan optimize:clear
php artisan optimize
php artisan view:cache

# ------------------------------------------------------------------------------
# 8. Ajusta permissões de escrita do storage e cache
# ------------------------------------------------------------------------------
chmod -R ug+rwx storage bootstrap/cache

# ------------------------------------------------------------------------------
# 9. Desativa modo de manutenção
# ------------------------------------------------------------------------------
php artisan up

# ------------------------------------------------------------------------------
# 10. Conclusão segura: remove o backup do .env somente após o sucesso total
# ------------------------------------------------------------------------------
rm -f "${ENV_GUARD_FILE}" 2>/dev/null || true

echo "✅ Deploy concluído com sucesso! Versão ativa: $(cat VERSION 2>/dev/null || echo "${TARGET_REF}")"
