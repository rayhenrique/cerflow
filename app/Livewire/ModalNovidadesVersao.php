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

    public function mount(): void
    {
        if (session()->get('exibir_modal_versao', false) === true) {
            $this->aberto = true;
        }
    }

    #[On('abrir-modal-versao')]
    public function abrirModal(): void
    {
        $this->aberto = true;
    }

    public function fecharModal(): void
    {
        session()->forget('exibir_modal_versao');
        $this->aberto = false;
    }

    public function render(VersionService $versionService): View
    {
        return view('livewire.modal-novidades-versao', [
            'releaseAtual' => $versionService->releaseAtual(),
        ]);
    }
}

