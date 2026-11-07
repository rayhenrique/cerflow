<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EsusPecIntegrationService;
use Illuminate\Console\Command;
use Throwable;

class DiagnosticoEsusPecCommand extends Command
{
    /**
     * A assinatura do comando de diagnóstico do e-SUS PEC via CLI.
     *
     * @var string
     */
    protected $signature = 'esus:diagnostico';

    /**
     * Descrição do comando exibida na listagem do Artisan.
     *
     * @var string
     */
    protected $description = 'Executa diagnóstico somente-leitura da estrutura e vínculos no PostgreSQL do e-SUS APS PEC';

    public function handle(EsusPecIntegrationService $service): int
    {
        try {
            $dados = $service->executarDiagnosticoVps();

            $sincronizarCmd = new SincronizarEsusPecCommand;
            $sincronizarCmd->setOutput($this->output);
            $sincronizarCmd->exibirRelatorioDiagnostico($dados);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('========================================================================');
            $this->error(' [ERRO AO EXECUTAR DIAGNÓSTICO DO e-SUS PEC]');
            $this->error('========================================================================');
            $this->error($e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }
    }
}

