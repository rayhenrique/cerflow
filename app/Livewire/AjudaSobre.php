<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\BpaiExportService;
use App\Services\VersionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('components.layouts.app')]
#[Title('Ajuda & Sobre o Sistema — CER Digital')]
class AjudaSobre extends Component
{
    public string $abaAtiva = 'evolucao';

    public string $secaoManual = 'todos';

    public function selecionarAba(string $aba): void
    {
        if (in_array($aba, ['evolucao', 'ajuda'], true)) {
            $this->abaAtiva = $aba;
        }
    }

    public function selecionarSecaoManual(string $secao): void
    {
        if (in_array($secao, ['todos', 'acesso', 'recepcao', 'terapeutas', 'faturamento', 'relatorios', 'coordenacao', 'checklists', 'faq', 'glossario'], true)) {
            $this->secaoManual = $secao;
        }
    }

    public function baixarManual(VersionService $versionService): ?BinaryFileResponse
    {
        $caminho = base_path('docs/MANUAL-DO-USUARIO.md');
        if (file_exists($caminho)) {
            $versao = $versionService->versaoAtual();

            return response()->download($caminho, "Manual-do-Usuario-CER-Digital-v{$versao}.md", [
                'Content-Type' => 'text/markdown; charset=UTF-8',
            ]);
        }

        return null;
    }

    public function render(VersionService $versionService): View
    {
        return view('livewire.ajuda-sobre', [
            'versaoAtual' => $versionService->versaoAtual(),
            'historicoVersoes' => $versionService->historicoVersoes(),
            'infoAmbiente' => [
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'cnes' => BpaiExportService::CNES_PADRAO,
            ],
        ]);
    }
}
