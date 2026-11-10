<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agendamento;
use App\Models\User;

class AgendamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']);
    }

    public function view(User $user, Agendamento $agendamento): bool
    {
        return $user->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['recepcao', 'terapeuta', 'coordenacao']);
    }

    public function update(User $user, Agendamento $agendamento): bool
    {
        return $user->hasAnyRole(['recepcao', 'coordenacao']);
    }

    public function checkin(User $user, Agendamento $agendamento): bool
    {
        return $user->hasAnyRole(['recepcao', 'terapeuta', 'coordenacao']);
    }

    public function delete(User $user, Agendamento $agendamento): bool
    {
        return $user->hasRole('coordenacao');
    }
}
