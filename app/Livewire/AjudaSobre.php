<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\BpaiExportService;
use App\Services\VersionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Ajuda & Sobre o Sistema — CER Digital')]
class AjudaSobre extends Component
{
    public string $abaAtiva = 'evolucao';

    public function selecionarAba(string $aba): void
    {
        if (in_array($aba, ['evolucao', 'ajuda'], true)) {
            $this->abaAtiva = $aba;
        }
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
