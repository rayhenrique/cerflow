# Changelog — CER Digital

Todas as alterações relevantes deste projeto são documentadas neste arquivo, seguindo o padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e [Versionamento Semântico (SemVer)](https://semver.org/lang/pt-BR/).

## [1.8.0] - 2026-11-07

### Adicionado
- **Módulo de Identificação do Estabelecimento (Coordenação):**
  - Nova aba em Configurações para gestão dos dados institucionais da unidade de saúde.
  - Parametrização do Código IBGE padrão do município e do código CNES da unidade.
  - Upload, pré-visualização, substituição e remoção da Logomarca Oficial do estabelecimento (armazenada em disco público com suporte a PNG, JPG, JPEG e WebP de até 2MB).
  - Exibição dinâmica da logomarca na tela de login e nos menus de navegação (desktop e mobile), com fallback automático para a identidade textual "CER Digital" e o badge "CD".
  - Integração do CNES institucional no cabeçalho do sistema e no layout posicional oficial do faturamento BPA-i SIA/SUS.
  - Registro detalhado de auditoria LGPD para todas as alterações de dados institucionais e arquivos de imagem.

### Corrigido
- Correção de escopo de compilação Blade no layout principal (`components/layouts/app.blade.php`), eliminando erro 500 no carregamento da aplicação.
- Registro da rota `/configuracoes/estabelecimento` com preservação estrita do controle de acesso (RBAC restrito à Coordenação).
- Implementação de leitura pura e fallback em memória em `ConfiguracaoEstabelecimento::atual()`, evitando escritas desnecessárias durante a renderização e tratando com segurança a ausência da tabela antes das migrations.
- Adição de isolamento de banco em testes com `RefreshDatabase` no `ExampleTest`.

## [1.7.0] - 2026-11-07

### Adicionado
- **Rebranding para CER Digital:** transição de toda a identidade visual visível do sistema de "CER Flow" para "CER Digital" na tela de login, títulos de abas e componentes Livewire, menus de navegação, cabeçalhos, Central de Ajuda, `.env.example`, `config/app.php` e documentação técnica de implantação, preservando a KL Tecnologia como desenvolvedora e proprietária.
- **Auditoria Completa em Português Brasileiro (PT-BR):**
  - Tradução dos termos em inglês apresentados ao usuário, incluindo a substituição integral de "Dashboard" por "Painel Inicial" no histórico de versões, menus e views.
  - Criação dos arquivos oficiais de internacionalização em `lang/pt_BR` (`auth.php`, `pagination.php`, `passwords.php` e `validation.php`), garantindo mensagens amigáveis e precisas em formulários e autenticação.
  - Validação do atributo `lang="pt-BR"` em todos os documentos HTML da aplicação.
  - Preservação estrita das siglas e nomenclaturas oficiais do Ministério da Saúde e DATASUS: SUS, BPA-i, SIGTAP, CNS, CBO, CNES, LGPD, PEP e PIN.
- **Persistência Individual do Modal de Novidades da Versão:**
  - Migration reversível adicionando a coluna `ultima_versao_novidades_vista` (string nullable) na tabela `users`.
  - Componente `ModalNovidadesVersao` refatorado para comparar a versão atual com a coluna do usuário logado, exibindo a janela de novidades apenas uma vez por versão para cada colaborador.
  - Registro de visualização persistido no banco de dados exclusivamente ao fechar ou confirmar o modal (via botão de fechamento, botão "Entendi, continuar", tecla ESC, clique fora ou pelo link da Central de Ajuda via `irParaAjudaSobre` com persistência antes do redirecionamento).
  - Remoção completa da dependência de chaves de sessão voláteis (`exibir_modal_versao` e `ultima_versao_vista`), assegurando independência entre usuários e dispositivos.
  - Manutenção do acionamento manual das novidades através do botão de versão na barra superior e na Central de Ajuda.
- **Melhorias e Manutenção:**
  - Fallback de `VersionService::versaoAtual()` alinhado dinamicamente com `$this->releaseAtual()['versao']`, garantindo que não haja divergências mesmo na ausência do arquivo `VERSION`.
  - Sincronização do hash de integridade do `composer.lock` para evitar avisos em rotinas de deploy.

## [1.6.2] - 2026-11-07

### Corrigido
- Removida a referência à coluna inexistente `quantidade` na consulta de `producoes_bpai` dentro de `DashboardInicial::calcularMetricasBpai()`, corrigindo o erro `500 Server Error (SQLSTATE[42S22]: Unknown column 'quantidade')` ao acessar `/dashboard` em banco MySQL 8.0 de produção.

## [1.6.1] - 2026-11-07

### Corrigido
- Refatorado `DashboardInicial::render()` em métodos auxiliares tipados (`resolverPeriodoCompetencia`, `contarModalidadesPacientes`, `calcularMetricasMensais`, `calcularMetricasBpai`) e substituído `filter()` por `whereIn()`, eliminando avisos de inferência do PHP Language Server (`PHP0447` e `PHP6613`).

## [1.6.0] - 2026-11-07

### Adicionado
- **Toggle Retrátil na Sidebar (`sidebarCollapsed` com persistência em `localStorage`):** permite recolher (`lg:w-20`) ou expandir (`lg:w-64`) a barra de navegação no desktop com 1 clique pelo topo da sidebar ou pelo cabeçalho superior.
- **Ícones SVG Dedicados em Todos os Itens e Subitens do Menu:** ícones vetoriais exclusivos para `Dashboard Inicial`, `Grade Semanal`, `Recepção & Check-in`, `Prontuário (PEP Multi)`, `Faturamento BPA-i`, `Configurações` (`Pacientes SUS`, `Salas & Espaços`, `Equipe & Perfis`, `Tabela SIGTAP`, `Auditoria LGPD`, `Minha Conta & PIN`), `Ajuda & Sobre` e `Sair`.

## [1.5.0] - 2026-11-07

### Adicionado
- **Dashboard Inicial Operacional (`DashboardInicial` — `/dashboard`):**
  - KPIs em tempo real de Pacientes Ativos, Agenda de Hoje (presentes, aguardando e faltas), Evoluções Assinadas no Mês e Produção BPA-i da Competência.
  - Distribuição visual de pacientes pelas 4 modalidades de reabilitação do CER (`FISICA`, `INTELECTUAL`, `AUDITIVA`, `VISUAL`).
  - Painel de Assiduidade & Absenteísmo no mês, ranking de ocupação por Sala Terapêutica e tabela rápida da agenda do dia com atalhos operacionais.

## [1.4.0] - 2026-11-06

### Adicionado
- **Responsividade Total (Mobile, Tablet e Desktop):**
  - Menu lateral deslizante (*Off-canvas Drawer*) com botão hamburger (`☰`) no cabeçalho para telas menores que `1024px` (`lg:hidden`).
  - Adaptação responsiva de todas as telas (`GradeSemanal`, `RecepcaoCheckin`, `ProntuarioPaciente`, `FaturamentoBpai`, `ConfiguracoesSistema`, `AjudaSobre`, `Login` e `ToastNotifications`) com rolagem horizontal segura nas tabelas (`overflow-x-auto`), grids adaptativos (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`) e modais ajustados para celulares.

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

