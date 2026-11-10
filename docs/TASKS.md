# Checklist de Implementação Sequencial - CER Flow (Laravel 13 + Livewire 4)

## 📌 Fase 1: Setup do Projeto e Banco de Dados
- [x] 1.1 Criar projeto Laravel 13 (`composer create-project laravel/laravel cer-flow`)
- [x] 1.2 Configurar ambiente `.env` (MySQL 8.0+, timezone `America/Maceio`, locale `pt_BR`)
- [x] 1.3 Configurar Tailwind CSS v4.3 e Alpine.js 3.x
- [x] 1.4 Instalar pacotes essenciais: `spatie/laravel-permission`, `livewire/livewire` (^4.0)
- [x] 1.5 Criar Migrations na ordem estrita de integridade referencial:
  - [x] `01_create_salas_table`
  - [x] `02_create_pacientes_table`
  - [x] `03_create_procedimentos_sigtap_table`
  - [x] `04_create_profissionais_table`
  - [x] `05_create_agendamentos_table`
  - [x] `06_create_agendamento_profissionais_table`
  - [x] `07_create_evolucoes_clinicas_table`
  - [x] `08_create_evolucao_adendos_table`
  - [x] `09_create_producoes_bpai_table`
  - [x] `10_create_logs_auditoria_table`
- [x] 1.6 Criar e executar Seeders fundamentais:
  - [x] `RoleAndPermissionSeeder` (`coordenacao`, `recepcao`, `terapeuta`, `faturamento`)
  - [x] `ProcedimentoSigtapSeeder` (procedimentos de reabilitação com CBOs compatíveis)
  - [x] `SalaSeeder` (salas da grade: Sensorial, AVD, Consultórios 08/09, Pediatria, etc.)
  - [x] `UserAdminSeeder` (usuário inicial coordenador)

## 📌 Fase 2: Models, Relacionamentos e Scopes Eloquent
- [x] 2.1 Model `Paciente` com casts (`data_nascimento` => `date`, `modalidades_reabilitacao` => `array`) e softDeletes
- [x] 2.2 Model `Profissional` com relações `belongsTo(User::class)` e `belongsToMany(Agendamento::class)`
- [x] 2.3 Model `Agendamento` com relações com `Paciente`, `Sala`, `Profissionais` e `Evolucoes`
- [x] 2.4 Model `EvolucaoClinica` com cast de `assinado_em` => `datetime`, helper de trava de 24h e relação com `Adendos`
- [x] 2.5 Model `ProducaoBpai` com scopes de busca por competência e status de exportação

## 📌 Fase 3: Autenticação, Middlewares e Policies
- [x] 3.1 Configurar autenticação Laravel Breeze / Fortify
- [x] 3.2 Criar Middleware `AuditAccessLog` para registrar consultas ao prontuário e downloads de remessas
- [x] 3.3 Implementar `EvolucaoPolicy`:
  - [x] Bloquear edição se decorridas 24 horas da assinatura (`now()->diffInHours($evolucao->assinado_em) >= 24`)
  - [x] Permitir alteração apenas pelo profissional autor da evolução
- [x] 3.4 Implementar `AgendamentoPolicy` e `PacientePolicy` com base nas roles do Spatie

## 📌 Fase 4: Serviços de Domínio e Validações
- [x] 4.1 Criar Form Requests com validações estritas:
  - [x] `PacienteRequest` (algoritmo e máscara do CNS com 15 dígitos, CPF e IBGE de 6 dígitos)
  - [x] `AgendamentoRequest` (anti-conflito de sala e paciente, com bypass se marcado como compartilhado)
  - [x] `AssinarEvolucaoRequest` (confirmação obrigatória do PIN de assinatura)
- [x] 4.2 Criar `AgendamentoService` (criação de grade semanal recorrente e alocação de co-atendimentos)
- [x] 4.3 Criar `EvolucaoService` (cálculo de hash SHA-256 e geração de registro em `producoes_bpai`)
- [x] 4.4 Criar `BpaiExportService`:
  - [x] Validador pré-exportação de inconsistências em memória
  - [x] Formatador de layout posicional oficial DATASUS (Header `#BPA#` + linhas tipo `03`)
  - [x] Gerador de stream para download do arquivo `.txt`

