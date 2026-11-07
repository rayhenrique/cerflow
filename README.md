# CER Digital — Plataforma de Gestão Clínica, Grade Multiprofissional e Faturamento BPA-i (SIA/SUS)

> **Software Proprietário — Todos os direitos reservados à [KL Tecnologia](https://kltecnologia.com).**

O **CER Digital** é uma plataforma web corporativa desenvolvida pela **[KL Tecnologia](https://kltecnologia.com)** especificamente para a operação clínica, assistencial e de faturamento de **Centros Especializados em Reabilitação (CER - SUS)**.

A solução foi arquitetada para substituir processos fragmentados baseados em planilhas de cronograma, formulários avulsos de produção e prontuários em papel físico, unificando toda a jornada do usuário do SUS em um único fluxo contínuo: desde a alocação na grade semanal e recepção até a evolução multiprofissional assinada eletronicamente e a exportação magnética oficial para o **DATASUS (SIA/SUS)**.

---

## Sobre o Sistema e Módulos Operacionais

### 1. Grade Semanal Recorrente e Co-Atendimento
- **Espelhamento por Salas e Terapeutas:** Visualização matricial de segunda a sexta-feira, dividida em turnos (Manhã `08h–12h` e Tarde `13h–17h`) com slots de 30 minutos por espaço físico (Sala de Integração Sensorial, Sala de AVD, Consultórios Multiprofissionais, Ginásios de Fisioterapia Pediátrica/Adulto e Anexos).
- **Horário Cativo Recorrente:** Agendamento automatizado para múltiplas semanas consecutivas.
- **Atendimentos Compartilhados:** Alocação simultânea de dois ou mais especialistas (ex.: Fonoaudiologia + Terapia Ocupacional) no mesmo espaço e horário sem conflito de agenda, além de bloqueio visual para horários de trabalho interno (`EVOLUÇÃO`).

### 2. Recepção Inteligente e Controle de Absenteísmo
- **Check-in em 1 Clique:** Atualização instantânea do status diário (`AGENDADO`, `PRESENTE`, `ATENDIDO`, `FALTA_JUSTIFICADA`, `FALTA_INJUSTIFICADA`, `CANCELADO`).
- **Alerta Preventivo de Absenteísmo:** Identificação visual imediata de usuários que atingem **3 faltas consecutivas** ou **5 faltas alternadas** dentro do mês vigente, apoiando a busca ativa e a gestão de vagas.
- **Validação Cadastral SUS:** Algoritmo nativo (Módulo 11 DATASUS) para validação de Cartão Nacional de Saúde (CNS) de 15 dígitos (definitivos e provisórios), CPF, código IBGE do município (6 dígitos), CID-10 e modalidades de reabilitação (`FISICA`, `INTELECTUAL`, `AUDITIVA`, `VISUAL`).

### 3. Prontuário Eletrônico Multiprofissional (PEP Multi)
- **Linha do Tempo Integrada:** Leitura cronológica unificada das evoluções clínicas entre todas as especialidades (Fisioterapia, Fonoaudiologia, Psicologia, Terapia Ocupacional, etc.).
- **Assinatura Eletrônica SHA-256 via PIN:** Confirmação mediante PIN individual do profissional de saúde, gerando carimbo de tempo e hash criptográfico SHA-256 de 64 caracteres.
- **Trava Legal de Imutabilidade (24 Horas):** Em conformidade com as normas éticas e legais de prontuário eletrônico, toda evolução torna-se estritamente imutável após 24 horas da assinatura. Retificações posteriores são registradas exclusivamente por meio de **Termos de Adendo** vinculados ao registro original.
- **Sigilo e LGPD:** Perfis administrativos e de recepção são bloqueados por Policies de acessar anotações clínicas sensíveis.

### 4. Produção Automatizada e Faturamento BPA-i (SIA/SUS)
- **Zero Retrabalho de Digitação:** Ao assinar a evolução clínica, o sistema gera automaticamente a linha de produção ambulatorial vinculando Paciente, Profissional, CBO, CID-10 e Procedimento SIGTAP de 10 dígitos.
- **Desmembramento Automático em Co-Atendimento:** Encontros compartilhados geram registros independentes de BPA-i para cada profissional participante, respeitando seu respectivo CBO e procedimento SIGTAP.
- **Pré-Auditoria em Memória:** Painel preventivo que valida cada registro antes da exportação (CNS válido, compatibilidade `CBO x SIGTAP`, faixa etária e sexo), retendo inconsistências para correção antes do envio ao Ministério da Saúde.
- **Exportação Magnética Posicional (`.txt`):** Geração em stream do arquivo oficial do **BPA Magnético** contendo o cabeçalho `01#BPA#` (130 colunas, soma de controle `[1111..2221]`) e as linhas de produção individualizada Tipo `03` (350 colunas exatas, paginadas em folhas de até 20 linhas por profissional).

### 5. Governança, Auditoria e Controle de Versões
- **Trilha de Auditoria (`logs_auditoria`):** Registro imutável de acessos ao prontuário (`ACESSO_PEP`), assinaturas clínicas (`ASSINATURA_EVOLUCAO`) e exportações de lotes (`EXPORTACAO_BPA`) com identificação de usuário e endereço IP.
- **Comunicado de Versão e Central de Ajuda:** Modal automático pós-login apresentando as novidades de cada release e módulo **Ajuda & Sobre** (`/ajuda`) com linha do tempo SemVer e manual operacional por perfil.

---

## Stack Tecnológica

- **Backend:** PHP 8.3+ / Laravel 13.x
- **Banco de Dados:** MySQL 8.0+ (`InnoDB`, `utf8mb4_unicode_ci`)
- **Frontend Reativo:** Laravel Livewire 4.x + Alpine.js 3.x
- **Estilização:** Tailwind CSS v4 + Vite
- **Controle de Acesso (RBAC):** Spatie Laravel-Permission (`coordenacao`, `recepcao`, `terapeuta`, `faturamento`)
- **Autenticação:** Laravel Fortify
- **Qualidade & Testes:** PHPUnit 12 + Laravel Pint (PSR-12 estrito)

---

## Implantação em Produção (VPS Hostinger + CloudPanel)

### 1. Provisionamento no CloudPanel
1. No painel do **CloudPanel**, acesse **+ Add Site** → **Create a PHP Site**.
2. Selecione o template **Laravel 12 / 13** e a versão **PHP 8.3** (ou superior).
3. Na aba **Databases**, crie o banco de dados MySQL 8.0+ (`cer_flow`) e as credenciais de acesso.

### 2. Instalação Inicial via SSH
Conecte-se via SSH com o usuário do site criado no CloudPanel:

```bash
cd ~/htdocs/<seu-dominio>
rm -rf ./* ./.??* 2>/dev/null || true

git clone https://github.com/rayhenrique/cerflow.git .
cp .env.example .env
nano .env
```

Configure as variáveis de ambiente de produção no arquivo `.env`:

```dotenv
APP_NAME="CER Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br
APP_TIMEZONE=America/Maceio
APP_LOCALE=pt_BR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cer_flow
DB_USERNAME=seu_usuario_mysql
DB_PASSWORD=sua_senha_mysql
```

Execute os comandos de instalação e otimização:

```bash
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
npm ci && npm run build
php artisan optimize
chmod +x deploy.sh
```

### 3. Atualizações Contínuas (`deploy.sh`)
Para atualizar o ambiente de produção pela branch `main` ou por uma tag de versão específica (`SemVer`):

```bash
# Atualizar para o último commit da branch main
./deploy.sh

# Ou atualizar para uma tag específica de release
./deploy.sh v1.1.0
```

---

## Propriedade Intelectual e Licença

**Copyright © KL Tecnologia ([kltecnologia.com](https://kltecnologia.com)). Todos os direitos reservados.**

Este sistema é um **software proprietário** de titularidade exclusiva da **KL Tecnologia**. Não é um projeto open-source. É expressamente proibida a cópia, redistribuição, engenharia reversa, sublicenciamento, modificação ou utilização comercial/institucional sem autorização prévia e formal da **KL Tecnologia**.
