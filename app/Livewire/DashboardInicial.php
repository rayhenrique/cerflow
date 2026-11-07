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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            ->with([
                'paciente:id,nome',
                'sala:id,nome',
                'profissionais:id,nome',
            ])
            ->whereDate('data_atendimento', $hoje->toDateString())
            ->where('status', '!=', 'CANCELADO')
            ->orderBy('hora_inicio')
            ->get();

        $metricasMes = $this->calcularMetricasMensais($inicioMes, $fimMes);
        $metricasBpai = $this->calcularMetricasBpai($competenciaLimpa);

        Cache::forget('dash_salas_ocupacao_'.$inicioMes->format('Ym'));

        $salasOcupacao = Cache::remember('dash_salas_ocupacao_v2_'.$inicioMes->format('Ym'), 60, function () use ($inicioMes, $fimMes): array {
            return Sala::query()
                ->ativas()
                ->withCount([
                    'agendamentos as atendimentos_mes_count' => function ($q) use ($inicioMes, $fimMes): void {
                        $q->whereBetween('data_atendimento', [$inicioMes->toDateString(), $fimMes->toDateString()])
                            ->where('status', '!=', 'CANCELADO');
                    },
                ])
                ->orderByDesc('atendimentos_mes_count')
                ->limit(6)
                ->get()
                ->map(fn ($sala): array => [
                    'id' => $sala->id,
                    'nome' => (string) $sala->nome,
                    'tipo' => (string) $sala->tipo,
                    'atendimentos_mes_count' => (int) $sala->atendimentos_mes_count,
                ])
                ->all();
        });

        return view('livewire.dashboard-inicial', [
            'competenciaFormatada' => substr($competenciaLimpa, 4, 2).'/'.substr($competenciaLimpa, 0, 4),
            'totalPacientesAtivos' => Cache::remember('dash_total_pacientes_ativos', 60, fn () => Paciente::query()->ativos()->count()),
            'novosPacientesMes' => Cache::remember('dash_novos_pacientes_mes_'.$inicioMes->format('Ym'), 60, fn () => Paciente::query()->whereBetween('created_at', [$inicioMes, $fimMes])->count()),
            'totalProfissionaisAtivos' => Cache::remember('dash_total_profissionais_ativos', 60, fn () => Profissional::query()->ativos()->count()),
            'totalSalasAtivas' => Cache::remember('dash_total_salas_ativas', 60, fn () => Sala::query()->ativas()->count()),
            'totalProcedimentosAtivos' => Cache::remember('dash_total_procedimentos_ativos', 60, fn () => ProcedimentoSigtap::query()->ativos()->count()),
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
            'evolucoesMes' => Cache::remember('dash_evolucoes_mes_'.$inicioMes->format('Ym'), 60, fn () => EvolucaoClinica::query()->whereBetween('assinado_em', [$inicioMes, $fimMes])->count()),
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
        $driver = DB::connection()->getDriverName();
        $castType = match ($driver) {
            'mysql' => 'CHAR',
            default => 'TEXT',
        };

        return Cache::remember('dashboard_contagem_modalidades', 60, function () use ($castType): array {
            $metricas = DB::table('pacientes')
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->selectRaw("
                    SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%FISICA%' THEN 1 ELSE 0 END) as fisica,
                    SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%INTELECTUAL%' THEN 1 ELSE 0 END) as intelectual,
                    SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%AUDITIVA%' THEN 1 ELSE 0 END) as auditiva,
                    SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%VISUAL%' THEN 1 ELSE 0 END) as visual
                ")
                ->first();

            return [
                'FISICA' => (int) ($metricas->fisica ?? 0),
                'INTELECTUAL' => (int) ($metricas->intelectual ?? 0),
                'AUDITIVA' => (int) ($metricas->auditiva ?? 0),
                'VISUAL' => (int) ($metricas->visual ?? 0),
            ];
        });
    }

    /**
     * @return array{total: int, realizados: int, faltas_justificadas: int, faltas_injustificadas: int, compartilhados: int, taxa_comparecimento: int}
     */
    private function calcularMetricasMensais(CarbonImmutable $inicioMes, CarbonImmutable $fimMes): array
    {
        $cacheKey = 'dashboard_metricas_mensais_'.$inicioMes->format('Ym');

        return Cache::remember($cacheKey, 60, function () use ($inicioMes, $fimMes): array {
            $metricas = Agendamento::query()
                ->whereBetween('data_atendimento', [$inicioMes->toDateString(), $fimMes->toDateString()])
                ->where('status', '!=', 'CANCELADO')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('PRESENTE', 'ATENDIDO') THEN 1 ELSE 0 END) as realizados,
                    SUM(CASE WHEN status = 'FALTA_JUSTIFICADA' THEN 1 ELSE 0 END) as faltas_justificadas,
                    SUM(CASE WHEN status = 'FALTA_INJUSTIFICADA' THEN 1 ELSE 0 END) as faltas_injustificadas,
                    SUM(CASE WHEN tipo_atendimento = 'COMPARTILHADO' THEN 1 ELSE 0 END) as compartilhados
                ")
                ->first();

            $total = (int) ($metricas->total ?? 0);
            $realizados = (int) ($metricas->realizados ?? 0);

            return [
                'total' => $total,
                'realizados' => $realizados,
                'faltas_justificadas' => (int) ($metricas->faltas_justificadas ?? 0),
                'faltas_injustificadas' => (int) ($metricas->faltas_injustificadas ?? 0),
                'compartilhados' => (int) ($metricas->compartilhados ?? 0),
                'taxa_comparecimento' => $total > 0 ? (int) round(($realizados / $total) * 100) : 0,
            ];
        });
    }

    /**
     * @return array{total: int, aptos: int, exportados: int, inconsistentes: int}
     */
    private function calcularMetricasBpai(string $competenciaLimpa): array
    {
        $cacheKey = 'dashboard_metricas_bpai_'.$competenciaLimpa;

        return Cache::remember($cacheKey, 60, function () use ($competenciaLimpa): array {
            $metricas = ProducaoBpai::query()
                ->where('competencia', $competenciaLimpa)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status_exportacao IN ('PENDENTE', 'VALIDADO', 'EXPORTADO') THEN 1 ELSE 0 END) as aptos,
                    SUM(CASE WHEN status_exportacao = 'EXPORTADO' THEN 1 ELSE 0 END) as exportados,
                    SUM(CASE WHEN status_exportacao = 'INCONSISTENTE' THEN 1 ELSE 0 END) as inconsistentes
                ")
                ->first();

            return [
                'total' => (int) ($metricas->total ?? 0),
                'aptos' => (int) ($metricas->aptos ?? 0),
                'exportados' => (int) ($metricas->exportados ?? 0),
                'inconsistentes' => (int) ($metricas->inconsistentes ?? 0),
            ];
        });
    }
}
