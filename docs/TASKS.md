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