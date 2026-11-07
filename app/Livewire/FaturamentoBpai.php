<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\BpaiExportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Faturamento e Pré-Auditoria BPA-i — CER Digital')]
class FaturamentoBpai extends Component
{
    public string $competencia = '';

    public function mount(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['faturamento', 'coordenacao']),
            403
        );

        $this->competencia = CarbonImmutable::now()->format('Ym');
    }

    public function exportarArquivoTxt(BpaiExportService $bpaiExportService): StreamedResponse
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['faturamento', 'coordenacao']),
            403
        );

        return $bpaiExportService->exportarStream(
            competencia: $this->competencia,
            usuarioExecutor: auth()->user(),
            ipAddress: request()->ip(),
        );
    }

    public function render(BpaiExportService $bpaiExportService): View
    {
        $auditoria = $bpaiExportService->auditarCompetencia($this->competencia, true);

        return view('livewire.faturamento-bpai', [
            'auditoria' => $auditoria,
        ]);
    }
}