## 📌 Fase 5: Interface Reativa (Livewire 4 + Tailwind v4.3)
- [x] 5.1 Criar Layout base moderno (estilo Filament/Linear) com navegação por perfil
- [x] 5.2 Desenvolver Componente `Livewire\GradeSemanal`:
  - [x] Visualização em colunas por Sala/Profissional e linhas por slots de 30 min (espelhando a planilha)
  - [x] Filtros por dia da semana (Seg a Sex) e turnos (Manhã / Tarde)
  - [x] Modal de agendamento rápido com seleção de múltiplos profissionais
- [x] 5.3 Desenvolver Componente `Livewire\RecepcaoCheckin`:
  - [x] Lista de agendamentos diários com botões de status rápido (Presente, Falta)
  - [x] Badge visual de alerta para pacientes com histórico de faltas recorrentes
- [x] 5.4 Desenvolver Componente `Livewire\ProntuarioPaciente`:
  - [x] Linha do tempo multiprofissional de atendimentos anteriores
  - [x] Formulário de evolução rápida com modal de PIN para assinatura
  - [x] Interface para inserção de termos de adendo em evoluções bloqueadas
- [x] 5.5 Desenvolver Componente `Livewire\FaturamentoBpai`:
  - [x] Seletor de competência (Mês/Ano)
  - [x] Painel de pré-auditoria destacando registros válidos x inconsistentes
  - [x] Botão de download em stream do arquivo magnético `.txt`

## 📌 Fase 6: Testes Automatizados e Homologação
- [x] 6.1 Teste unitário para validação de formato e comprimento do CNS (15 dígitos)
- [x] 6.2 Teste de feature para a trava de imutabilidade de 24h na `EvolucaoClinica`
- [x] 6.3 Teste de feature para desmembramento de produção em Atendimento Compartilhado
- [x] 6.4 Teste de integração do `BpaiExportService` conferindo o layout posicional gerado
- [x] 6.5 Homologação final com importação do arquivo no validador oficial do BPA do DATASUS

## 📌 Fase 7: Evolução Contínua, Usabilidade e Identidade CER Digital
- [x] 7.1 Módulo de Configurações, Parâmetros SUS e Cadastros Base com CRUD completo (`ConfiguracoesSistema`)
- [x] 7.2 Sistema Global de Notificações Toast (`<x-toast-notifications />`) e Linguagem Acessível (`VersionService`)
- [x] 7.3 Responsividade Mobile, Tablet e Desktop com Drawer off-canvas e tabelas roláveis
- [x] 7.4 Painel Inicial Operacional em tempo real (`DashboardInicial` — `/dashboard`) com métricas do CER e BPA-i
- [x] 7.5 Sidebar retrátil com alternância de largura (`lg:w-20` / `lg:w-64`) e ícones vetoriais em todos os itens
- [x] 7.6 Rebranding completo da identidade visual para **CER Digital** (login, cabeçalho, menus, documentação)
- [x] 7.7 Auditoria de Internacionalização em Português Brasileiro (PT-BR), criação de `lang/pt_BR` e padronização (Painel Inicial)
- [x] 7.8 Persistência individual do Modal de Novidades da Versão no banco (`ultima_versao_novidades_vista` na tabela `users`), independente por usuário e dispositivo

## 📌 Fase 8: Integração e-SUS APS PEC & Sistema de Chamadas por TV
- [x] 8.1 Sincronização direta com banco de dados PostgreSQL do e-SUS APS PEC (`ConfiguracoesEsusPecService` e comando `php artisan esus:sincronizar-pec`)
- [x] 8.2 Vinculação automática de UBS de referência e Agente Comunitário de Saúde (ACS) na ficha do paciente
- [x] 8.3 Painel de TV na Sala de Espera (`/painel-chamadas`) com emissão de áudio harmônico e síntese de voz (Web Speech API)
- [x] 8.4 Pareamento seguro de Smart TVs via código numérico de 6 dígitos (`dispositivos_tv` e `solicitacoes_pareamento_tv`)
- [x] 8.5 Disparo de chamadas a partir da Recepção e do consultório do Terapeuta

