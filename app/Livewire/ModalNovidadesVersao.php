<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Services\VersionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ModalNovidadesVersao extends Component
{
    public bool $aberto = false;

    public function mount(VersionService $versionService): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $versaoAtual = $versionService->versaoAtual();

            if ($user->ultima_versao_novidades_vista !== $versaoAtual) {
                $this->aberto = true;
            }
        }
    }

    #[On('abrir-modal-versao')]
    public function abrirModal(): void
    {
        $this->aberto = true;
    }

    public function fecharModal(VersionService $versionService): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $versaoAtual = $versionService->versaoAtual();
            $user->update([
                'ultima_versao_novidades_vista' => $versaoAtual,
            ]);
        }

        $this->aberto = false;
    }

    public function irParaAjudaSobre(VersionService $versionService)
    {
        $this->fecharModal($versionService);

        return $this->redirectRoute('ajuda.sobre', navigate: true);
    }

    public function render(VersionService $versionService): View
    {
        return view('livewire.modal-novidades-versao', [
            'releaseAtual' => $versionService->releaseAtual(),
        ]);
    }
}
