<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Paciente;
use App\Rules\CnsValido;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgendamentoService
{
    private const DIAS_SEMANA_MAP = [
        1 => 'SEGUNDA',
        2 => 'TERCA',
        3 => 'QUARTA',
        4 => 'QUINTA',
        5 => 'SEXTA',
    ];

    /**
     * Cria um agendamento único ou grade semanal recorrente com alocação de 1 ou mais profissionais (co-atendimento).
     *
     * @param  array<string, mixed>  $dados
     * @param  array<int, int|string>  $profissionaisIds
     * @return Collection<int, Agendamento>
     */
    public function criarGrade(array $dados, array $profissionaisIds, int $semanasRecorrencia = 1): Collection
    {
        $profissionaisLimpos = array_values(array_unique(array_map('intval', $profissionaisIds)));
        if ($profissionaisLimpos === []) {
            throw ValidationException::withMessages([
                'profissionais' => 'Selecione ao menos um profissional para o agendamento.',
            ]);
        }

        /** @var ?Paciente $paciente */
        $paciente = Paciente::find($dados['paciente_id'] ?? null);
        if ($paciente === null) {
            throw ValidationException::withMessages([
                'paciente_id' => 'O paciente selecionado não foi encontrado no sistema.',
            ]);
        }

        if ($paciente->data_nascimento === null) {
            throw ValidationException::withMessages([
                'paciente_id' => 'O paciente selecionado não possui data de nascimento cadastrada (RN02).',
            ]);
        }

        if (empty($paciente->cns) || ! CnsValido::validar((string) $paciente->cns)) {
            throw ValidationException::withMessages([
                'paciente_id' => 'O paciente selecionado não possui CNS válido cadastrado (RN02). É obrigatório registrar o CNS no cadastro do paciente antes de realizar o agendamento no SUS.',
            ]);
        }

        $tipoAtendimento = (string) ($dados['tipo_atendimento'] ?? (count($profissionaisLimpos) > 1 ? 'COMPARTILHADO' : 'INDIVIDUAL'));
        if (count($profissionaisLimpos) > 1 && $tipoAtendimento === 'INDIVIDUAL') {
            $tipoAtendimento = 'COMPARTILHADO';
        }

        $recorrente = (bool) ($dados['recorrente'] ?? false);
        $totalSemanas = $recorrente ? max(1, $semanasRecorrencia) : 1;
        $dataBase = CarbonImmutable::parse((string) $dados['data_atendimento']);
        $horaInicio = (string) $dados['hora_inicio'];
        $horaFim = (string) $dados['hora_fim'];
        $salaId = (int) $dados['sala_id'];

        return DB::transaction(function () use (
            $dados,
            $paciente,
            $profissionaisLimpos,
            $tipoAtendimento,
            $recorrente,
            $totalSemanas,
            $dataBase,
            $horaInicio,
            $horaFim,
            $salaId
        ): Collection {
            $criados = collect();

            for ($semana = 0; $semana < $totalSemanas; $semana++) {
                $dataSlot = $dataBase->addWeeks($semana);
                $diaSemana = (string) ($dados['dia_semana'] ?? (self::DIAS_SEMANA_MAP[$dataSlot->dayOfWeekIso] ?? 'SEGUNDA'));

                $this->validarConflitoDeGrade(
                    dataAtendimento: $dataSlot->toDateString(),
                    horaInicio: $horaInicio,
                    horaFim: $horaFim,
                    salaId: $salaId,
                    pacienteId: (int) $paciente->id,
                    tipoAtendimento: $tipoAtendimento,
                );

                $agendamento = Agendamento::create([
                    'paciente_id' => $paciente->id,
                    'sala_id' => $salaId,
                    'data_atendimento' => $dataSlot->toDateString(),
                    'hora_inicio' => $horaInicio,
                    'hora_fim' => $horaFim,
                    'dia_semana' => $diaSemana,
                    'tipo_atendimento' => $tipoAtendimento,
                    'status' => $dados['status'] ?? 'AGENDADO',
                    'recorrente' => $recorrente,
                    'observacao' => $dados['observacao'] ?? null,
                ]);

                $pivotData = [];
                foreach ($profissionaisLimpos as $indice => $profId) {
                    $pivotData[$profId] = [
                        'papel' => ($indice === 0 && $tipoAtendimento === 'INDIVIDUAL') ? 'PRINCIPAL' : ($indice === 0 ? 'PRINCIPAL' : 'COMPARTILHADO'),
                    ];
                }

                $agendamento->profissionais()->sync($pivotData);
                $criados->push($agendamento->load(['paciente', 'sala', 'profissionais']));
            }

            return $criados;
        });
    }

    /**
     * RN01 - Valida conflito de sala e paciente, com bypass para co-atendimentos compartilhados na mesma sala.
     */
    public function validarConflitoDeGrade(
        string $dataAtendimento,
        string $horaInicio,
        string $horaFim,
        int $salaId,
        int $pacienteId,
        string $tipoAtendimento = 'INDIVIDUAL',
        ?int $ignorarAgendamentoId = null
    ): void {
        $compartilhado = $tipoAtendimento === 'COMPARTILHADO';

        $queryBase = Agendamento::query()
            ->whereDate('data_atendimento', $dataAtendimento)
            ->where('status', '!=', 'CANCELADO')
            ->where('hora_inicio', '<', $horaFim)
            ->where('hora_fim', '>', $horaInicio)
            ->when($ignorarAgendamentoId !== null, fn ($q) => $q->where('id', '!=', $ignorarAgendamentoId));

        if (! $compartilhado) {
            if ((clone $queryBase)->where('sala_id', $salaId)->exists()) {
                throw ValidationException::withMessages([
                    'sala_id' => 'Conflito de agenda: a sala selecionada já está ocupada neste horário.',
                ]);
            }

            if ((clone $queryBase)->where('paciente_id', $pacienteId)->exists()) {
                throw ValidationException::withMessages([
                    'paciente_id' => 'Conflito de agenda: o paciente já possui agendamento simultâneo neste horário (RN01).',
                ]);
            }

            return;
        }

        if ((clone $queryBase)->where('paciente_id', $pacienteId)->where('sala_id', '!=', $salaId)->exists()) {
            throw ValidationException::withMessages([
                'paciente_id' => 'Conflito de agenda: o paciente já está alocado em outra sala neste mesmo horário.',
            ]);
        }
    }

    /**
     * Otimização: Verifica em lote o alerta de absenteísmo para múltiplos pacientes (evita N+1).
     *
     * @param  array<int, int>  $pacientesIds
     * @return array<int, array{alerta: bool, faltas_mes: int, faltas_consecutivas: int, motivo: ?string}>
     */
    public function verificarAlertasAbsenteismoEmLote(array $pacientesIds, ?CarbonImmutable $referencia = null): array
    {
        $pacientesIds = array_values(array_filter(array_unique($pacientesIds)));
        if (empty($pacientesIds)) {
            return [];
        }

        $ref = $referencia ?? CarbonImmutable::now();
        $inicioMes = $ref->startOfMonth()->toDateString();
        $fimMes = $ref->endOfMonth()->toDateString();
        $statusesFalta = ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA'];

        $faltasMesAgrupadas = Agendamento::query()
            ->whereIn('paciente_id', $pacientesIds)
            ->whereBetween('data_atendimento', [$inicioMes, $fimMes])
            ->whereIn('status', $statusesFalta)
            ->selectRaw('paciente_id, COUNT(*) as total')
            ->groupBy('paciente_id')
            ->pluck('total', 'paciente_id')
            ->all();

        $ultimosAgendamentos = Agendamento::query()
            ->whereIn('paciente_id', $pacientesIds)
            ->whereIn('status', ['PRESENTE', 'ATENDIDO', 'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA'])
            ->orderByDesc('data_atendimento')
            ->orderByDesc('hora_inicio')
            ->select(['id', 'paciente_id', 'status'])
            ->get()
            ->groupBy('paciente_id');

        $resultado = [];
        foreach ($pacientesIds as $id) {
            $faltasMes = (int) ($faltasMesAgrupadas[$id] ?? 0);
            $historico = $ultimosAgendamentos->get($id, collect());

            $consecutivas = 0;
            foreach ($historico as $ag) {
                if (in_array($ag->status, $statusesFalta, true)) {
                    $consecutivas++;
                } else {
                    break;
                }
            }

            $alerta = $consecutivas >= 3 || $faltasMes >= 5;
            $motivo = null;
            if ($consecutivas >= 3) {
                $motivo = "{$consecutivas} faltas consecutivas";
            } elseif ($faltasMes >= 5) {
                $motivo = "{$faltasMes} faltas acumuladas no mês";
            }

            $resultado[$id] = [
                'alerta' => $alerta,
                'faltas_mes' => $faltasMes,
                'faltas_consecutivas' => $consecutivas,
                'motivo' => $motivo,
            ];
        }

        return $resultado;
    }

    /**
     * RF2.5 - Verifica se o paciente atingiu 3 faltas consecutivas ou 5 faltas alternadas no mês vigente.
     *
     * @return array{alerta: bool, faltas_mes: int, faltas_consecutivas: int, motivo: ?string}
     */
    public function verificarAlertaAbsenteismo(int $pacienteId, ?CarbonImmutable $referencia = null): array
    {
        $ref = $referencia ?? CarbonImmutable::now();
        $inicioMes = $ref->startOfMonth()->toDateString();
        $fimMes = $ref->endOfMonth()->toDateString();

        $statusesFalta = ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA'];

        $faltasMes = Agendamento::query()
            ->where('paciente_id', $pacienteId)
            ->whereBetween('data_atendimento', [$inicioMes, $fimMes])
            ->whereIn('status', $statusesFalta)
            ->count();

        $ultimosAgendamentos = Agendamento::query()
            ->where('paciente_id', $pacienteId)
            ->whereIn('status', ['PRESENTE', 'ATENDIDO', 'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA'])
            ->orderByDesc('data_atendimento')
            ->orderByDesc('hora_inicio')
            ->limit(10)
            ->pluck('status');

        $consecutivas = 0;
        foreach ($ultimosAgendamentos as $status) {
            if (in_array($status, $statusesFalta, true)) {
                $consecutivas++;
            } else {
                break;
            }
        }

        $alerta = $consecutivas >= 3 || $faltasMes >= 5;
        $motivo = null;
        if ($consecutivas >= 3) {
            $motivo = "{$consecutivas} faltas consecutivas";
        } elseif ($faltasMes >= 5) {
            $motivo = "{$faltasMes} faltas acumuladas no mês";
        }

        return [
            'alerta' => $alerta,
            'faltas_mes' => $faltasMes,
            'faltas_consecutivas' => $consecutivas,
            'motivo' => $motivo,
        ];
    }
}
