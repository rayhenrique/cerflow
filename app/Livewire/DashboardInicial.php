<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\EvolucaoClinica;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\Sala;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Painel Inicial Operacional — CER Digital')]
class DashboardInicial extends Component
{
    public string $competencia = '';

    public function mount(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']),
            403
        );

        $this->competencia = CarbonImmutable::now()->format('Ym');
    }

    public function render(): View
    {
        $hoje = CarbonImmutable::now();
        [$competenciaLimpa, $inicioMes, $fimMes] = $this->resolverPeriodoCompetencia($hoje);

        $agendamentosHoje = Agendamento::query()
            ->with(['paciente', 'sala', 'profissionais'])
            ->whereDate('data_atendimento', $hoje->toDateString())
            ->where('status', '!=', 'CANCELADO')
            ->orderBy('hora_inicio')
            ->get();

        $metricasMes = $this->calcularMetricasMensais($inicioMes, $fimMes);
        $metricasBpai = $this->calcularMetricasBpai($competenciaLimpa);

        $salasOcupacao = Sala::query()
            ->ativas()
            ->withCount([
                'agendamentos as atendimentos_mes_count' => function ($q) use ($inicioMes, $fimMes): void {
                    $q->whereBetween('data_atendimento', [$inicioMes->toDateString(), $fimMes->toDateString()])
                        ->where('status', '!=', 'CANCELADO');
                },
            ])
            ->orderByDesc('atendimentos_mes_count')
            ->limit(6)
            ->get();

        return view('livewire.dashboard-inicial', [
            'competenciaFormatada' => substr($competenciaLimpa, 4, 2).'/'.substr($competenciaLimpa, 0, 4),
            'totalPacientesAtivos' => Paciente::query()->ativos()->count(),
            'novosPacientesMes' => Paciente::query()->whereBetween('created_at', [$inicioMes, $fimMes])->count(),
            'totalProfissionaisAtivos' => Profissional::query()->ativos()->count(),
            'totalSalasAtivas' => Sala::query()->ativas()->count(),
            'totalProcedimentosAtivos' => ProcedimentoSigtap::query()->ativos()->count(),
            'contagemModalidades' => $this->contarModalidadesPacientes(),
            'agendamentosHoje' => $agendamentosHoje->take(8),
            'hojeTotal' => $agendamentosHoje->count(),
            'hojePresentesAtendidos' => $agendamentosHoje->whereIn('status', ['PRESENTE', 'ATENDIDO'])->count(),
            'hojeAguardando' => $agendamentosHoje->where('status', 'AGENDADO')->count(),
            'hojeFaltas' => $agendamentosHoje->whereIn('status', ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA'])->count(),
            'mesTotalAgendamentos' => $metricasMes['total'],
            'mesRealizados' => $metricasMes['realizados'],
            'mesFaltasJustificadas' => $metricasMes['faltas_justificadas'],
            'mesFaltasInjustificadas' => $metricasMes['faltas_injustificadas'],
            'mesCompartilhados' => $metricasMes['compartilhados'],
            'taxaComparecimento' => $metricasMes['taxa_comparecimento'],
            'evolucoesMes' => EvolucaoClinica::query()->whereBetween('assinado_em', [$inicioMes, $fimMes])->count(),
            'bpaiTotal' => $metricasBpai['total'],
            'bpaiAptos' => $metricasBpai['aptos'],
            'bpaiExportados' => $metricasBpai['exportados'],
            'bpaiInconsistentes' => $metricasBpai['inconsistentes'],
            'salasOcupacao' => $salasOcupacao,
        ]);
    }

    /**
     * @return array{0: string, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    private function resolverPeriodoCompetencia(CarbonImmutable $hoje): array
    {
        $competenciaLimpa = preg_replace('/\D/', '', $this->competencia) ?? '';
        if (strlen($competenciaLimpa) !== 6) {
            $competenciaLimpa = $hoje->format('Ym');
        }

        $anoComp = (int) substr($competenciaLimpa, 0, 4);
        $mesComp = (int) substr($competenciaLimpa, 4, 2);
        if ($mesComp < 1 || $mesComp > 12) {
            $anoComp = (int) $hoje->format('Y');
            $mesComp = (int) $hoje->format('m');
            $competenciaLimpa = $hoje->format('Ym');
        }

        $inicioMes = CarbonImmutable::create($anoComp, $mesComp, 1)->startOfMonth();

        return [$competenciaLimpa, $inicioMes, $inicioMes->endOfMonth()];
    }

    /**
     * @return array<string, int>
     */
    private function contarModalidadesPacientes(): array
    {
        $contagem = [
            'FISICA' => 0,
            'INTELECTUAL' => 0,
            'AUDITIVA' => 0,
            'VISUAL' => 0,
        ];

        $pacientes = Paciente::query()->ativos()->get(['id', 'modalidades_reabilitacao']);
        foreach ($pacientes as $pac) {
            $mods = is_array($pac->modalidades_reabilitacao) ? $pac->modalidades_reabilitacao : [];
            foreach ($mods as $m) {
                if (isset($contagem[$m])) {
                    $contagem[$m]++;
                }
            }
        }

        return $contagem;
    }

    /**
     * @return array{total: int, realizados: int, faltas_justificadas: int, faltas_injustificadas: int, compartilhados: int, taxa_comparecimento: int}
     */
    private function calcularMetricasMensais(CarbonImmutable $inicioMes, CarbonImmutable $fimMes): array
    {
        $agendamentosMes = Agendamento::query()
            ->whereBetween('data_atendimento', [$inicioMes->toDateString(), $fimMes->toDateString()])
            ->where('status', '!=', 'CANCELADO')
            ->get(['id', 'status', 'tipo_atendimento']);

        $total = $agendamentosMes->count();
        $realizados = $agendamentosMes->whereIn('status', ['PRESENTE', 'ATENDIDO'])->count();

        return [
            'total' => $total,
            'realizados' => $realizados,
            'faltas_justificadas' => $agendamentosMes->where('status', 'FALTA_JUSTIFICADA')->count(),
            'faltas_injustificadas' => $agendamentosMes->where('status', 'FALTA_INJUSTIFICADA')->count(),
            'compartilhados' => $agendamentosMes->where('tipo_atendimento', 'COMPARTILHADO')->count(),
            'taxa_comparecimento' => $total > 0 ? (int) round(($realizados / $total) * 100) : 0,
        ];
    }

    /**
     * @return array{total: int, aptos: int, exportados: int, inconsistentes: int}
     */
    private function calcularMetricasBpai(string $competenciaLimpa): array
    {
        $producoesComp = ProducaoBpai::query()
            ->where('competencia', $competenciaLimpa)
            ->get(['id', 'status_exportacao']);

        return [
            'total' => $producoesComp->count(),
            'aptos' => $producoesComp->whereIn('status_exportacao', ['PENDENTE', 'VALIDADO', 'EXPORTADO'])->count(),
            'exportados' => $producoesComp->where('status_exportacao', 'EXPORTADO')->count(),
            'inconsistentes' => $producoesComp->where('status_exportacao', 'INCONSISTENTE')->count(),
        ];
    }
}
