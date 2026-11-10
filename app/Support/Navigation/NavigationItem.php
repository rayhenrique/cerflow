<?php

declare(strict_types=1);

namespace App\Support\Navigation;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class NavigationItem
{
    /**
     * @param  array<string, mixed>  $parametros
     * @param  array<int, string>  $perfis
     */
    public function __construct(
        public string $id,
        public string $grupo,
        public string $rotulo,
        public string $icone,
        public string $rota,
        public array $parametros = [],
        public array $perfis = [],
        public int $ordem = 10,
        public ?string $target = null,
        public ?string $badge = null,
        public ?string $tooltip = null,
        public ?Closure $regraAtivo = null,
    ) {}

    public function url(): string
    {
        return route($this->rota, $this->parametros);
    }

    public function estaAtivo(Request $request): bool
    {
        if ($this->regraAtivo !== null) {
            return (bool) ($this->regraAtivo)($request);
        }

        if ($this->rota === 'configuracoes.secao') {
            $secaoRota = $request->route('secao');
            $secaoItem = (string) ($this->parametros['secao'] ?? '');

            if ($request->routeIs('configuracoes.*')) {
                if ($secaoRota !== null && $secaoRota !== '') {
                    return $secaoRota === $secaoItem;
                }

                // Se a URL for /configuracoes sem parametro secao, detecta a aba padrao do perfil
                /** @var User|null $user */
                $user = $request->user();
                if ($user !== null) {
                    if ($user->hasRole('coordenacao') || $user->hasRole('recepcao')) {
                        return $secaoItem === 'pacientes';
                    }
                    if ($user->hasRole('faturamento')) {
                        return $secaoItem === 'sigtap';
                    }

                    return $secaoItem === 'perfil';
                }
            }

            return false;
        }

        return $request->routeIs($this->rota) || $request->routeIs($this->rota.'.*');
    }

    public function autorizadoPara(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->perfis === []) {
            return true;
        }

        return $user->hasAnyRole($this->perfis);
    }

    public function getTooltip(): string
    {
        return $this->tooltip ?? $this->rotulo;
    }
}
