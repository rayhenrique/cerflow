# CER Digital — Plataforma de Gestão Clínica, Grade Multiprofissional e Faturamento BPA-i (SIA/SUS)

> **Software Proprietário — Todos os direitos reservados à [KL Tecnologia](https://kltecnologia.com).**

O **CER Digital** é uma plataforma web corporativa desenvolvida pela **[KL Tecnologia](https://kltecnologia.com)** projetada sob medida para a operação clínica, assistencial, administrativa e de faturamento de **Centros Especializados em Reabilitação (CER II, CER III e CER IV - SUS)**.

A plataforma substitui integralmente processos fragmentados baseados em planilhas eletrônicas de cronograma, formulários avulsos de produção e prontuários em papel físico. Ela unifica a jornada do cidadão em um fluxo contínuo e auditável: acolhimento e agendamento na grade semanal, recepção e check-in diário, chamada de pacientes por voz no painel de TV, evolução clínica multidisciplinar assinada eletronicamente (PEP Multi) com trava legal de 24 horas, indicadores gerenciais em tempo real e exportação magnética oficial para o **DATASUS (SIA/SUS)**.

---

## Sumário

- [Visão Geral dos Módulos](#visão-geral-dos-módulos)
- [Stack Tecnológica](#stack-tecnológica)
- [Arquitetura de Segurança, Sigilo e LGPD](#arquitetura-de-segurança-sigilo-e-lgpd)
- [Instalação e Execução Local](#instalação-e-execução-local)
- [Testes Automatizados e Qualidade](#testes-automatizados-e-qualidade)
- [Implantação em Produção (VPS Hostinger + CloudPanel)](#implantação-em-produção-vps-hostinger--cloudpanel)
- [Rotinas de Manutenção, Backup e Cron](#rotinas-de-manutenção-backup-e-cron)
- [Propriedade Intelectual e Direitos](#propriedade-intelectual-e-direitos)

---

## Visão Geral dos Módulos

### 1. Landing Page Pública & Tela de Entrada Elegante
- **Página Institucional Pública (`/`):** Apresentação das linhas de reabilitação (Física, Intelectual, Auditiva e Visual), fluxo de cuidado do SUS e chamada direta para login corporativo.
- **Tela de Autenticação Segura (`/login`):** Design limpo em tons institucionais, alternância para visualização ou ocultação de senha, proteção contra tentativas excessivas e acesso à recuperação de credenciais.

### 2. Painel Inicial Operacional (Dashboard em Tempo Real — `/dashboard`)
- **Indicadores Rápidos do Dia:** Contagem de agendamentos diários, pacientes acolhidos na recepção, atendimentos clínicos concluídos e faltas.
- **Gráficos e Distribuição:** Visão do volume assistencial por modalidade de reabilitação e acompanhamento da produção ambulatorial do mês vigente.
- **Atalhos Rápidos:** Acesso direto à Agenda, Recepção, Prontuário, Faturamento, Relatórios e Configurações.

### 3. Agenda Operacional Unificada (`/agenda`)
- **Visões Flexíveis:** Alternância fluida entre os modos **Dia**, **Grade Semanal** (Segunda a Sexta, turnos Manhã `08h–12h` e Tarde `13h–17h`, em blocos de 30 minutos) e **Mapa Mensal**.
- **Horário Cativo Recorrente:** Alocação automatizada de pacientes em tratamento contínuo para múltiplas semanas consecutivas.
- **Atendimentos Compartilhados (Co-atendimento):** Dois ou mais profissionais no mesmo espaço e horário (ex.: Fonoaudiologia + Terapia Ocupacional na Sala Sensorial) sem conflito de agenda e com desmembramento automático na produção SUS.
- **Validação Cadastral Imediata:** Ao selecionar o paciente, o sistema verifica a presença e conformidade do Cartão Nacional de Saúde (CNS) e da Data de Nascimento. Caso o CNS esteja ausente ou seja matematicamente inválido, o sistema alerta de forma clara e oferece atalho direto para atualização cadastral sem perda do formulário.
- **Trabalho Interno ("EVOLUÇÃO"):** Bloqueio de horários para discussão de casos clínicos e preenchimento de relatórios técnicos.

### 4. Recepção Inteligente, Check-in & Controle de Absenteísmo
- **Check-in em 1 Clique:** Atualização em tempo real do status diário (`AGENDADO`, `PRESENTE`, `ATENDIDO`, `FALTA_JUSTIFICADA`, `FALTA_INJUSTIFICADA`, `CANCELADO`), com registro automático do horário de chegada (`chegada_em`).
- **Alerta Preventivo de Absenteísmo:** Destaque visual imediato para pacientes com **3 faltas consecutivas** ou **5 faltas alternadas** dentro do mês, subsidiando a atuação do Serviço Social e a busca ativa.
- **Busca Rápida de Pacientes:** Filtro instantâneo por Nome, CPF (com ou sem máscara) ou CNS de 15 dígitos sem latência em bases volumosas.

### 5. Sistema de Chamada de Pacientes por Painel / TV (`/painel-chamadas`)
- **Chamada Audiovisual na Sala de Espera:** Emissão de aviso sonoro (campainha harmônica) e sintetizador de voz (Web Speech API) anunciando o nome do paciente e o consultório/sala de atendimento.
- **Disparo Multi-origem:** Chamadas realizadas a partir da Recepção (ao registrar a chegada) ou diretamente do Consultório pelo terapeuta.
- **Pareamento Seguro de TVs:** Conexão de Smart TVs e telas da recepção mediante código numérico de 6 dígitos gerado na tela e autorizado no módulo de configurações.

### 6. Prontuário Eletrônico Multiprofissional (PEP Multi — `/prontuario/{paciente}`)
- **Linha do Tempo Multidisciplinar Unificada:** Visualização cronológica integrada de todas as anotações clínicas de Fisioterapia, Fonoaudiologia, Terapia Ocupacional, Psicologia, Serviço Social e demais áreas.
- **Assinatura Eletrônica com PIN:** Autenticação individual por PIN numérico do terapeuta, gerando carimbo de tempo inviolável e assinatura criptográfica com hash SHA-256 de 64 caracteres.
- **Trava Legal de 24 Horas:** Em conformidade com as normas dos conselhos de classe da saúde (CFM, COFFITO, CFP, CFFa, CRESS) e resoluções do prontuário eletrônico, toda evolução é travada após 24 horas da assinatura.
- **Termos de Adendo Retificadores:** Modificações posteriores são registradas exclusivamente como adendos complementares vinculados à anotação original, preservando o histórico legal.
- **Impressão Otimizada:** Exportação e impressão formatada em folhas A4 limpas com cabeçalho institucional, dados do paciente e carimbos de validação.

### 7. Faturamento Automatizado e Lote Magnético BPA-i (`/faturamento`)
- **Zero Retrabalho de Digitação:** Ao assinar a evolução clínica, o sistema gera automaticamente o registro de produção ambulatorial SUS associando Paciente, Profissional, CBO, CID-10 e Procedimento SIGTAP de 10 dígitos.
- **Desmembramento de Co-atendimento:** Sessões conjuntas geram linhas independentes na tabela de faturamento para cada profissional com seu respectivo CBO e procedimento SIGTAP.
- **Painel de Pré-Auditoria em Memória:** Validação antecipada do lote mensal antes do fechamento: verificação de CNS do paciente e do profissional, regras de compatibilidade `CBO x SIGTAP`, limites de idade mínima/máxima, sexo e CID-10 permitido.
- **Exportação Magnética Oficial Datasus (`.txt`):** Geração do arquivo texto estruturado para importação no programa **BPA Magnético (SIA/SUS)**, composto pelo cabeçalho oficial `01#BPA#` (130 colunas, checksum `[1111..2221]`) e linhas de produção individualizada Tipo `03` (350 colunas, paginadas em folhas de até 20 linhas por profissional).

### 8. Relatórios e Indicadores Gerenciais (`/relatorios`)
- **Caráter Exclusivamente Consultivo:** Ferramenta analítica que não altera nenhum status de agendamento, evolução ou produção.
- **Filtros Globais Dinâmicos:** Seleção de período (Hoje, Últimos 7 dias, Mês Atual, Mês Anterior, Ano Atual), Profissional, Especialidade/CBO, Sala e Modalidade de Reabilitação. Para faturamento, filtro por Competência (AAAAMM).
- **6 Áreas Temáticas Especializadas:**
  1. **Visão Geral:** Indicadores executivos (Total de atendimentos, presença, absenteísmo, novos pacientes, produção SUS), gráfico diário de atendimentos e distribuição por modalidade.
  2. **Atendimentos:** Distribuição detalhada por status, ranking de motivos de faltas/cancelamentos e horários de pico (manhã vs tarde).
  3. **Pacientes:** Volume total em acompanhamento, novos cadastros e lista prioritária de **Pacientes Críticos** (com 3+ faltas seguidas ou 5+ alternadas no mês) para busca ativa.
  4. **Profissionais:** Ranking de produtividade, atendimentos realizados, faltas registradas e taxa de ocupação da agenda.
  5. **Salas e Ocupação:** Taxa de ocupação dos espaços terapêuticos e análise de turnos para remanejamento de horários ociosos.
  6. **Produção SUS / BPA-i:** Total de procedimentos realizados, estimativa de faturamento ambulatorial SIGTAP, procedimentos mais frequentes e inconsistências pendentes.
- **Exportação & Impressão:** Download de qualquer relatório filtrado em formato CSV universal (compatível com Excel e Google Planilhas) e impressão limpa formatada para folhas A4 e PDF institucional.

### 9. Cadastros, Parâmetros e Integração e-SUS APS PEC (`/configuracoes`)
- **Dados do Estabelecimento:** Configuração de Razão Social, Nome Fantasia, CNES oficial, Município IBGE, logotipo institucional e contato do gestor.
- **Gestão de Espaços e Terapeutas:** Cadastro de salas (sensorial, AVD, consultórios, ginásios) e de profissionais com seus respectivos conselhos e CBOs SUS.
- **Gestão de Pacientes com Alta Performance:** Visualização e pesquisa em bases com dezenas de milhares de usuários com paginação configurável (15, 25, 50 ou 100 por página).
- **Integração Nativa com e-SUS APS PEC:** Conexão direta via rede interna/VPN com o banco de dados PostgreSQL do e-SUS PEC do município, permitindo a importação de cadastros da Atenção Primária, vinculação com UBS de referência e Agente Comunitário de Saúde (ACS), tanto pela interface web quanto por comando CLI agendado (`php artisan esus:sincronizar-pec`).

### 10. Central de Ajuda & Histórico de Versões (`/ajuda`)
- **Comunicação de Atualizações:** Modal automático pós-login informando as novidades da release mais recente em linguagem simples e acessível, com persistência individual por usuário (`ultima_versao_novidades_vista`).
- **Manual do Usuário Integrado:** Consulta dos capítulos operacionais diretamente na aplicação e botão para download do arquivo completo em formato Markdown (`Manual-do-Usuario-CER-Digital-v1.21.2.md`).

---

## Stack Tecnológica

| Componente | Tecnologia | Versão Mínima |
| :--- | :--- | :--- |
| **Linguagem Backend** | PHP | `^8.3` (suporte a 8.4 e 8.5) |
| **Framework Web** | Laravel | `^13.0` |
| **Camada Reativa** | Laravel Livewire + Alpine.js | Livewire `^4.0`, Alpine `^3.x` |
| **Estilização e Design** | Tailwind CSS + Vite | Tailwind CSS `^4.0`, Vite `^6.0` |
| **Banco de Dados Principal** | MySQL | `8.0+` (`InnoDB`, `utf8mb4_unicode_ci`) |
| **Banco de Dados Integrado** | PostgreSQL (e-SUS APS PEC) | `10.0+` (somente leitura) |
| **Autenticação & RBAC** | Laravel Fortify + Spatie Permission | Perfis: coordenação, recepção, terapeuta, faturamento |
| **Qualidade & Testes** | PHPUnit + Laravel Pint | PHPUnit `^12.0`, Pint (PSR-12 estrito) |

---

## Arquitetura de Segurança, Sigilo e LGPD

1. **Controle de Acesso Baseado em Papéis (RBAC):**
   - `recepcao`: Acesso à recepção, check-in, agenda e cadastro demográfico. **Acesso estritamente bloqueado a anotações clínicas e prontuários.**
   - `terapeuta`: Acesso à agenda pessoal, histórico multiprofissional do paciente, registro e assinatura de evolução clínica.
   - `faturamento`: Acesso ao painel de pré-auditoria e geração dos lotes magnéticos BPA-i.
   - `coordenacao`: Gestão de parâmetros, profissionais, salas, relatórios gerenciais e trilha de auditoria.
2. **Trilha de Auditoria Imutável (`logs_auditoria`):**
   - Registra todos os eventos sensíveis: consulta a prontuário (`ACESSO_PEP`), assinatura clínica com hash (`ASSINATURA_EVOLUCAO`) e download de lote magnético (`EXPORTACAO_BPA`), armazenando ID do usuário, data/hora e endereço IP.
3. **Validação Rigorosa de Dados SUS:**
   - Validação matemática de 15 dígitos do Cartão Nacional de Saúde (CNS) conforme algoritmo Módulo 11 do Ministério da Saúde.
   - Validação de CPF, códigos de procedimento SIGTAP de 10 dígitos e municípios IBGE de 6 dígitos.

---

## Instalação e Execução Local

### Pré-requisitos
- PHP 8.3 ou superior com extensões: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `xml`, `zip`.
- Composer 2.7+.
- Node.js 20+ e npm.
- Servidor MySQL 8.0+.

### Passo a Passo

1. **Clonar o Repositório:**
   ```bash
   git clone https://github.com/rayhenrique/cerflow.git
   cd cerflow
   ```

2. **Instalar Dependências PHP e Node:**
   ```bash
   composer install
   npm install
   ```

3. **Configurar as Variáveis de Ambiente:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Edite o arquivo `.env` para apontar para seu banco de dados MySQL:
   ```dotenv
   APP_NAME="CER Digital"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   APP_TIMEZONE=America/Maceio
   APP_LOCALE=pt_BR

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cer_flow
   DB_USERNAME=root
   DB_PASSWORD=sua_senha
   ```

4. **Executar Migrações e Dados Iniciais (Seeders):**
   ```bash
   php artisan migrate --seed
   ```

5. **Compilar os Arquivos de Frontend:**
   ```bash
   npm run build
   # ou para desenvolvimento com live-reload:
   npm run dev
   ```

6. **Iniciar o Servidor Local:**
   ```bash
   php artisan serve
   ```
   Acesse a aplicação em `http://localhost:8000`.

---

## Testes Automatizados e Qualidade

O CER Digital conta com uma suíte abrangente de testes automatizados cobrindo agendamentos, imutabilidade do PEP, co-atendimento, exportação BPA-i, controle de absenteísmo e relatórios:

```bash
# Executar a suíte completa de testes (PHPUnit)
php artisan test

# Executar testes com filtro específico
php artisan test --filter=RelatoriosIndicadoresTest
php artisan test --filter=ControleVersaoAjudaTest

# Analisar e corrigir formatação de código (PSR-12)
vendor/bin/pint --test
vendor/bin/pint
```

---

## Implantação em Produção (VPS Hostinger + CloudPanel)

### 1. Provisionamento no CloudPanel
1. Acesse o **CloudPanel** da VPS Hostinger.
2. Em **Sites**, clique em **+ Add Site** → **Create a PHP Site**.
3. Selecione o template **Laravel 12 / 13**, defina o domínio oficial (ex.: `cer.municipio.gov.br`) e a versão **PHP 8.3** (ou superior).
4. Na aba **Databases**, crie o banco de dados MySQL 8.0+ (`cer_digital_prod`) e o usuário seguro com permissões completas.

### 2. Instalação Inicial via SSH
Conecte-se à VPS via SSH com o usuário do site criado:

```bash
cd ~/htdocs/<seu-dominio>
rm -rf ./* ./.??* 2>/dev/null || true

git clone https://github.com/rayhenrique/cerflow.git .
cp .env.example .env
nano .env
```

Configure as variáveis essenciais para produção no `.env`:
```dotenv
APP_NAME="CER Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cer.municipio.gov.br
APP_TIMEZONE=America/Maceio
APP_LOCALE=pt_BR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cer_digital_prod
DB_USERNAME=usuario_cloudpanel
DB_PASSWORD=senha_forte_mysql

SESSION_SECURE_COOKIE=true
```

Execute os comandos de inicialização e otimização:
```bash
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate --force
php artisan migrate --seed --force
npm ci && npm run build
php artisan storage:link
php artisan optimize
chmod +x deploy.sh
```

### 3. Atualização Automatizada em Produção (`deploy.sh`)
O script `deploy.sh` automatiza o ciclo completo de atualização em produção:

```bash
# Atualizar para o commit mais recente da branch main:
./deploy.sh

# Ou atualizar para uma versão específica (SemVer):
./deploy.sh v1.21.2
```

---

## Rotinas de Manutenção, Backup e Cron

### 1. Agendamento de Tarefas (Cron do CloudPanel)
No painel do CloudPanel, acesse **Cron Jobs** e configure a execução do agendador do Laravel a cada minuto:

```bash
* * * * * php /home/<usuario-site>/htdocs/<seu-dominio>/artisan schedule:run >> /dev/null 2>&1
```

### 2. Sincronização e-SUS APS PEC Agendada
Para manter a base de pacientes sincronizada diariamente com o PostgreSQL da Atenção Primária:

```bash
# Exemplo de execução manual:
php artisan esus:sincronizar-pec

# Exemplo de comando agendado no crontab (madrugada):
0 2 * * * php /home/<usuario-site>/htdocs/<seu-dominio>/artisan esus:sincronizar-pec --tipo=incremental >> /home/<usuario-site>/htdocs/<seu-dominio>/storage/logs/esus-sync.log 2>&1
```

### 3. Rotinas de Backup
- **Banco de Dados MySQL:** Configurar dump diário do MySQL via CloudPanel ou script automatizado:
  ```bash
  mysqldump -u usuario_cloudpanel -p'senha_forte' cer_digital_prod | gzip > ~/backups/cer_$(date +\%Y\%m\%d_\%H\%M).sql.gz
  ```
- **Arquivos e Storage:** Realizar snapshot periódico do diretório `storage/app/public` (onde estão armazenados logotipos e documentos institucionais).

---

## Propriedade Intelectual e Direitos

**Copyright © KL Tecnologia ([kltecnologia.com](https://kltecnologia.com)). Todos os direitos reservados.**

Este sistema é um **software proprietário** de titularidade exclusiva da **KL Tecnologia**. É expressamente proibida a cópia, reprodução, redistribuição, engenharia reversa, sublicenciamento, modificação ou comercialização sem autorização formal e expressa por escrito da **KL Tecnologia**.
