<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\EsusPecIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SincronizarEsusPecJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O tempo máximo em segundos que o job pode rodar antes de expirar.
     */
    public int $timeout = 600;

    /**
     * Número máximo de tentativas de execução.
     */
    public int $tries = 1;

    /**
     * Tempo de expiração do bloqueio de unicidade (2 horas).
     */
    public int $uniqueFor = 7200;

    /**
     * Identificador estável de unicidade para evitar sincronizações simultâneas.
     */
    public function uniqueId(): string
    {
        return 'sincronizacao_esus_pec';
    }

    public function __construct(
        public string $tipoExecucao = 'AGENDADA',
        public ?int $historicoId = null,
        public ?string $forcarModo = null
    ) {}

    public function handle(EsusPecIntegrationService $service): void
    {
        $service->sincronizarPacientes($this->tipoExecucao, null, $this->historicoId, null, $this->forcarModo);
    }
}
