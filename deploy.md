# Guia de Deploy em Produção — CER Flow (Hostinger VPS + CloudPanel)

> **Propriedade Intelectual:** [KL Tecnologia](https://kltecnologia.com)  
> **Repositório Oficial:** [https://github.com/rayhenrique/cerflow](https://github.com/rayhenrique/cerflow)

Este documento descreve o passo a passo exato para implantar e atualizar o **CER Flow** na VPS Hostinger utilizando o **CloudPanel**, parametrizado exatamente com os dados do seu ambiente:

| Parâmetro no CloudPanel | Valor Configurado |
| :--- | :--- |
| **Application Template** | `Laravel 13` |
| **Domain Name** | `cer.kltecnologia.com` |
| **IP da VPS** | `72.60.142.2` |
| **PHP Version** | `PHP 8.5` |
| **Site User (SSH)** | `kltecnologia-cer` |
| **Diretório da Aplicação** | `/home/kltecnologia-cer/htdocs/cer.kltecnologia.com` |
| **Root Directory (Nginx)** | `/home/kltecnologia-cer/htdocs/cer.kltecnologia.com/public` |
| **Database Name** | `cer` |
| **Database User Name** | `cer` |

---

## 1. Apontamento DNS e Certificado SSL (HTTPS)

1. Na zona DNS do domínio `kltecnologia.com`, garanta que existe uma entrada do tipo **A**:
   - **Tipo:** `A`
   - **Nome / Host:** `cer`
   - **Valor / Destino:** `72.60.142.2`
2. No CloudPanel, dentro do site `cer.kltecnologia.com`:
   - Acesse a aba **SSL/TLS** → clique em **Actions** → **New Let's Encrypt Certificate** → **Create and Install**.

---

## 2. Acesso via SSH na VPS Hostinger

Conecte-se à VPS pelo terminal. Você pode entrar diretamente com o **Site User** ou entrar como `root` e alternar para o usuário do site (`kltecnologia-cer`):

```bash
# Opção A: Login direto com o Site User
ssh kltecnologia-cer@72.60.142.2

# Opção B: Caso esteja logado como root na VPS, troque para o usuário do site (NUNCA rode composer/git como root)
su - kltecnologia-cer
```

---

## 3. Clonar o Repositório do GitHub

Ao criar o site no CloudPanel, ele gera uma pasta padrão em `/home/kltecnologia-cer/htdocs/cer.kltecnologia.com`. Limpe o diretório inicial e clone o repositório oficial:

```bash
cd /home/kltecnologia-cer/htdocs/cer.kltecnologia.com

# Remove os arquivos iniciais gerados pelo CloudPanel
rm -rf ./* ./.??* 2>/dev/null || true

# Clona o repositório oficial do CER Flow
git clone https://github.com/rayhenrique/cerflow.git .
```

> **Nota (se tornar o repositório privado no futuro):** Gere uma chave SSH (`ssh-keygen -t ed25519 -C "deploy@cer.kltecnologia.com"`), adicione o conteúdo de `~/.ssh/id_ed25519.pub` em **Settings → Deploy Keys** no repositório GitHub e use `git clone git@github.com:rayhenrique/cerflow.git .`.

---

## 4. Configurar o Arquivo `.env` de Produção

Copie o arquivo `.env.example` para `.env` e edite as credenciais do banco `cer` criado na aba **Databases** do CloudPanel:

```bash
cp .env.example .env
nano .env
```

Deixe o bloco principal do `.env` exatamente assim (substituindo `SUA_SENHA_DO_BANCO_CER` pela senha definida no CloudPanel):

```dotenv
APP_NAME="CER Flow"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://cer.kltecnologia.com
APP_TIMEZONE=America/Maceio

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cer
DB_USERNAME=cer
DB_PASSWORD="SUA_SENHA_DO_BANCO_CER"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
```

Salve no `nano` pressionando `CTRL + O`, `ENTER` e saia com `CTRL + X`.

---

## 5. Instalar Dependências, Rodar Migrations/Seeders e Compilar Assets

Execute a sequência abaixo no terminal (ainda como usuário `kltecnologia-cer` dentro de `/home/kltecnologia-cer/htdocs/cer.kltecnologia.com`):

```bash
# 1. Instala pacotes PHP de produção otimizados
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# 2. Gera a chave criptográfica da aplicação (APP_KEY)
php artisan key:generate --force

# 3. Cria todas as tabelas na ordem estrita e popula Roles, Procedimentos SIGTAP, Salas e Admin inicial
php artisan migrate --seed --force

# 4. Cria o link simbólico de storage público
php artisan storage:link

# 5. Instala pacotes Node e compila o Tailwind CSS v4 + Alpine.js 3 via Vite
npm ci --no-audit --no-fund
npm run build

# 6. Gera os caches de configuração, rotas e views de produção
php artisan optimize
php artisan view:cache

# 7. Ajusta permissões de escrita das pastas storage/ e bootstrap/cache/
chmod -R 775 storage bootstrap/cache
chmod +x deploy.sh
```

---

## 6. Primeiro Acesso ao Sistema

Acesse no navegador:

- **URL:** `https://cer.kltecnologia.com/login`
- **E-mail Inicial (Coordenação Geral):** `coordenacao@cerflow.gov.br`
- **Senha Inicial:** `password`

> Assim que fizer o primeiro login, o **Modal de Novidades da Versão (`v1.1.0`)** será exibido automaticamente. Recomenda-se alterar a senha do usuário inicial imediatamente após o primeiro acesso.

---

## 7. Como Atualizar o Sistema nas Próximas Versões (`deploy.sh`)

O repositório já conta com o script automatizado `./deploy.sh`. Sempre que você subir uma nova versão para o GitHub, basta conectar via SSH com o usuário `kltecnologia-cer` e rodar:

```bash
cd /home/kltecnologia-cer/htdocs/cer.kltecnologia.com

# Atualizar para o último commit da branch main:
./deploy.sh

# OU atualizar travado em uma tag de release específica (SemVer):
./deploy.sh v1.1.0
```

O script `./deploy.sh` executa automaticamente:
1. `php artisan down` (modo de manutenção rápido)
2. `git fetch` + `git pull` (ou checkout da tag informada)
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force` e `php artisan permission:cache-reset`
5. `npm ci && npm run build`
6. `php artisan optimize` e `php artisan view:cache`
7. `php artisan up`

---

## 8. Dicas Importantes para CloudPanel

1. **Varnish Cache:** Como o **CER Flow** utiliza **Livewire 4** e sessões autenticadas em tempo real (grade, check-in e prontuário), mantenha o **Varnish Cache desativado** na aba *Varnish Cache* do CloudPanel para evitar cache de páginas autenticadas ou tokens CSRF.
2. **Versão do PHP no CLI:** Caso o terminal SSH padrão chame outra versão do PHP, no CloudPanel você pode invocar explicitamente o PHP 8.5 com `/usr/bin/php8.5 artisan ...` ou garantir que o `PHP Version` na aba *Settings* esteja em `8.5`.
3. **Logs de Erro:** Se precisar inspecionar logs da aplicação ou do Nginx no CloudPanel:
   ```bash
   tail -f /home/kltecnologia-cer/htdocs/cer.kltecnologia.com/storage/logs/laravel.log
   ```
