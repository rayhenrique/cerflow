# Changelog — CER Flow

Todas as alterações relevantes deste projeto são documentadas neste arquivo, seguindo o padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e [Versionamento Semântico (SemVer)](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-11-06

### Adicionado
- **Fase 1 (Setup & Banco de Dados):**
  - Estrutura Laravel 13.x + Livewire 4.x + Alpine.js 3.x + Tailwind CSS v4 + Spatie Laravel-Permission.
  - Modelagem relacional MySQL 8.0+ (`InnoDB`, `utf8mb4_unicode_ci`) na ordem estrita de integridade referencial (`salas`, `pacientes`, `procedimentos_sigtap`, `profissionais`, `agendamentos`, `agendamento_profissionais`, `evolucoes_clinicas`, `evolucao_adendos`, `producoes_bpai`, `logs_auditoria`).
  - Seeders fundamentais (`RoleAndPermissionSeeder`, `ProcedimentoSigtapSeeder`, `SalaSeeder`, `UserAdminSeeder`).
- **Fase 2 (Models, Relacionamentos e Scopes):**
  - Models Eloquent com `SoftDeletes`, casts imutáveis, relacionamentos N:N com pivô (`agendamento_profissionais`), trava de 24h (`EvolucaoClinica::estaBloqueada()`) e scopes de competência/status em `ProducaoBpai`.
- **Fase 3 (Autenticação, Middlewares e Policies):**
  - Autenticação Laravel Fortify com verificação de usuário ativo.
  - Middleware `AuditAccessLog` para trilha LGPD/CFM em `logs_auditoria`.
  - `EvolucaoPolicy` (autoria estrita e imutabilidade após 24h), `AgendamentoPolicy` e `PacientePolicy`.
- **Fase 4 (Serviços de Domínio e Validações SUS):**
  - Regra algorítmica `CnsValido` (Módulo 11 DATASUS de 15 dígitos) e Form Requests (`PacienteRequest`, `AgendamentoRequest`, `AssinarEvolucaoRequest`).
  - `AgendamentoService` (grade recorrente, anti-conflito com bypass para co-atendimento e alerta de absenteísmo).
  - `EvolucaoService` (assinatura PIN + hash SHA-256, desmembramento de produção compartilhada e termos de adendo).
  - `BpaiExportService` (pré-auditoria em memória e exportação posicional DATASUS Header `01#BPA#` + linhas Tipo `03`).
- **Fase 5 (Interface Reativa Livewire 4):**
  - Layout estilo Filament/Linear com controle de navegação por perfil (`coordenacao`, `recepcao`, `terapeuta`, `faturamento`).
  - Componentes `GradeSemanal`, `RecepcaoCheckin`, `ProntuarioPaciente` e `FaturamentoBpai`.
- **Fase 6 (Testes Automatizados e Homologação):**
  - Suíte completa PHPUnit (Unit, Feature e Homologação estrutural DATASUS BPA-i) e padronização PSR-12 via Laravel Pint.
