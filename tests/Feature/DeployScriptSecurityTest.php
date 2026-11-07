<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DeployScriptSecurityTest extends TestCase
{
    public function test_deploy_script_interrompe_se_arquivo_env_estiver_ausente(): void
    {
        $deployScriptPath = base_path('deploy.sh');
        $this->assertFileExists($deployScriptPath);

        $conteudoScript = (string) file_get_contents($deployScriptPath);

        // Valida que o script verifica existência de .env antes de entrar em manutenção
        $posicaoCheckEnv = strpos($conteudoScript, 'if [ ! -f .env ]; then');
        $posicaoArtisanDown = strpos($conteudoScript, 'php artisan down');

        $this->assertNotFalse($posicaoCheckEnv, 'O script deve verificar a existência de .env');
        $this->assertNotFalse($posicaoArtisanDown, 'O script deve conter php artisan down');
        $this->assertLessThan($posicaoArtisanDown, $posicaoCheckEnv, 'A verificação de .env deve ocorrer ANTES de colocar a aplicação em manutenção');
    }

    public function test_deploy_script_interrompe_se_app_key_estiver_ausente_ou_vazia(): void
    {
        $deployScriptPath = base_path('deploy.sh');
        $conteudoScript = (string) file_get_contents($deployScriptPath);

        $posicaoCheckAppKey = strpos($conteudoScript, 'APP_KEY_VALUE=');
        $posicaoArtisanDown = strpos($conteudoScript, 'php artisan down');

        $this->assertNotFalse($posicaoCheckAppKey, 'O script deve validar APP_KEY');
        $this->assertLessThan($posicaoArtisanDown, $posicaoCheckAppKey, 'A validação de APP_KEY deve ocorrer antes do modo de manutenção');
        $this->assertStringContainsString('Variável APP_KEY ausente ou vazia no arquivo .env!', $conteudoScript);
    }

    public function test_deploy_script_nao_contem_comandos_destrutivos_para_env_ou_app_key(): void
    {
        $deployScriptPath = base_path('deploy.sh');
        $conteudoScript = (string) file_get_contents($deployScriptPath);

        // Não deve executar key:generate automaticamente
        $this->assertStringNotContainsString('key:generate', $conteudoScript, 'O deploy em produção NUNCA deve chamar key:generate automaticamente');

        // Não deve sobrescrever .env com .env.example
        $this->assertStringNotContainsString('cp .env.example .env', $conteudoScript, 'O deploy em produção NUNCA deve copiar .env.example sobre .env');

        // Não deve usar git clean com remoção forçada de arquivos ignorados (-x)
        $this->assertStringNotContainsString('git clean -x', $conteudoScript, 'O script não deve remover arquivos ignorados com git clean -x');
    }

    public function test_env_esta_estritamente_ignorado_no_gitignore(): void
    {
        $gitignorePath = base_path('.gitignore');
        $this->assertFileExists($gitignorePath);

        $linhasGitignore = file($gitignorePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        $this->assertContains('.env', $linhasGitignore, 'O arquivo .env deve estar no .gitignore');
    }

    public function test_deploy_script_possui_salvaguarda_e_recuperacao_de_manutencao(): void
    {
        $deployScriptPath = base_path('deploy.sh');
        $conteudoScript = (string) file_get_contents($deployScriptPath);

        $this->assertStringContainsString('.env.deploy_guard.bak', $conteudoScript, 'O script deve criar salvaguarda temporária do .env durante operações Git');
        $this->assertStringContainsString("trap '", $conteudoScript, 'O script deve conter trap de recuperação em caso de erro');
        $this->assertStringContainsString('php artisan up || true', $conteudoScript, 'O trap deve garantir retorno da aplicação do modo de manutenção');
    }
}
