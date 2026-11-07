# Changelog — CER Flow

Todas as alterações relevantes deste projeto são documentadas neste arquivo, seguindo o padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e [Versionamento Semântico (SemVer)](https://semver.org/lang/pt-BR/).

## [1.3.3] - 2026-11-06

### Adicionado
- Exibição automática do **Modal de Novidades da Versão** (`ModalNovidadesVersao`) mesmo para usuários já autenticados sempre que a versão do sistema (`VERSION`) for atualizada, comparando `session('ultima_versao_vista')` com `VersionService::versaoAtual()`.

## [1.3.2] - 2026-11-06

### Corrigido
- Adicionada a flag `--force` ao comando `git fetch --all --tags --force --prune` no `deploy.sh` para evitar que tags recriadas (`would clobber existing tag`) interrompam a execução do deploy sob `set -e`.

## [1.3.1] - 2026-11-06

### Alterado
- Simplificada toda a linguagem do **Modal de Novidades da Versão** e da **Central de Ajuda & Sobre** (`VersionService` e `ajuda-sobre.blade.php`) para um vocabulário acessível, amigável e voltado ao usuário comum (recepção, terapeutas, faturamento e coordenação), sem termos técnicos complexos.

## [1.3.0] - 2026-11-06

### Adicionado
- **Subitens de Menu em Configurações (`/configuracoes/{secao}`):** navegação direta na barra lateral expansível para `Pacientes (CER)`, `Salas & Espaços`, `Equipe & Perfis`, `Tabela SIGTAP`, `Trilha de Auditoria` e `Meu Perfil & PIN`.
- **CRUD Completo com Modais em Todos os Submódulos:** criação, busca em tempo real, edição completa em modal, alternância de status (Ativo/Inativo) e modal padronizado de confirmação de exclusão com verificação prévia de vínculos clínicos/BPA-I.
- **Sistema Global de Toasts (`<x-toast-notifications />`):** notificações flutuantes padronizadas de `sucesso`, `erro`, `alerta` e `info` com barra de progresso e suporte a eventos Livewire (`$this->dispatch('toast', ...)`).

## [1.2.0] - 2026-11-06

### Adicionado
- **Módulo de Configurações, Parâmetros SUS e Cadastros Base (`ConfiguracoesSistema` — `/configuracoes`):**
  - Aba de Salas e Espaços Físicos (`salas`).
  - Aba de Usuários, Perfis RBAC e Profissionais de Saúde com CNS, CBO, Conselho e PIN de assinatura (`users` + `profissionais`).
  - Aba de Cadastro de Pacientes SUS com validação algorítmica de CNS de 15 dígitos (`pacientes`).
  - Aba de Parametrização de Procedimentos SIGTAP x CBO (`procedimentos_sigtap`).
  - Aba de Trilha Imutável de Auditoria LGPD/CFM (`logs_auditoria`).
  - Aba Minha Conta & PIN para atualização individual de senha e PIN eletrônico.

## [1.1.4] - 2026-11-06

### Corrigido
- Blindado o script `deploy.sh` com `git config core.fileMode false`, `git reset --hard origin/${TARGET_REF}` e permissão `+x` nativa no índice do Git para evitar conflitos de merge por alterações de `chmod` no Linux.

## [1.1.3] - 2026-11-06

### Corrigido
- Corrigido erro `404 Not Found` no carregamento de `livewire.min.js` em produção no Nginx/CloudPanel por meio da publicação dos assets estáticos em `public/vendor/livewire` (`php artisan livewire:publish --assets`).
- Corrigido travamento do modal de novidades da versão pós-login utilizando `session()->pull('exibir_modal_versao')`, fechamento instantâneo com Alpine.js (`x-show` / `ESC` / clique externo) e remoção de inicialização duplicada do Alpine em `resources/js/app.js`.

## [1.1.2] - 2026-11-06

### Alterado
- Padronização PSR-12 nos componentes `ModalNovidadesVersao` e `AjudaSobre` e atualização das referências de release no guia `deploy.md`.

## [1.1.1] - 2026-11-06

### Adicionado
- Guia dedicado de implantação `deploy.md` parametrizado para a VPS Hostinger com CloudPanel (`cer.kltecnologia.com`, usuário `kltecnologia-cer`, PHP 8.5 e banco MySQL `cer`).
- Atualização institucional do `README.md` e definição de licença proprietária exclusiva da **KL Tecnologia** ([kltecnologia.com](https://kltecnologia.com)).

## [1.1.0] - 2026-11-06

### Adicionado
- **Modal de Novidades da Versão no Login (`ModalNovidadesVersao`):** exibido automaticamente sempre que um usuário realiza login no sistema, apresentando os destaques da release ativa (`v1.1.0`).
- **Módulo Ajuda & Sobre (`AjudaSobre` — `/ajuda`):** central de documentação operacional por perfil SUS e linha do tempo interativa mostrando a evolução de todas as versões (`v1.0.0`, `v1.0.1`, `v1.1.0`).
- **Serviço de Versionamento (`VersionService`):** gerenciamento centralizado da versão SemVer e histórico de releases.

## [1.0.1] - 2026-11-06

### Adicionado
- Script de deploy automatizado `deploy.sh` para VPS Hostinger / CloudPanel (suporte a deploy por branch ou tag SemVer, migrations, reset de cache do Spatie, build Vite e `artisan optimize`).
- Documentação operacional completa de produção e deploy no `README.md`.

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

