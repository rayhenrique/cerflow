<?php

declare(strict_types=1);

namespace App\Support\Navigation;

use App\Models\User;
use Illuminate\Http\Request;

class NavigationGroup
{
    /**
     * @param  array<int, NavigationItem>  $itens
     */
    public function __construct(
        public string $id,
        public string $titulo,
        public int $ordem = 10,
        public bool $expansivel = true,
        public bool $padraoAberto = false,
        public array $itens = [],
    ) {}

    public function adicionarItem(NavigationItem $item): self
    {
        $this->itens[] = $item;

        return $this;
    }

    /**
     * @return array<int, NavigationItem>
     */
    public function itensAutorizados(?User $user): array
    {
        $filtrados = array_filter(
            $this->itens,
            fn (NavigationItem $item): bool => $item->autorizadoPara($user)
        );

        usort($filtrados, fn (NavigationItem $a, NavigationItem $b): int => $a->ordem <=> $b->ordem);

        return array_values($filtrados);
    }

    public function temItemAtivo(Request $request, ?User $user = null): bool
    {
        foreach ($this->itensAutorizados($user) as $item) {
            if ($item->estaAtivo($request)) {
                return true;
            }
        }

        return false;
    }

    public function visivelPara(?User $user): bool
    {
        return count($this->itensAutorizados($user)) > 0;
    }

    public function deveIniciarAberto(Request $request, ?User $user = null): bool
    {
        if (! $this->expansivel) {
            return true;
        }

        if ($this->temItemAtivo($request, $user)) {
            return true;
        }

        return $this->padraoAberto;
    }
}
