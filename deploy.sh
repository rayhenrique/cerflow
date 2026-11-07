#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# CER Flow — Script de Deploy Automatizado (Hostinger VPS / CloudPanel)
# Uso:
#   ./deploy.sh              -> Atualiza pela branch main
#   ./deploy.sh v1.0.1       -> Faz deploy de uma tag específica (Release)
# ==============================================================================

TARGET_REF="${1:-main}"

echo "🚀 Iniciando deploy do CER Flow (Ref: ${TARGET_REF})..."

# 1. Ativa modo de manutenção
php artisan down --retry=15 || true

# 2. Atualiza código-fonte via Git
git fetch --all --tags --prune

if [[ "${TARGET_REF}" == v* ]]; then
    echo "📌 Fazendo checkout da tag ${TARGET_REF}..."
    git checkout -f "tags/${TARGET_REF}"
else
    echo "📌 Atualizando branch ${TARGET_REF}..."
    git checkout "${TARGET_REF}"
    git pull origin "${TARGET_REF}"
fi

# 3. Instala dependências PHP de produção
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# 4. Executa migrations pendentes e limpa cache de permissões Spatie
php artisan migrate --force
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

