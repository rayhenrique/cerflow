# CER DIGITAL — GUIA PERMANENTE DE COMANDOS ARTISAN (CLI)

> **Documento Vivo e Normativo**  
> Projeto: `rayhenrique/cerflow`  
> Versão do Sistema: v1.30.0 | Stack: Laravel 13, Livewire 4, MySQL, PostgreSQL (e-SUS APS PEC)  
> Padrão Operacional Obrigatório em Servidores Linux / VPS: **`php8.5 artisan <comando>`**  
> Última Atualização: 11/11/2026

---

## SUMÁRIO

1. [Padrão Obrigatório de Execução (PHP 8.5)](#1-padrão-obrigatório-de-execução-php-85)
2. [Regra Permanente de Manutenção](#2-regra-permanente-de-manutenção)
3. [Inventário Geral de Comandos](#3-inventário-geral-de-comandos)
4. [Categoria 1 — Informações e Diagnóstico](#4-categoria-1--informações-e-diagnóstico)
5. [Categoria 2 — Banco de Dados e Migrations](#5-categoria-2--banco-de-dados-e-migrations)
6. [Categoria 3 — Cache e Otimização](#6-categoria-3--cache-e-otimização)
7. [Categoria 4 — Rotas, Eventos e Configurações](#7-categoria-4--rotas-eventos-e-configurações)
8. [Categoria 5 — Filas e Agendador de Tarefas (Queues & Scheduler)](#8-categoria-5--filas-e-agendador-de-tarefas-queues--scheduler)
9. [Categoria 6 — Testes Automatizados](#9-categoria-6--testes-automatizados)
10. [Categoria 7 — Desenvolvimento e Geração de Código (Make & Scaffolding)](#10-categoria-7--desenvolvimento-e-geração-de-código-make--scaffolding)
11. [Categoria 8 — Integração e-SUS APS PEC (Personalizados)](#11-categoria-8--integração-e-sus-aps-pec-personalizados)
12. [Categoria 9 — Profissões, Especialidades e Catálogo CBO (Personalizados)](#12-categoria-9--profissões-especialidades-e-catálogo-cbo-personalizados)
13. [Categoria 10 — Comandos de Pacotes (Livewire, Spatie Permission, Fortify)](#13-categoria-10--comandos-de-pacotes-livewire-spatie-permission-fortify)
14. [Categoria 11 — Comandos Perigosos e Restritos (Destrutivos)](#14-categoria-11--comandos-perigosos-e-restritos-destrutivos)
15. [Categoria 12 — Guia de Solução de Problemas e Procedimentos de Operação](#15-categoria-12--guia-de-solução-de-problemas-e-procedimentos-de-operação)

---

## 1. PADRÃO OBRIGATÓRIO DE EXECUÇÃO (PHP 8.5)

### 1.1 Por que utilizar explicitamente `php8.5 artisan` no Linux / VPS?

Em servidores de hospedagem e VPS (CloudPanel, Debian, Ubuntu), é padrão a coexistência de múltiplas versões do PHP. O comando genérico de sistema `php` frequentemente aponta para o PHP padrão do sistema operacional (por exemplo, PHP 8.1 ou 8.2), o qual:
1. **Não possui a extensão `pdo_pgsql` instalada/ativa**, necessária para a comunicação com o banco PostgreSQL do e-SUS APS PEC.
2. Não possui as configurações otimizadas de memória (`memory_limit = 512M` ou superior), OPcache e extensões ativas para o Laravel 13.
3. Causa incompatibilidades de tipagem estrita do PHP 8.3/8.4/8.5.

Portanto, em todos os scripts, rotinas de deploy, crontabs e comandos manuais no terminal Linux/VPS, **é obrigatório utilizar**:
```bash
php8.5 artisan <comando>
```
Caminho canônico do binário: `/usr/bin/php8.5 artisan <comando>`

### 1.2 Desenvolvimento em Ambiente Windows

No ambiente Windows (desenvolvimento local), deve-se garantir que o executável configurado nas Variáveis de Ambiente (`PATH`) ou chamado diretamente seja o **PHP 8.5**:
```powershell
php -v # Deve retornar PHP 8.5.x
php artisan <comando>
```
Caso haja múltiplas versões no Windows, utilize o caminho absoluto do executável PHP 8.5:
```powershell
C:\php85\php.exe artisan <comando>
```

---

## 2. REGRA PERMANENTE DE MANUTENÇÃO

> [!IMPORTANT]
> **DIRETRIZ PERMANENTE DO PROJETO CER DIGITAL:**
> Sempre que um comando Artisan for **criado**, **alterado**, **renomeado** ou **removido**, o desenvolvedor ou agente de IA **DEVE atualizar este arquivo (`comandos-cli.md`) na mesma tarefa e no mesmo commit.**

A documentação de cada novo comando deve conter obrigatoriamente:
- Assinatura correta no padrão `php8.5 artisan`.
- Finalidade e descrição objetiva.
- Todos os argumentos e opções suportados (com destaque para `--dry-run`, `--lote`, etc.).
- Exemplo prático de execução.
- Ambientes permitidos (`Local`, `Homologação`, `Produção / VPS`).
- Necessidade de autorização prévia da coordenação/administração.
- Impacto sobre banco de dados, memória/cache ou arquivos.
- Nível de risco operacional.
- Arquivo PHP responsável pela implementação.

---

## 3. INVENTÁRIO GERAL DE COMANDOS

A instalação do CER Digital possui **149 comandos Artisan** registrados no Laravel 13:
- **6 Comandos Personalizados do CER Digital**:
  - `esus:diagnostico` (Diagnóstico de estrutura e rede do e-SUS APS PEC)
  - `esus:sincronizar` (Sincronização incremental/completa e de vínculos com e-SUS PEC)
  - `esus:conciliar-pacientes` (Conciliação e vinculação de UBS para pacientes locais)
  - `cer:importar-cbos` (Importação em lote do catálogo oficial de CBOs do MTE)
  - `cer:recuperar-cbos-legados` (Recuperação e conciliação idempotente de CBOs legados)
  - `inspire` (Frase motivacional no console — `routes/console.php`)
- **15 Comandos de Pacotes Integrados**:
  - Livewire 4 (`livewire:*`)
  - Spatie Laravel-Permission 8 (`permission:*`)
  - Laravel Fortify (`fortify:install`)
  - Laravel Pail (`pail`)
- **128 Comandos Nativos do Framework Laravel 13**:
  - Otimização, banco, migrations, rotas, filas, agendamento, make, events e canais.

---

## 4. CATEGORIA 1 — INFORMAÇÕES E DIAGNÓSTICO

Comandos somente-leitura utilizados para obter dados do ambiente, monitoramento e integridade.

| Comando | Finalidade | Risco | Ambiente |
|---|---|---|---|
| `php8.5 artisan about` | Exibe resumo completo da aplicação (PHP, Laravel, banco, cache, drivers). | Nenhum | Todos |
| `php8.5 artisan env` | Informa o ambiente atual configurado (`APP_ENV`). | Nenhum | Todos |
| `php8.5 artisan help <cmd>` | Exibe ajuda detalhada, argumentos e opções de um comando. | Nenhum | Todos |
| `php8.5 artisan list` | Lista todos os comandos Artisan registrados. | Nenhum | Todos |
| `php8.5 artisan docs` | Abre links da documentação oficial do Laravel no navegador. | Nenhum | Local |
| `php8.5 artisan pail` | Monitora logs em tempo real via terminal (Laravel Pail). | Nenhum | Local / Homolog |
| `php8.5 artisan dev:list` | Lista processos de desenvolvimento registrados. | Nenhum | Local |
| `php8.5 artisan esus:diagnostico` | Diagnóstico de conexão, tabelas e integridade do PostgreSQL e-SUS PEC. | Nenhum | Todos |

### Detalhamento dos Comandos:

#### `php8.5 artisan about`
- **Finalidade:** Apresenta versão do Laravel (13.x), PHP (8.5), timezone, status de cache de configuração/rotas e drivers ativos.
- **Opções:** `--only=SECTION` (filtra seções: `environment`, `cache`, `drivers`), `--json` (saída em JSON).
- **Exemplo:** `php8.5 artisan about --only=environment`
- **Impacto:** Leitura pura.

#### `php8.5 artisan pail`
- **Finalidade:** Exibe stream de logs da aplicação em tempo real com filtros por nível (`--level`), exceção (`--filter`) ou usuário (`--user`).
- **Exemplo:** `php8.5 artisan pail --level=error`
- **Impacto:** Leitura de arquivos de log.

---

## 5. CATEGORIA 2 — BANCO DE DADOS E MIGRATIONS

Comandos para visualização, manutenção estrutural, migrações e seeders do banco de dados MySQL do CER Digital.

| Comando | Finalidade | Risco | Ambiente |
|---|---|---|---|
| `php8.5 artisan db:show` | Exibe dados do banco MySQL (versão, tamanho, contagem de tabelas). | Nenhum | Todos |
| `php8.5 artisan db:table <tabela>` | Inspeciona colunas, tipos e índices de uma tabela específica. | Nenhum | Todos |
| `php8.5 artisan db:monitor` | Monitora conexões ativas no banco de dados. | Baixo | Produção / VPS |
| `php8.5 artisan db` | Abre uma sessão CLI interativa no cliente do MySQL. | Médio | Local / Homolog |
| `php8.5 artisan db:seed` | Executa classes seeders para alimentar dados no banco. | Médio | Local / Homolog |
| `php8.5 artisan migrate` | Executa migrations pendentes no banco de dados. | Médio | Todos |
| `php8.5 artisan migrate:status` | Lista todas as migrations e o status de execução (Sim/Não). | Nenhum | Todos |
| `php8.5 artisan migrate:rollback` | Reverte o último lote de migrations executadas. | Alto | Local / Homolog |
| `php8.5 artisan model:show <Model>` | Inspeciona atributos, tipos e relacionamentos de uma model Eloquent. | Nenhum | Todos |
| `php8.5 artisan model:prune` | Remove modelos antigos que implementam a trait `Prunable`. | Médio | Todos |
| `php8.5 artisan schema:dump` | Exporta o dump do esquema do banco de dados. | Baixo | Local |

### Detalhamento e Exemplos:

#### `php8.5 artisan migrate`
- **Finalidade:** Aplica alterações de esquema no banco MySQL do CER Digital.
- **Opções:** `--force` (obrigatório em ambiente de produção `APP_ENV=production`), `--pretend` (simula SQL sem executar), `--step` (migra uma migration por vez).
- **Exemplo:** `php8.5 artisan migrate --force`
- **Impacto:** DDL no MySQL. Requer backup prévio em produção.

#### `php8.5 artisan db:show`
- **Finalidade:** Inspeciona tamanho das tabelas, motor de armazenamento (InnoDB) e consumo do banco local.
- **Exemplo:** `php8.5 artisan db:show`

#### `php8.5 artisan db:table pacientes`
- **Finalidade:** Detalha colunas, tipos, nulabilidade e chaves da tabela `pacientes`.
- **Exemplo:** `php8.5 artisan db:table pacientes`

---

## 6. CATEGORIA 3 — CACHE E OTIMIZAÇÃO

Comandos para limpeza e compilação de caches essenciais para a performance da aplicação em produção.

| Comando | Finalidade | Risco | Ambiente |
|---|---|---|---|
| `php8.5 artisan optimize` | Compila configurações, rotas e arquivos de bootstrap em cache para máxima performance. | Baixo | Produção / VPS |
| `php8.5 artisan optimize:clear` | Remove todos os caches compilados (config, route, view, events). | Baixo | Todos |
| `php8.5 artisan cache:clear` | Limpa o repositório de cache da aplicação (driver database/file/redis). | Médio | Todos |
| `php8.5 artisan cache:forget <chave>` | Invalida uma chave específica do cache da aplicação. | Baixo | Todos |
| `php8.5 artisan view:cache` | Pré-compila todos os templates Blade para execução imediata. | Baixo | Produção / VPS |
| `php8.5 artisan view:clear` | Apaga os arquivos Blade pré-compilados em `storage/framework/views`. | Baixo | Todos |
| `php8.5 artisan clear-compiled` | Remove os arquivos de classe pré-compilados pelo Composer. | Baixo | Todos |

### Procedimento Recomendado de Deploy:
Após cada deploy ou atualização de código na VPS, executar a sequência:
```bash
php8.5 artisan optimize:clear
php8.5 artisan optimize
```

---

## 7. CATEGORIA 4 — ROTAS, EVENTOS E CONFIGURAÇÕES

Comandos para inspecionar, publicar e compilar arquivos de configuração, rotas HTTP/canais e eventos do Laravel.

| Comando | Finalidade | Risco | Ambiente |
|---|---|---|---|
| `php8.5 artisan config:cache` | Cria arquivo único consolidado de configurações em `bootstrap/cache`. | Baixo | Produção / VPS |
| `php8.5 artisan config:clear` | Remove o cache consolidado de configurações. | Baixo | Todos |
| `php8.5 artisan config:show <chave>` | Exibe o valor de uma chave específica de configuração (ex: `database.connections.mysql`). | Nenhum | Todos |
| `php8.5 artisan route:list` | Lista todas as rotas HTTP, métodos (GET/POST), URIs e middlewares da aplicação. | Nenhum | Todos |
| `php8.5 artisan route:cache` | Compila as rotas em cache binário para alta velocidade de roteamento. | Baixo | Produção / VPS |
| `php8.5 artisan route:clear` | Remove o cache compilado de rotas. | Baixo | Todos |
| `php8.5 artisan event:list` | Lista os eventos da aplicação e seus respectivos listeners associados. | Nenhum | Todos |
| `php8.5 artisan event:cache` | Compila a descoberta de eventos e ouvintes. | Baixo | Produção / VPS |
| `php8.5 artisan event:clear` | Limpa o cache compilado de eventos. | Baixo | Todos |
| `php8.5 artisan channel:list` | Lista todos os canais de broadcasting registrados. | Nenhum | Todos |
| `php8.5 artisan env:encrypt` | Criptografa o arquivo `.env` para armazenamento seguro em repositório. | Médio | Local |
| `php8.5 artisan env:decrypt` | Decifra um arquivo `.env` criptografado com uma chave de decodificação. | Médio | Local / VPS |

### Exemplos:
- Consultar rotas com filtro:
  ```bash
  php8.5 artisan route:list --path=configuracoes
  ```
- Exibir configuração do e-SUS:
  ```bash
  php8.5 artisan config:show database.connections.esus_pec
  ```

---

## 8. CATEGORIA 5 — FILAS E AGENDADOR DE TAREFAS (QUEUES & SCHEDULER)

O CER Digital utiliza filas assíncronas e agendador diário para processamento em segundo plano (especialmente a sincronização noturna do e-SUS APS PEC via `SincronizarEsusPecJob`).

| Comando | Finalidade | Risco | Ambiente |
|---|---|---|---|
| `php8.5 artisan queue:work` | Inicia o worker de fila em modo daemon para consumir jobs pendentes. | Baixo | Todos |
| `php8.5 artisan queue:listen` | Escuta a fila reiniciando o framework a cada job (ideal para dev). | Baixo | Local |
| `php8.5 artisan queue:restart` | Notifica todos os workers ativos para reiniciarem após terminar o job atual. | Baixo | Produção / VPS |
| `php8.5 artisan queue:monitor <filas>` | Monitora o volume de jobs pendentes nas filas informadas. | Nenhum | Produção / VPS |
| `php8.5 artisan queue:failed` | Lista todos os jobs que falharam durante o processamento. | Nenhum | Todos |
| `php8.5 artisan queue:retry <id\|all>` | Reinsere jobs falhos na fila para nova tentativa de execução. | Médio | Todos |
| `php8.5 artisan queue:forget <id>` | Remove um job específico da tabela de falhas (`failed_jobs`). | Baixo | Todos |
| `php8.5 artisan queue:flush` | Remove **todos** os registros da tabela de jobs com falha. | Médio | Todos |
| `php8.5 artisan queue:clear` | Deleta todos os jobs pendentes da fila informada. | **Alto** | Todos |
| `php8.5 artisan queue:prune-failed` | Remove jobs falhos mais antigos que o limite configurado (ex: `--hours=48`). | Baixo | Todos |
| `php8.5 artisan queue:prune-batches` | Limpa lotes de jobs finalizados da tabela `job_batches`. | Baixo | Todos |
| `php8.5 artisan schedule:list` | Lista todas as tarefas programadas no cron e seus horários de execução. | Nenhum | Todos |
| `php8.5 artisan schedule:run` | Executa as tarefas agendadas para o minuto atual (invocado pelo crontab). | Médio | Produção / VPS |
| `php8.5 artisan schedule:work` | Mantém o agendador rodando a cada minuto em primeiro plano. | Baixo | Local |
| `php8.5 artisan schedule:test` | Simula a execução de um comando do agendador para depuração. | Baixo | Local / Homolog |
| `php8.5 artisan schedule:clear-cache` | Deleta os arquivos mutex/locks que controlam tarefas sem sobreposição (`withoutOverlapping`). | Médio | Produção / VPS |

### Configuração Recomendada no Crontab da VPS (Linux):
Para execução das tarefas agendadas (ex: sincronização e-SUS às 02:00):
```cron
* * * * * php8.5 /caminho/do/projeto/artisan schedule:run >> /dev/null 2>&1
```

---

## 9. CATEGORIA 6 — TESTES AUTOMATIZADOS

| Comando | Finalidade | Risco | Ambiente |
|---|---|---|---|
| `php8.5 artisan test` | Executa a suíte de testes automatizados com PHPUnit / Pest. | Nenhum | Local / Homolog |

### Opções e Exemplos:
- Executar suíte completa:
  ```bash
  php8.5 artisan test
  ```
- Executar arquivo de teste específico:
  ```bash
  php8.5 artisan test tests/Feature/UnificarSelecaoUbsPacientesTest.php
  ```
- Executar apenas testes que falharam na última rodada:
  ```bash
  php8.5 artisan test --retry
  ```
- Filtrar por nome do teste:
  ```bash
  php8.5 artisan test --filter=test_selecionar_ubs_sincroniza_nome
  ```

---

## 10. CATEGORIA 7 — DESENVOLVIMENTO E GERAÇÃO DE CÓDIGO (MAKE & SCAFFOLDING)

Comandos para geração de artefatos de código baseados em padrões de arquitetura do Laravel 13 e Livewire 4.

| Comando | Artefato Gerado / Finalidade |
|---|---|
| `php8.5 artisan make:livewire <Nome>` | Cria componente Livewire (classe PHP em `app/Livewire` e view Blade). |
| `php8.5 artisan make:model <Nome> -m` | Cria model Eloquent e migration associada em `database/migrations`. |
| `php8.5 artisan make:migration <nome>` | Cria arquivo de migration para alteração estrutural no MySQL. |
| `php8.5 artisan make:command <Nome>` | Cria novo comando Artisan personalizado em `app/Console/Commands`. |
| `php8.5 artisan make:controller <Nome>` | Cria classe controladora em `app/Http/Controllers`. |
| `php8.5 artisan make:job <Nome>` | Cria classe de job enfileirável em `app/Jobs`. |
| `php8.5 artisan make:rule <Nome>` | Cria classe de validação personalizada em `app/Rules`. |
| `php8.5 artisan make:test <Nome>` | Cria classe de teste automatizado em `tests/Feature` ou `tests/Unit`. |
| `php8.5 artisan make:seeder <Nome>` | Cria classe de seeder de banco de dados em `database/seeders`. |
| `php8.5 artisan make:factory <Nome>` | Cria factory de testes em `database/factories`. |
| `php8.5 artisan make:request <Nome>` | Cria FormRequest para validação HTTP. |
| `php8.5 artisan make:resource <Nome>` | Cria classe de API Resource para transformação JSON. |
| `php8.5 artisan make:observer <Nome>` | Cria Observer de eventos de ciclo de vida de Models Eloquent. |
| `php8.5 artisan make:policy <Nome>` | Cria classe Policy para autorização de acesso a entidades. |
| `php8.5 artisan make:class <Nome>` | Cria uma classe PHP simples com namespace. |
| `php8.5 artisan make:interface <Nome>` | Cria uma interface PHP. |
| `php8.5 artisan make:trait <Nome>` | Cria uma trait PHP reutilizável. |
| `php8.5 artisan make:enum <Nome>` | Cria uma Enum PHP tipada (PHP 8.1+). |
| `php8.5 artisan tinker` | Terminal REPL interativo para consultar e manipular models via PHP CLI. |
| `php8.5 artisan serve` | Inicia o servidor HTTP de desenvolvimento integrado do PHP. |
| `php8.5 artisan inspire` | Exibe uma frase inspiradora no terminal (definido em `routes/console.php`). |

---

## 11. CATEGORIA 8 — INTEGRAÇÃO e-SUS APS PEC (PERSONALIZADOS)

Comandos específicos para validação, teste, importação incremental e complementação de vínculos com o banco PostgreSQL da Atenção Primária à Saúde (e-SUS APS PEC).

> [!CAUTION]
> **REGRAS DE SEGURANÇA OBRIGATÓRIAS PARA e-SUS PEC:**
> 1. O banco PostgreSQL do e-SUS APS PEC deve ser acessado **EXCLUSIVAMENTE EM MODO SOMENTE-LEITURA**. Nenhum comando executa `INSERT`, `UPDATE`, `DELETE` ou `DDL` no servidor de origem do PEC.
> 2. O usuário de conexão no PostgreSQL deve possuir apenas privilégios `GRANT SELECT` nas tabelas necessárias.
> 3. Em execuções com a opção `--dry-run`, **nenhum dado é gravado no MySQL do CER Digital e nenhum checkpoint é alterado**.

---

### 11.1 `php8.5 artisan esus:diagnostico`
- **Arquivo Responsável:** [`app/Console/Commands/DiagnosticoEsusPecCommand.php`](file:///c:/Users/rayhe/Downloads/cerflow/app/Console/Commands/DiagnosticoEsusPecCommand.php)
- **Finalidade:** Testa a conectividade TCP/Rede, credenciais de autenticação, extensão `pdo_pgsql`, presença de tabelas dimensionais/operacionais do PEC e volume de cidadãos cadastrados.
- **Argumentos / Opções:** Nenhuma opção obrigatória. Suporta `--help`, `-v`, `-vvv`.
- **Exemplo Prático:**
  ```bash
  php8.5 artisan esus:diagnostico
  ```
- **Ambiente de Execução:** Todos (Local, Homologação, Produção/VPS).
- **Autorização Necessária:** Nenhuma (operação puramente de diagnóstico de leitura).
- **Impacto:** Zero impacto em disco, banco de dados ou caches.
- **Risco:** **Nenhum**. 100% seguro.

---

### 11.2 `php8.5 artisan esus:sincronizar`
- **Arquivo Responsável:** [`app/Console/Commands/SincronizarEsusPecCommand.php`](file:///c:/Users/rayhe/Downloads/cerflow/app/Console/Commands/SincronizarEsusPecCommand.php)
- **Finalidade:** Sincroniza pacientes, UBS de referência e vínculos de ACS a partir do banco PostgreSQL do e-SUS PEC para a base MySQL local do CER Digital.
- **Opções Suportadas:**
  - `--dry-run`: Executa simulação completa em memória sem gravar no MySQL e sem alterar checkpoints.
  - `--lote=2000`: Quantidade de registros consultados e processados por lote (padrão: 2.000).
  - `--max-registros=N`: Limite máximo de registros lidos do PEC antes de pausar a execução.
  - `--modo=AUTO|INCREMENTAL|COMPLETA`: Define a estratégia de sincronização (padrão: `AUTO`).
  - `--apenas-vinculos`: Não cria nenhum novo paciente. Atualiza exclusivamente os campos `ubs_referencia`, `ubs_id` e `nome_acs` de pacientes que já constam no CER Digital e estejam sem vínculo preenchido.
  - `--reiniciar`: Reseta o checkpoint de sincronização e relê os registros a partir do ID 1 do PEC (não apaga pacientes existentes).
  - `--diagnostico`: Executa o diagnóstico de VPS antes de processar.
  - `--limpar-bloqueio`: Remove travas ativas de execuções anteriores travadas.
  - `--force`: Ignora avisos de confirmação e libera travas existentes.
- **Efeitos Colaterais Reais e Investigação de Dry-Run:**
  - **Com `--dry-run`**: O serviço utiliza instâncias simuladas em memória com IDs negativos (`id = -1, -2...`) para UBS e pacientes. **Nenhuma alteração é gravada no MySQL** e o checkpoint de sincronização permanece inalterado.
  - **Sem `--dry-run` (Execução Real)**: Insere novos pacientes na tabela `pacientes`, atualiza dados cadastrais faltantes e, caso uma UBS válida do PEC (com CNES ou nome conclusivo) ainda não exista no catálogo `unidades_basicas_saude`, ela **será criada automaticamente com `origem = 'ESUS'`**.
- **Exemplos Práticos:**
  ```bash
  # 1. Simulação segura de 100 registros em lotes de 50:
  php8.5 artisan esus:sincronizar --dry-run --max-registros=100 --lote=50

  # 2. Simulação de complementação exclusiva de vínculos de UBS/ACS:
  php8.5 artisan esus:sincronizar --apenas-vinculos --dry-run --max-registros=500 --lote=100

  # 3. Execução real de complementação de vínculos de UBS/ACS:
  php8.5 artisan esus:sincronizar --apenas-vinculos --lote=500

  # 4. Sincronização incremental noturna real:
  php8.5 artisan esus:sincronizar --lote=1000
  ```
- **Ambiente de Execução:** Homologação e Produção / VPS.
- **Autorização Necessária:** **Sim**, autorização prévia da coordenação técnica para execuções reais com `--reiniciar` ou lotes volumosos sem `--dry-run`.
- **Impacto:** Leitura no PostgreSQL (PEC); escrita nas tabelas `pacientes`, `unidades_basicas_saude`, `historicos_sincronizacao_esus` e atualização de checkpoint em `configuracoes_esus_pec` (MySQL).
- **Risco:** **Baixo a Médio** (o serviço não sobrescreve nomes nem dados clínicos previamente digitados).

---

### 11.3 `php8.5 artisan esus:conciliar-pacientes`
- **Arquivo Responsável:** [`app/Console/Commands/ConciliarPacientesUbsCommand.php`](file:///c:/Users/rayhe/Downloads/cerflow/app/Console/Commands/ConciliarPacientesUbsCommand.php)
- **Finalidade:** Audita a tabela local de `pacientes` do CER Digital, identifica registros com `ubs_referencia` textual preenchida mas `ubs_id` nulo, e localiza a correspondência exata no catálogo `unidades_basicas_saude` (por CNES ou por Nome Normalizado + Município IBGE). Vincula o `ubs_id` sem sobrescrever o nome manual do paciente.
- **Opções Suportadas:**
  - `--lote=500`: Tamanho de cada bloco de processamento via `chunkById` (padrão: 500).
  - `--dry-run`: Executa a auditoria e contabilização sem gravar alterações no banco.
- **Exemplos Práticos:**
  ```bash
  # Simulação prévia obrigatória:
  php8.5 artisan esus:conciliar-pacientes --dry-run

  # Execução definitiva em lotes:
  php8.5 artisan esus:conciliar-pacientes --lote=500
  ```
- **Ambiente de Execução:** Todos (Local, Homologação, Produção/VPS).
- **Autorização Necessária:** Recomendada comunicação à coordenação antes da execução definitiva em produção.
- **Impacto:** Atualização da coluna `ubs_id` na tabela `pacientes` (MySQL).
- **Risco:** **Baixo** (operações idempotentes, sem remoção de dados, preservando textos pré-existentes).

---

## 12. CATEGORIA 9 — PROFISSÕES, ESPECIALIDADES E CATÁLOGO CBO (PERSONALIZADOS)

Comandos para importação, manutenção e recuperação de códigos da Classificação Brasileira de Ocupações (CBO/MTE) e vínculo com profissionais de saúde.

---

### 12.1 `php8.5 artisan cer:importar-cbos`
- **Arquivo Responsável:** [`app/Console/Commands/ImportarCbosCommand.php`](file:///c:/Users/rayhe/Downloads/cerflow/app/Console/Commands/ImportarCbosCommand.php)
- **Finalidade:** Importa o catálogo oficial de ocupações da CBO do Ministério do Trabalho e Emprego (MTE) a partir de arquivo CSV em lote com validação de formato e integridade.
- **Argumentos / Opções:**
  - `caminho` *(argumento opcional)*: Caminho para o arquivo CSV de ocupações. Se omitido, procura em locais padrão (`database/seeders/data/CBO2002 - Ocupacao.csv`).
  - `--dry-run`: Valida as linhas do CSV e simula a importação sem alterar o banco de dados.
  - `--atualizar-titulos`: Atualiza o título de CBOs já existentes no banco quando divergirem do arquivo oficial.
  - `--fonte=...`: Fonte de referência oficial (padrão: `"Ministério do Trabalho e Emprego (MTE) - CBO2002"`).
  - `--versao=...`: Versão do catálogo (padrão: `"CBO/2002-Ocupações"`).
- **Exemplos Práticos:**
  ```bash
  # Simulação de importação:
  php8.5 artisan cer:importar-cbos --dry-run

  # Importação oficial real atualizando títulos:
  php8.5 artisan cer:importar-cbos --atualizar-titulos
  ```
- **Ambiente de Execução:** Local, Homologação e Produção / VPS.
- **Autorização Necessária:** Sim, para execuções definitivas em produção.
- **Impacto:** Inserção e atualização na tabela `cbos` (MySQL).
- **Risco:** **Baixo** (não altera especialidades nem vínculos clínicos existentes).

---

### 12.2 `php8.5 artisan cer:recuperar-cbos-legados`
- **Arquivo Responsável:** [`app/Console/Commands/RecuperarCbosLegadosCommand.php`](file:///c:/Users/rayhe/Downloads/cerflow/app/Console/Commands/RecuperarCbosLegadosCommand.php)
- **Finalidade:** Inspeciona os profissionais de saúde já cadastrados na base, extrai especialidades e CBOs legados informados no formato textual/histórico e concilia os vínculos na tabela `cbos` e `profissionais` de forma segura e idempotente.
- **Opções Suportadas:**
  - `--dry-run`: Executa a leitura e diagnóstico de conciliação sem persistir alterações.
- **Exemplos Práticos:**
  ```bash
  # Simulação de recuperação:
  php8.5 artisan cer:recuperar-cbos-legados --dry-run

  # Execução definitiva:
  php8.5 artisan cer:recuperar-cbos-legados
  ```
- **Ambiente de Execução:** Local, Homologação e Produção / VPS.
- **Autorização Necessária:** Sim, da equipe técnica.
- **Impacto:** Atualização dos campos `cbo_id` e `especialidade_id` na tabela `profissionais`.
- **Risco:** **Baixo** (operação idempotente).

---

## 13. CATEGORIA 10 — COMANDOS DE PACOTES (LIVEWIRE, SPATIE PERMISSION, FORTIFY)

### 13.1 Livewire 4 (`livewire:*`)
- `php8.5 artisan livewire:make <Componente>`: Cria novo componente Livewire com Blade view correspondente.
- `php8.5 artisan livewire:layout`: Gera o layout mestre padrão do Livewire.
- `php8.5 artisan livewire:publish`: Publica os arquivos de configuração do Livewire.
- `php8.5 artisan livewire:form <Nome>`: Gera classe especializada Livewire Form Object.
- `php8.5 artisan livewire:attribute <Nome>`: Cria atributo customizado do Livewire.
- `php8.5 artisan livewire:convert <Nome>`: Converte componente entre formato single-file e multi-file.

### 13.2 Spatie Laravel-Permission (`permission:*`)
- `php8.5 artisan permission:show`: Exibe matriz tabular de perfis (`roles`) e permissões (`permissions`) configurados por guard (`web`).
- `php8.5 artisan permission:cache-reset`: Reseta o cache de permissões do Spatie em memória/armazenamento (essencial após criar ou alterar permissões).
- `php8.5 artisan permission:create-role <nome>`: Cria um novo perfil no sistema.
- `php8.5 artisan permission:create-permission <nome>`: Cria uma nova permissão atômica.
- `php8.5 artisan permission:assign-role <role> <user_id>`: Atribui um perfil a um usuário específico.

### 13.3 Laravel Fortify
- `php8.5 artisan fortify:install`: Publica as ações, rotas e configurações do Fortify (autenticação de dois fatores, redefinição de senha).

### 13.4 Storage & Vendor
- `php8.5 artisan storage:link`: Cria link simbólico de `public/storage` para `storage/app/public` (obrigatório para uploads de fotos, anexos e logos).
- `php8.5 artisan storage:unlink`: Remove o link simbólico existente.
- `php8.5 artisan vendor:publish`: Publica assets e configurações de pacotes instalados.
- `php8.5 artisan package:discover`: Reconstrói o manifesto de auto-descoberta de pacotes do Composer.

---

## 14. CATEGORIA 11 — COMANDOS PERIGOSOS E RESTRITOS (DESTRUTIVOS)

> [!CAUTION]
> **ATENÇÃO MÁXIMA:** Os comandos desta seção causam **perda irreversível de dados**, exclusão de tabelas ou interrupção do sistema. **NUNCA execute estes comandos em ambiente de Produção sem autorização expressa e backup prévio do banco MySQL.**

| Comando | Efeito Destrutivo | Nível de Risco | Ambiente Permitido |
|---|---|---|---|
| `php8.5 artisan db:wipe` | **APAGA TODAS AS TABELAS, VIEWS E TIPOS** do banco de dados MySQL sem pedir confirmação se usado com `--force`. | **CRÍTICO** | Apenas Local / Testes |
| `php8.5 artisan migrate:fresh` | Executa `db:wipe` e roda todas as migrations do zero. **ZERA TODO O BANCO.** | **CRÍTICO** | Apenas Local / Testes |
| `php8.5 artisan migrate:reset` | Dá rollback em **TODAS** as migrations da história da aplicação. | **CRÍTICO** | Apenas Local / Testes |
| `php8.5 artisan migrate:refresh` | Reseta e reexecuta todas as migrations. Apaga dados existentes. | **CRÍTICO** | Apenas Local / Testes |
| `php8.5 artisan key:generate` | Gera nova `APP_KEY`. **Invalida todas as senhas criptografadas e dados cifrados no banco.** | **CRÍTICO** | Apenas Instalação Inicial |
| `php8.5 artisan queue:clear` | Deleta todos os jobs da fila sem processá-los. | **ALTO** | Apenas se houver lixo |
| `php8.5 artisan down` | Coloca a aplicação inteira em **Modo de Manutenção** (retorna HTTP 503 aos usuários). | **MÉDIO** | Apenas Janela de Manutenção |
| `php8.5 artisan up` | Tira a aplicação do modo de manutenção, reabrindo o acesso aos usuários. | Baixo | Pós-manutenção |
| `php8.5 artisan esus:sincronizar --reiniciar` | Reseta checkpoints de sincronização e relê o e-SUS PEC do início. | **MÉDIO** | Com autorização |

### Diretrizes de Bloqueio em Produção:
Em servidores de Produção (`APP_ENV=production`), o Laravel automaticamente bloqueia comandos destrutivos sem a flag `--force`. Contudo, a flag `--force` **NÃO DEVE SER USADA** com `migrate:fresh`, `migrate:reset` ou `db:wipe` sob nenhuma hipótese em bases que contenham dados de pacientes e prontuários reais.

---

## 15. CATEGORIA 12 — GUIA DE SOLUÇÃO DE PROBLEMAS E PROCEDIMENTOS DE OPERAÇÃO

### 15.1 Problema: Telas ou estilos do Livewire não atualizam após deploy
**Procedimento:**
```bash
php8.5 artisan optimize:clear
php8.5 artisan view:clear
php8.5 artisan vendor:publish --force --tag=livewire:assets
php8.5 artisan optimize
```

### 15.2 Problema: Permissões de usuários não têm efeito após alteração de roles
**Procedimento:**
```bash
php8.5 artisan permission:cache-reset
php8.5 artisan cache:clear
```

### 15.3 Problema: Execução da sincronização e-SUS APS PEC travada com lock de concorrência
**Sintoma:** O comando `esus:sincronizar` retorna erro informando que outra execução está em andamento.
**Procedimento:**
```bash
# 1. Verificar se existe processo PHP rodando em segundo plano:
ps aux | grep "esus:sincronizar"

# 2. Se o processo anterior morreu abruptamente, liberar a trava com segurança:
php8.5 artisan esus:sincronizar --limpar-bloqueio
```

### 15.4 Problema: Falha de conexão com PostgreSQL do e-SUS PEC
**Sintoma:** O comando falha com mensagem `Driver not found` ou erro de timeout.
**Procedimento:**
```bash
# 1. Verificar se o PHP CLI em uso possui pdo_pgsql:
php8.5 -m | grep pdo_pgsql

# 2. Executar o diagnóstico oficial:
php8.5 artisan esus:diagnostico
```

### 15.5 Procedimento Padrão para Deploy na VPS:
```bash
# 1. Atualizar repositório Git
git pull origin main

# 2. Atualizar dependências Composer (otimizado sem dev)
composer install --no-dev --optimize-autoloader

# 3. Compilar assets de frontend
npm run build

# 4. Executar migrations com segurança
php8.5 artisan migrate --force

# 5. Otimizar caches da aplicação
php8.5 artisan optimize:clear
php8.5 artisan optimize

# 6. Reiniciar workers de fila
php8.5 artisan queue:restart
```

