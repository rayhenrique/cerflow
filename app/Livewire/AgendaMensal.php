<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\Profissional;
use App\Services\AgendamentoService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Mapa Mensal da Agenda — CER Digital')]
class AgendaMensal extends Component
{
    #[Url]
    public int $mes = 0;

    #[Url]
    public int $ano = 0;

    #[Url]
    public string $filtroEspecialidade = '';

    #[Url]
    public string $filtroProfissionalId = '';

    #[Url]
    public string $filtroTurno = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Agendamento::class);
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']),
            403
        );

        $hoje = CarbonImmutable::now();
        if ($this->mes < 1 || $this->mes > 12) {
            $this->mes = (int) $hoje->format('n');
        }
        if ($this->ano < 2000 || $this->ano > 2100) {
            $this->ano = (int) $hoje->format('Y');
        }
    }

    public function mesAnterior(): void
    {
        $novaData = CarbonImmutable::create($this->ano, $this->mes, 1)->subMonth();
        $this->mes = (int) $novaData->format('n');
        $this->ano = (int) $novaData->format('Y');
    }

    public function proximoMes(): void
    {
        $novaData = CarbonImmutable::create($this->ano, $this->mes, 1)->addMonth();
        $this->mes = (int) $novaData->format('n');
        $this->ano = (int) $novaData->format('Y');
    }

    public function irParaMesAtual(): void
    {
        $hoje = CarbonImmutable::now();
        $this->mes = (int) $hoje->format('n');
        $this->ano = (int) $hoje->format('Y');
    }

    public function limparFiltros(): void
    {
        $this->filtroEspecialidade = '';
        $this->filtroProfissionalId = '';
        $this->filtroTurno = '';
    }

    public function render(AgendamentoService $agendamentoService): View
    {
        Gate::authorize('viewAny', Agendamento::class);

        $inicioMes = CarbonImmutable::create($this->ano, $this->mes, 1)->startOfMonth();
        $fimMes = $inicioMes->endOfMonth();

        $inicioCalendario = $inicioMes->startOfWeek(CarbonImmutable::MONDAY);
        $fimCalendario = $fimMes->endOfWeek(CarbonImmutable::SUNDAY);

        $query = Agendamento::query()
            ->whereBetween('data_atendimento', [$inicioCalendario->toDateString(), $fimCalendario->toDateString()]);

        if ($this->filtroEspecialidade !== '') {
            $query->whereHas('profissionais', function ($q): void {
                $q->where('especialidade', $this->filtroEspecialidade);
            });
        }

        if ($this->filtroProfissionalId !== '') {
            $profId = (int) $this->filtroProfissionalId;
            $query->whereHas('profissionais', function ($q) use ($profId): void {
                $q->where('profissionais.id', $profId);
            });
        }

        if ($this->filtroTurno === 'MANHA') {
            $query->whereTime('hora_inicio', '<', '12:00:00');
        } elseif ($this->filtroTurno === 'TARDE') {
            $query->whereTime('hora_inicio', '>=', '12:00:00');
        }

        // Seleciona exclusivamente os campos necessários para contagem e métricas de ocupação
        // Sem expor dados clínicos, nomes ou CNS de pacientes (conformidade estrita com LGPD)
        $agendamentos = $query->get([
            'id',
            'data_atendimento',
            'hora_inicio',
            'hora_fim',
            'status',
            'tipo_atendimento',
            'sala_id',
        ]);

        $metricasMes = $agendamentoService->calcularMetricasMes($agendamentos, $inicioMes, $fimMes);
        $diasCalendario = $agendamentoService->montarDiasCalendario($agendamentos, $inicioCalendario, $fimCalendario, $this->mes);

        $especialidadesDisponiveis = Profissional::query()
            ->ativos()
            ->whereNotNull('especialidade')
            ->where('especialidade', '!=', '')
            ->distinct()
            ->orderBy('especialidade')
            ->pluck('especialidade');

        $profissionaisDisponiveis = Profissional::query()
            ->ativos()
            ->orderBy('nome')
            ->get(['id', 'nome', 'especialidade']);

        return view('livewire.agenda-mensal', [
            'mesNome' => $this->nomeMesPtBr($this->mes),
            'inicioMes' => $inicioMes,
            'fimMes' => $fimMes,
            'metricasMes' => $metricasMes,
            'diasCalendario' => $diasCalendario,
            'especialidadesDisponiveis' => $especialidadesDisponiveis,
            'profissionaisDisponiveis' => $profissionaisDisponiveis,
        ]);
    }

    private function nomeMesPtBr(int $mes): string
    {
        $nomes = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $nomes[$mes] ?? 'Mês '.$mes;
    }
}
