<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\EsusPecIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SincronizarEsusPecJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O tempo máximo em segundos que o job pode rodar antes de expirar.
     */
    public int $timeout = 300;

    /**
     * Número máximo de tentativas de execução.
     */
    public int $tries = 2;

    public function __construct(
        public string $tipoExecucao = 'AGENDADA'
    ) {}

    public function handle(EsusPecIntegrationService $service): void
    {
        $service->sincronizarPacientes($this->tipoExecucao);
    }
}
