<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Services\AgendamentoService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Grade Semanal — CER Flow')]
class GradeSemanal extends Component
{
    public string $diaSemana = 'SEGUNDA';

    public string $turno = 'MANHA';

    public string $dataReferencia = '';

    public bool $modalAberto = false;

    public ?int $paciente_id = null;

    public ?int $sala_id = null;

    public string $hora_inicio = '08:00';

    public string $hora_fim = '08:30';

    public string $tipo_atendimento = 'INDIVIDUAL';

    /** @var array<int, int|string> */
    public array $profissionaisSelecionados = [];

    public bool $recorrente = true;

    public int $semanasRecorrencia = 4;

    public string $observacao = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Agendamento::class);

        $hoje = CarbonImmutable::now();
        $mapa = [
            1 => 'SEGUNDA',
            2 => 'TERCA',
            3 => 'QUARTA',
            4 => 'QUINTA',
            5 => 'SEXTA',
        ];
        $this->diaSemana = $mapa[$hoje->dayOfWeekIso] ?? 'SEGUNDA';
        $this->dataReferencia = $this->calcularDataDoDiaDaSemana($this->diaSemana);
    }

    public function updatedDiaSemana(string $valor): void
    {
        $this->dataReferencia = $this->calcularDataDoDiaDaSemana($valor);
    }

    public function abrirModalSlot(?int $salaId = null, string $horaInicio = '08:00'): void
    {
        Gate::authorize('create', Agendamento::class);

        $this->resetValidation();
        $this->sala_id = $salaId;
        $this->hora_inicio = $horaInicio;
        $this->hora_fim = CarbonImmutable::createFromFormat('H:i', $horaInicio)->addMinutes(30)->format('H:i');
        $this->modalAberto = true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'sala_id' => ['required', 'integer', 'exists:salas,id'],
            'dataReferencia' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'diaSemana' => ['required', 'in:SEGUNDA,TERCA,QUARTA,QUINTA,SEXTA'],
            'tipo_atendimento' => ['required', 'in:INDIVIDUAL,COMPARTILHADO,TRABALHO_INTERNO'],
            'profissionaisSelecionados' => ['required', 'array', 'min:1'],
            'profissionaisSelecionados.*' => ['integer', 'exists:profissionais,id'],
            'recorrente' => ['boolean'],
            'semanasRecorrencia' => ['integer', 'min:1', 'max:12'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function salvarAgendamento(AgendamentoService $agendamentoService): void
    {
        Gate::authorize('create', Agendamento::class);

        $this->validate();

        $agendamentoService->criarGrade(
            dados: [
                'paciente_id' => $this->paciente_id,
                'sala_id' => $this->sala_id,
                'data_atendimento' => $this->dataReferencia,
                'hora_inicio' => $this->hora_inicio,
                'hora_fim' => $this->hora_fim,
                'dia_semana' => $this->diaSemana,
                'tipo_atendimento' => count($this->profissionaisSelecionados) > 1 && $this->tipo_atendimento === 'INDIVIDUAL'
                    ? 'COMPARTILHADO'
                    : $this->tipo_atendimento,
                'recorrente' => $this->recorrente,
                'observacao' => $this->observacao !== '' ? $this->observacao : null,
            ],
            profissionaisIds: $this->profissionaisSelecionados,
            semanasRecorrencia: $this->recorrente ? $this->semanasRecorrencia : 1,
        );

        $this->modalAberto = false;
        $this->reset(['paciente_id', 'profissionaisSelecionados', 'observacao']);
        $mensagem = 'Agendamento registrado com sucesso na grade.';
        session()->flash('sucesso', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Horário agendado', mensagem: $mensagem);
    }

    /**
     * @return array<int, string>
     */
    public function obterSlotsHorario(): array
    {
        $manha = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
        $tarde = ['13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];

        return match ($this->turno) {
            'MANHA' => $manha,
            'TARDE' => $tarde,
            default => array_merge($manha, $tarde),
        };
    }

    public function render(): View
    {
        $salas = Sala::query()->ativas()->orderBy('nome')->get();
        $slots = $this->obterSlotsHorario();

        $agendamentos = Agendamento::query()
            ->with(['paciente', 'profissionais', 'sala'])
            ->whereDate('data_atendimento', $this->dataReferencia)
            ->where('dia_semana', $this->diaSemana)
            ->where('status', '!=', 'CANCELADO')
            ->get();

        $gradeIndexada = [];
        foreach ($agendamentos as $agendamento) {
            $horaSlot = substr((string) $agendamento->hora_inicio, 0, 5);
            $gradeIndexada[$agendamento->sala_id][$horaSlot][] = $agendamento;
        }

        return view('livewire.grade-semanal', [
            'salas' => $salas,
            'slots' => $slots,
            'gradeIndexada' => $gradeIndexada,
            'pacientes' => Paciente::query()->ativos()->orderBy('nome')->get(['id', 'nome', 'cns']),
            'profissionaisDisponiveis' => Profissional::query()->ativos()->orderBy('nome')->get(['id', 'nome', 'especialidade', 'cbo_padrao']),
        ]);
    }

    private function calcularDataDoDiaDaSemana(string $diaSemana): string
    {
        $mapaIso = [
            'SEGUNDA' => 1,
            'TERCA' => 2,
            'QUARTA' => 3,
            'QUINTA' => 4,
            'SEXTA' => 5,
        ];
        $isoTarget = $mapaIso[$diaSemana] ?? 1;

        return CarbonImmutable::now()->startOfWeek()->addDays($isoTarget - 1)->toDateString();
    }
}
