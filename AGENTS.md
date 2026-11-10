<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Regras Permanentes do Projeto CER Digital

## 1. Documentação Obrigatória dos Comandos Artisan (`comandos-cli.md`)

**Regra Permanente:** Sempre que um comando Artisan for **criado**, **alterado**, **renomeado** ou **removido**, o agente ou desenvolvedor **DEVE atualizar `comandos-cli.md` na mesma tarefa e no mesmo commit.**

A documentação deve obrigatoriamente incluir:
1. **Assinatura correta** no padrão operacional: `php8.5 artisan <comando>`.
2. **Finalidade** e descrição objetiva da funcionalidade.
3. **Argumentos e opções** suportados (especialmente flags como `--dry-run`, `--lote`, `--max-registros`, `--reiniciar`, `--apenas-vinculos`, `--force`, `--limpar-bloqueio`).
4. **Exemplos práticos de uso** para ambientes Linux/VPS e desenvolvimento local.
5. **Ambiente de execução permitido** (`Local`, `Homologação`, `Produção / VPS`).
6. **Necessidade de autorização prévia** da coordenação ou gestão técnica.
7. **Impacto detalhado** em banco de dados, memória/cache ou arquivos em disco.
8. **Classificação de risco** de perda de dados ou indisponibilidade (Nenhum, Baixo, Médio, Alto, Crítico).
9. **Arquivo(s) responsável(is)** pela implementação (`app/Console/Commands/...` ou `routes/console.php`).

Nenhuma implementação de novo comando CLI ou alteração de comando existente deve ser considerada concluída sem a respectiva atualização em `comandos-cli.md` e a validação do teste automatizado correspondente (`tests/Feature/DocumentacaoComandosCliTest.php`).