## 📌 Fase 9: Landing Page Institucional & Experiência Mobile-First
- [x] 9.1 Landing Page pública (`/`) autoral, moderna e acessível, apresentando as linhas de reabilitação e o fluxo de cuidado do SUS
- [x] 9.2 Tela de Login corporativa (`/login`) redesenhada com identidade visual acolhedora, botão de visualização/ocultação de senha e tratamento de erro
- [x] 9.3 Experiência Mobile-First em todos os módulos: barra de navegação inferior (Bottom Nav) e cartões verticais em substituição a tabelas largas
- [x] 9.4 Aprimoramento da estabilidade no carregamento e renderização do logotipo institucional em todas as resoluções

## 📌 Fase 10: Validações Cadastrais no Agendamento & Relatórios Gerenciais
- [x] 10.1 Alerta imediato e claro no modal de Novo Agendamento ao selecionar paciente sem Cartão SUS (CNS) ou com CNS inválido
- [x] 10.2 Atalho de atualização cadastral rápida sem perda dos campos preenchidos e sem códigos internos exibidos ao usuário
- [x] 10.3 Módulo completo de Relatórios e Indicadores Gerenciais (`/relatorios`) com 6 áreas temáticas (Visão Geral, Atendimentos, Pacientes, Profissionais, Salas/Ocupação e Produção SUS)
- [x] 10.4 Filtros globais compartilhados por período, atalhos dinâmicos, profissional, especialidade, sala e competência BPA-i
- [x] 10.5 Exportação universal para planilhas em formato CSV e impressão otimizada em folha A4 limpa via CSS de impressão
- [x] 10.6 Documentação integral e sincronizada com a versão v1.21.1 (`README.md`, `MANUAL-DO-USUARIO.md`, `DATABASE-SCHEMA.md`, `AjudaSobre`)
- [x] 10.7 Correção de segurança no campo de senha da tela de login (`type="password"` nativo, toggle via JavaScript leve e acessibilidade por teclado) (v1.21.2)

## 📌 Fase 11: Cadastros Dinâmicos de CBOs, Especialidades e Unidades Básicas de Saúde (UBS) (v1.22.0)
- [x] 11.1 Migrations e modelos para `cbos`, `especialidades`, tabela pivot `cbo_especialidade` e `unidades_basicas_saude`
- [x] 11.2 Foreign keys com `restrictOnDelete`: `cbo_id` e `especialidade_id` em `profissionais`, e `ubs_id` em `pacientes`, preservando colunas legadas
- [x] 11.3 Seeder e conciliação de dados `CboEspecialidadeUbsSeeder` com ocupações oficiais, especialidades clínicas e rede básica de saúde
- [x] 11.4 Cadastro inicial do CBO oficial `251545` (Neuropsicólogo) e Especialidade `Neuropsicologia` (Fonte: MTE)
- [x] 11.5 Proteção e blindagem das regras de faturamento ambulatorial BPA-i (rejeição automática de procedimentos SIGTAP incompatíveis com CBO 251545)
- [x] 11.6 Gestão dinâmica e CRUD completo em `ConfiguracoesSistema` para CBOs (código de 6 dígitos, verificação oficial MTE) e Especialidades (relação N:N, deduplicação inteligente)
- [x] 11.7 Gestão dinâmica e CRUD completo de UBSs com CNES de 7 dígitos, município IBGE, endereço, telefone e origem (`MANUAL` ou `ESUS`)
- [x] 11.8 Adaptação do `EsusPecIntegrationService` para vincular `ubs_id` sem duplicação de CNES e preservando vínculos manuais
- [x] 11.9 Bloqueio de integridade referencial: proibição de exclusão física de CBOs/Especialidades com profissionais e de UBSs com pacientes
- [x] 11.10 Integração com filtros dinâmicos de especialidades e UBS no módulo de Relatórios Gerenciais
- [x] 11.11 Suíte completa de testes automatizados com 100% de aprovação (`CboEspecialidadesUbsTest`)