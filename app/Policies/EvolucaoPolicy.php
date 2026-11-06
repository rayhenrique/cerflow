<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EvolucaoClinica;
use App\Models\User;

class EvolucaoPolicy
{
    /**
     * Recepção e Faturamento não possuem acesso às anotações clínicas (RNF1).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['terapeuta', 'coordenacao']);
    }

    /**
     * Visualização da evolução clínica restrita a terapeutas e coordenação.
     */
    public function view(User $user, EvolucaoClinica $evolucao): bool
    {
        return $user->hasAnyRole(['terapeuta', 'coordenacao']);
    }

    /**
     * Criação de evolução clínica permitida a terapeutas e coordenação com perfil profissional ativo.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['terapeuta', 'coordenacao'])
            && $user->profissional !== null
            && $user->profissional->ativo;
    }

    /**
     * RN03 - Trava de 24 Horas e autoria estrita:
     * - Apenas o profissional autor da evolução pode alterá-la.
     * - Bloqueia edição se decorridas 24 horas da assinatura (now()->diffInHours($evolucao->assinado_em) >= 24).
     */
    public function update(User $user, EvolucaoClinica $evolucao): bool
    {
        $profissional = $user->profissional;

        if ($profissional === null || ! $profissional->ativo) {
            return false;
        }

        if ((int) $evolucao->profissional_id !== (int) $profissional->id) {
            return false;
        }

        if ($evolucao->bloqueado_edicao) {
            return false;
        }

        if ($evolucao->assinado_em !== null && abs(now()->diffInHours($evolucao->assinado_em)) >= 24) {
            return false;
        }

        return true;
    }

    /**
     * Permite ao profissional autor registrar termo de adendo retificador (inclusive após 24h).
     */
    public function createAdendo(User $user, EvolucaoClinica $evolucao): bool
    {
        $profissional = $user->profissional;

        if ($profissional === null || ! $profissional->ativo) {
            return false;
        }

        return (int) $evolucao->profissional_id === (int) $profissional->id;
    }

    /**
     * Prontuários clínicos assinados jamais podem ser excluídos.
     */
    public function delete(User $user, EvolucaoClinica $evolucao): bool
    {
        return false;
    }
}
