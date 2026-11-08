<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\ChamadaPainel;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChamadaPacienteService
{
    /**
     * Verifica se o usuário autenticado tem permissão para chamar o paciente deste agendamento.
     */
    public function podeChamar(Agendamento $agendamento, User $usuario): bool
    {
        if ($agendamento->status !== 'PRESENTE') {
            return false;
        }

        if ($usuario->hasAnyRole(['coordenacao', 'recepcao'])) {
            return true;
        }

        if ($usuario->hasRole('terapeuta')) {
            $profissionalId = $usuario->profissional?->id;
            if ($profissionalId === null) {
                return false;
            }

            // Verifica se o profissional faz parte da equipe deste agendamento
            if ($agendamento->relationLoaded('profissionais')) {
                return $agendamento->profissionais->contains('id', $profissionalId);
            }

            return $agendamento->profissionais()->where('profissionais.id', $profissionalId)->exists();
        }

        return false;
    }

    /**
     * Registra uma chamada (ou repetição de chamada) para ser exibida no painel da TV.
     */
    public function chamarPaciente(Agendamento $agendamento, User $usuario, string $tipo = 'CHAMADA'): ChamadaPainel
    {
        if ($agendamento->status !== 'PRESENTE') {
            throw ValidationException::withMessages([
                'status' => 'Somente agendamentos com status PRESENTE podem ser chamados para a sala de atendimento.',
            ]);
        }

        if (! $this->podeChamar($agendamento, $usuario)) {
            throw new AuthorizationException('Você não possui autorização para chamar este paciente.');
        }

        if (! $agendamento->relationLoaded('paciente')) {
            $agendamento->load('paciente');
        }
        if (! $agendamento->relationLoaded('sala')) {
            $agendamento->load('sala');
        }
        if (! $agendamento->relationLoaded('profissionais')) {
            $agendamento->load('profissionais');
        }

        $paciente = $agendamento->paciente;
        if ($paciente === null) {
            throw ValidationException::withMessages([
                'paciente' => 'Agendamento sem paciente associado.',
            ]);
        }

        $sala = $agendamento->sala;
        if ($sala === null) {
            throw ValidationException::withMessages([
                'sala' => 'Agendamento sem sala associada.',
            ]);
        }

        // Em atendimentos compartilhados, combina os nomes dos profissionais participantes
        $profissionaisNomes = $agendamento->profissionais->pluck('nome')->filter()->join(' + ');
        if ($profissionaisNomes === '') {
            $profissionaisNomes = $usuario->name;
        }

        $profissionalId = $usuario->profissional?->id ?? $agendamento->profissionais->first()?->id;

        return DB::transaction(function () use ($agendamento, $paciente, $sala, $profissionalId, $profissionaisNomes, $usuario, $tipo): ChamadaPainel {
            $chamada = ChamadaPainel::create([
                'agendamento_id' => $agendamento->id,
                'paciente_id' => $paciente->id,
                'sala_id' => $sala->id,
                'profissional_id' => $profissionalId,
                'chamado_por_id' => $usuario->id,
                'paciente_nome' => $paciente->nome,
                'sala_nome' => $sala->nome,
                'profissional_nome' => $profissionaisNomes,
                'tipo' => in_array($tipo, ['CHAMADA', 'REPETICAO'], true) ? $tipo : 'CHAMADA',
                'status' => 'CHAMANDO',
                'chamado_em' => now(),
            ]);

            LogAuditoria::create([
                'user_id' => $usuario->id,
                'acao' => $tipo === 'REPETICAO' ? 'REPETICAO_CHAMADA_TV' : 'CHAMADA_PACIENTE_TV',
                'tabela_afetada' => 'chamadas_painel',
                'registro_id' => $chamada->id,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'detalhes' => [
                    'agendamento_id' => $agendamento->id,
                    'paciente_id' => $paciente->id,
                    'paciente_nome' => $paciente->nome,
                    'sala_nome' => $sala->nome,
                    'profissional_nome' => $profissionaisNomes,
                    'tipo' => $tipo,
                ],
            ]);

            return $chamada;
        });
    }

    /**
     * Repete a chamada do agendamento.
     */
    public function repetirChamada(int $agendamentoId, User $usuario): ChamadaPainel
    {
        $agendamento = Agendamento::query()
            ->with(['paciente', 'sala', 'profissionais'])
            ->findOrFail($agendamentoId);

        return $this->chamarPaciente($agendamento, $usuario, 'REPETICAO');
    }
}
