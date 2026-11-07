#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# CER Flow — Script de Deploy Automatizado (Hostinger VPS / CloudPanel)
# Uso:
#   ./deploy.sh              -> Atualiza pela branch main
#   ./deploy.sh v1.1.4       -> Faz deploy de uma tag específica (Release)
# ==============================================================================

TARGET_REF="${1:-main}"

echo "🚀 Iniciando deploy do CER Flow (Ref: ${TARGET_REF})..."

# Garante que se o script falhar no meio, a aplicação volte do modo de manutenção
trap 'php artisan up || true' ERR

# 1. Ativa modo de manutenção
php artisan down --retry=15 || true

# 2. Ignora diferenças de permissão (chmod) no Git e sincroniza com o repositório remoto
git config core.fileMode false
git fetch --all --tags --prune

if [[ "${TARGET_REF}" == v* ]]; then
    echo "📌 Fazendo checkout forçado da tag ${TARGET_REF}..."
    git checkout -f "tags/${TARGET_REF}"
else
    echo "📌 Sincronizando branch ${TARGET_REF} com origin/${TARGET_REF}..."
    git checkout -f "${TARGET_REF}"
    git reset --hard "origin/${TARGET_REF}"
fi

chmod +x deploy.sh

# 3. Instala dependências PHP de produção
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# 4. Executa migrations pendentes, publica assets do Livewire e limpa cache de permissões Spatie
php artisan migrate --force
php artisan livewire:publish --assets
php artisan permission:cache-reset

# 5. Compila assets Frontend (Tailwind CSS v4 + Alpine.js 3 + Vite)
if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
fi

# 6. Otimiza caches do Laravel 13 (config, routes, views, events)
php artisan optimize:clear
php artisan optimize
php artisan view:cache

# 7. Ajusta permissões de escrita do storage e cache
chmod -R ug+rwx storage bootstrap/cache

# 8. Desativa modo de manutenção
php artisan up

echo "✅ Deploy concluído com sucesso! Versão ativa: $(cat VERSION 2>/dev/null || echo "${TARGET_REF}")"
