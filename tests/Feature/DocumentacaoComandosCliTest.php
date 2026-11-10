<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentacaoComandosCliTest extends TestCase
{
    private string $caminhoDocCli;

    private string $conteudoDocCli;

    protected function setUp(): void
    {
        parent::setUp();
        $this->caminhoDocCli = base_path('comandos-cli.md');
        $this->assertTrue(File::exists($this->caminhoDocCli), 'O arquivo comandos-cli.md deve existir na raiz do projeto.');
        $this->conteudoDocCli = File::get($this->caminhoDocCli);
    }

    public function test_arquivo_comandos_cli_contem_padrao_obrigatorio_php85(): void
    {
        $this->assertStringContainsString(
            'php8.5 artisan',
            $this->conteudoDocCli,
            'O arquivo comandos-cli.md deve definir e exemplificar o padrão obrigatório php8.5 artisan.'
        );

        $this->assertStringContainsString(
            'pdo_pgsql',
            $this->conteudoDocCli,
            'O arquivo deve explicar a necessidade da extensão pdo_pgsql no PHP 8.5 para o e-SUS APS PEC.'
        );
    }

    public function test_todos_os_comandos_personalizados_do_cer_digital_estao_documentados(): void
    {
        $comandosPersonalizadosEsperados = [
            'esus:diagnostico',
            'esus:sincronizar',
            'esus:conciliar-pacientes',
            'cer:importar-cbos',
            'cer:recuperar-cbos-legados',
            'inspire',
        ];

        foreach ($comandosPersonalizadosEsperados as $cmd) {
            $this->assertStringContainsString(
                $cmd,
                $this->conteudoDocCli,
                "O comando personalizado '{$cmd}' deve estar documentado em comandos-cli.md."
            );

            // Verifica se está documentado sob a chamada padrão php8.5 artisan
            $this->assertMatchesRegularExpression(
                '/php8\.5\s+artisan\s+'.preg_quote($cmd, '/').'/',
                $this->conteudoDocCli,
                "O comando '{$cmd}' deve possuir exemplo com a sintaxe oficial 'php8.5 artisan {$cmd}'."
            );
        }
    }

    public function test_opcoes_criticas_dos_comandos_personalizados_estao_documentadas(): void
    {
        $opcoesEssenciaisEsus = [
            '--dry-run',
            '--lote',
            '--max-registros',
            '--modo',
            '--reiniciar',
            '--apenas-vinculos',
            '--force',
            '--limpar-bloqueio',
        ];

        foreach ($opcoesEssenciaisEsus as $opcao) {
            $this->assertStringContainsString(
                $opcao,
                $this->conteudoDocCli,
                "A opção '{$opcao}' do comando esus:sincronizar deve estar documentada em comandos-cli.md."
            );
        }

        // Verifica opções de importação de CBOs
        $this->assertStringContainsString('--atualizar-titulos', $this->conteudoDocCli);
        $this->assertStringContainsString('--fonte', $this->conteudoDocCli);
        $this->assertStringContainsString('--versao', $this->conteudoDocCli);
    }

    public function test_estrutura_de_doze_categorias_esta_presente_no_documento(): void
    {
        $categorias = [
            'Informações e Diagnóstico',
            'Banco de Dados e Migrations',
            'Cache e Otimização',
            'Rotas',
            'Filas e Agendador de Tarefas',
            'Testes',
            'Desenvolvimento e Geração de Código',
            'Integração e-SUS APS PEC',
            'Profissões',
            'Comandos de Pacotes',
            'Comandos Perigosos e Restritos',
            'Solução de Problemas',
        ];

        foreach ($categorias as $categoria) {
            $this->assertStringContainsString(
                $categoria,
                $this->conteudoDocCli,
                "A categoria '{$categoria}' deve estar presente na organização de comandos-cli.md."
            );
        }
    }

    public function test_readme_referencia_comandos_cli_e_nao_contem_comando_obsoleto(): void
    {
        $caminhoReadme = base_path('README.md');
        $this->assertTrue(File::exists($caminhoReadme));
        $conteudoReadme = File::get($caminhoReadme);

        $this->assertStringContainsString(
            'comandos-cli.md',
            $conteudoReadme,
            'O arquivo README.md deve referenciar o catálogo oficial comandos-cli.md.'
        );

        $this->assertStringNotContainsString(
            'esus:sincronizar-pec',
            $conteudoReadme,
            'O comando obsoleto/inexistente esus:sincronizar-pec não deve constar no README.md.'
        );

        $this->assertStringContainsString(
            'php8.5 artisan esus:sincronizar',
            $conteudoReadme,
            'O README.md deve apontar para a sintaxe oficial php8.5 artisan esus:sincronizar.'
        );
    }

    public function test_agents_md_contem_regra_permanente_de_atualizacao_cli(): void
    {
        $caminhoAgents = base_path('AGENTS.md');
        $this->assertTrue(File::exists($caminhoAgents));
        $conteudoAgents = File::get($caminhoAgents);

        $this->assertStringContainsString(
            'comandos-cli.md',
            $conteudoAgents,
            'O arquivo AGENTS.md deve conter a regra permanente sobre comandos-cli.md.'
        );

        $this->assertStringContainsString(
            'php8.5 artisan',
            $conteudoAgents,
            'A regra permanente em AGENTS.md deve citar o padrão php8.5 artisan.'
        );
    }
}
