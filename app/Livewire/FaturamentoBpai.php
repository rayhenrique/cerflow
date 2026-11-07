<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ConfiguracaoEstabelecimento;
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

        abort_unless(preg_match('/^\d{7}$/', ConfiguracaoEstabelecimento::atual()->cnes) === 1, 422, 'Configure um CNES válido de 7 dígitos em Configurações > Identificação do Estabelecimento antes de exportar o BPA-i.');

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
