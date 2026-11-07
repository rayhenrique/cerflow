# CER Flow — Gestão de Reabilitação, PEP Multiprofissional e BPA-i (SIA/SUS)

Sistema monolítico reativo desenvolvido em **Laravel 13.x**, **Livewire 4.x**, **Alpine.js 3.x** e **Tailwind CSS v4**, projetado para Centros Especializados em Reabilitação (CER - SUS). Unifica a grade semanal recorrente por salas e terapeutas, recepção/check-in com alerta de absenteísmo, Prontuário Eletrônico Multiprofissional (PEP) com assinatura eletrônica SHA-256 (trava legal de 24h) e exportação magnética posicional do **BPA-i (Boletim de Produção Ambulatorial Individualizado - DATASUS)**.

---

## Stack Tecnológica

- **Backend:** PHP 8.3+ / Laravel 13.x
- **Banco de Dados:** MySQL 8.0+ (`InnoDB`, `utf8mb4_unicode_ci`)
- **Frontend Reativo:** Laravel Livewire 4.x + Alpine.js 3.x
- **Estilização:** Tailwind CSS v4 + Vite
- **RBAC:** Spatie Laravel-Permission (`coordenacao`, `recepcao`, `terapeuta`, `faturamento`)
- **Autenticação:** Laravel Fortify

---

## Instalação em Produção (VPS Hostinger + CloudPanel)

### 1. Criar o Site no CloudPanel
1. No painel do **CloudPanel**, clique em **+ Add Site** → **Create a PHP Site**.
2. Selecione a aplicação **Laravel 12 / 13** e **PHP 8.3** (ou superior).
3. Em **Databases**, crie o banco de dados MySQL 8.0+ (`cer_flow`) e o respectivo usuário.

### 2. Primeiro Deploy via SSH (Usuário do Site)
Acesse via SSH com o **Site User** do CloudPanel:

```bash
cd ~/htdocs/<seu-dominio>
rm -rf ./* ./.??* 2>/dev/null || true

git clone https://github.com/rayhenrique/cerflow.git .
cp .env.example .env
nano .env
```

Ajuste as variáveis de produção no `.env`:

```dotenv
APP_NAME="CER Flow"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.gov.br
APP_TIMEZONE=America/Maceio
APP_LOCALE=pt_BR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cer_flow
DB_USERNAME=seu_usuario_mysql
DB_PASSWORD=sua_senha_mysql
```

Finalize a instalação inicial:

```bash
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
npm ci && npm run build
php artisan optimize
chmod +x deploy.sh
```

> **Credencial Inicial (Coordenação Geral):**
> - **E-mail:** `coordenacao@cerflow.gov.br`
> - **Senha:** `password` *(altere imediatamente após o primeiro login)*

---

## Atualizações Contínuas e Controle de Versão

Para atualizar o servidor pela branch `main` ou por uma tag de versão específica (`SemVer`):

```bash
# Atualizar para a última versão da branch main
./deploy.sh

# Ou atualizar para uma tag de release específica
./deploy.sh v1.0.1
```

---

## Suíte de Testes e Qualidade de Código

```bash
# Executar testes unitários, de feature e homologação estrutural BPA-i DATASUS
php artisan test

# Verificar padronização estrita PSR-12
./vendor/bin/pint --test
```
