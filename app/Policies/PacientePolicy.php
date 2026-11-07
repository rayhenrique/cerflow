<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Paciente;
use App\Models\User;

class PacientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']);
    }

    public function view(User $user, Paciente $paciente): bool
    {
        return $user->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['recepcao', 'coordenacao']);
    }

    public function update(User $user, Paciente $paciente): bool
    {
        return $user->hasAnyRole(['recepcao', 'coordenacao']);
    }

    /**
     * Acesso ao PEP do paciente é estritamente bloqueado para recepção e faturamento (RNF1).
     */
    public function viewProntuario(User $user, Paciente $paciente): bool
    {
        return $user->hasAnyRole(['terapeuta', 'coordenacao']);
    }

    /**
     * Visualização e edição de diagnóstico clínico (CID-10 e modalidades) reservada à equipe clínica.
     */
    public function viewClinicalData(User $user, Paciente $paciente): bool
    {
        return $user->hasAnyRole(['terapeuta', 'coordenacao']);
    }

    public function updateClinicalData(User $user, Paciente $paciente): bool
    {
        return $user->hasAnyRole(['terapeuta', 'coordenacao']);
    }

    public function delete(User $user, Paciente $paciente): bool
    {
        return $user->hasRole('coordenacao');
    }
}
