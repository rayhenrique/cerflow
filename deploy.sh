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

# 1.2 Valida se APP_KEY está configurada e não vazia (sem exibir seu valor)
APP_KEY_VALUE="$(grep -E '^APP_KEY=' .env 2>/dev/null | head -n 1 | cut -d '=' -f2- | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's/^["'"'"']//' -e 's/["'"'"']$//' || true)"

if [ -z "${APP_KEY_VALUE}" ]; then
    echo "❌ ERRO CRÍTICO: Variável APP_KEY ausente ou vazia no arquivo .env!"
    echo "O deploy foi interrompido para proteger a integridade criptográfica da aplicação."
    echo "Configure uma chave válida no .env antes de executar o deploy."
    exit 1
fi

unset APP_KEY_VALUE

# 1.3 Confirma que o .env permanece ignorado pelo Git
if git ls-files --error-unmatch .env >/dev/null 2>&1; then
    echo "⚠️ ALERTA DE SEGURANÇA: O arquivo .env está sendo rastreado pelo Git!"
    echo "Remova-o do índice imediatamente com 'git rm --cached .env' para evitar vazamento."
fi

echo "✅ Pré-requisitos de segurança validados (.env e APP_KEY ativos)."

# Cria salvaguarda temporária do .env contra remoção acidental durante operações Git
cp -p .env .env.deploy_guard.bak 2>/dev/null || true

# Garante recuperação da aplicação e da salvaguarda em caso de falha durante a execução
trap 'if [ ! -f .env ] && [ -f .env.deploy_guard.bak ]; then mv -f .env.deploy_guard.bak .env; fi; rm -f .env.deploy_guard.bak 2>/dev/null || true; php artisan up || true' ERR

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

# Confirma que operações Git não removeram ou corromperam o .env
if [ ! -f .env ]; then
    if [ -f .env.deploy_guard.bak ]; then
        echo "⚠️ AVISO: .env ausente após sincronização Git. Restaurando salvaguarda..."
        mv -f .env.deploy_guard.bak .env
    else
        echo "❌ ERRO CRÍTICO: .env desapareceu após sincronização Git!"
        exit 1
    fi
fi
rm -f .env.deploy_guard.bak 2>/dev/null || true

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

echo "✅ Deploy concluído com sucesso! Versão ativa: $(cat VERSION 2>/dev/null || echo "${TARGET_REF}")"
