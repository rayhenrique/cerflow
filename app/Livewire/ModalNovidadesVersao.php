<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\VersionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ModalNovidadesVersao extends Component
{
    public bool $aberto = false;

    public function mount(VersionService $versionService): void
    {
        $versaoAtual = $versionService->versaoAtual();
        $foiLogin = session()->pull('exibir_modal_versao', false) === true;
        $ultimaVersaoVista = session()->get('ultima_versao_vista');

        if ($foiLogin || $ultimaVersaoVista !== $versaoAtual) {
            $this->aberto = true;
            session()->put('ultima_versao_vista', $versaoAtual);
        }
    }

    #[On('abrir-modal-versao')]
    public function abrirModal(): void
    {
        $this->aberto = true;
    }

    public function fecharModal(VersionService $versionService): void
    {
        session()->forget('exibir_modal_versao');
        session()->put('ultima_versao_vista', $versionService->versaoAtual());
        $this->aberto = false;
    }

    public function render(VersionService $versionService): View
    {
        return view('livewire.modal-novidades-versao', [
            'releaseAtual' => $versionService->releaseAtual(),
        ]);
    }
}
