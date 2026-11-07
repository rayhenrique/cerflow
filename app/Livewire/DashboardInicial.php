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
#[Title('Dashboard Operacional — CER Flow')]
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
        $fimMes = $inicioMes->endOfMonth();

        // 1. Indicadores Gerais de Pacientes e Equipe
        $totalPacientesAtivos = Paciente::query()->ativos()->count();
        $novosPacientesMes = Paciente::query()
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->count();

        $totalProfissionaisAtivos = Profissional::query()->ativos()->count();
        $totalSalasAtivas = Sala::query()->ativas()->count();
        $totalProcedimentosAtivos = ProcedimentoSigtap::query()->ativos()->count();

        // 2. Distribuição por Modalidades de Reabilitação (Física, Intelectual, Auditiva, Visual)
        $pacientesModalidades = Paciente::query()
            ->ativos()
            ->get(['id', 'modalidades_reabilitacao']);

        $contagemModalidades = [
            'FISICA' => 0,
            'INTELECTUAL' => 0,
            'AUDITIVA' => 0,
            'VISUAL' => 0,
        ];

        foreach ($pacientesModalidades as $pac) {
            $mods = is_array($pac->modalidades_reabilitacao) ? $pac->modalidades_reabilitacao : [];
            foreach ($mods as $m) {
                if (isset($contagemModalidades[$m])) {
                    $contagemModalidades[$m]++;
                }
            }
        }

        // 3. Indicadores de Hoje (Agenda e Check-in)
        $agendamentosHoje = Agendamento::query()
            ->with(['paciente', 'sala', 'profissionais'])
            ->whereDate('data_atendimento', $hoje->toDateString())
            ->where('status', '!=', 'CANCELADO')
            ->orderBy('hora_inicio')
            ->get();

        $hojeTotal = $agendamentosHoje->count();
        $hojePresentesAtendidos = $agendamentosHoje->whereIn('status', ['PRESENTE', 'ATENDIDO'])->count();
        $hojeAguardando = $agendamentosHoje->where('status', 'AGENDADO')->count();
        $hojeFaltas = $agendamentosHoje->filter(fn (Agendamento $a) => str_starts_with((string) $a->status, 'FALTA'))->count();

        // 4. Indicadores Mensais de Assiduidade e Modalidade de Encontro
        $agendamentosMes = Agendamento::query()
            ->whereBetween('data_atendimento', [$inicioMes->toDateString(), $fimMes->toDateString()])
            ->where('status', '!=', 'CANCELADO')
            ->get(['id', 'status', 'tipo_atendimento', 'sala_id']);

        $mesTotalAgendamentos = $agendamentosMes->count();
        $mesRealizados = $agendamentosMes->whereIn('status', ['PRESENTE', 'ATENDIDO'])->count();
        $mesFaltasJustificadas = $agendamentosMes->where('status', 'FALTA_JUSTIFICADA')->count();
        $mesFaltasInjustificadas = $agendamentosMes->where('status', 'FALTA_INJUSTIFICADA')->count();
        $mesCompartilhados = $agendamentosMes->where('tipo_atendimento', 'COMPARTILHADO')->count();

        $taxaComparecimento = $mesTotalAgendamentos > 0
            ? (int) round(($mesRealizados / $mesTotalAgendamentos) * 100)
            : 0;

        // 5. Evoluções Clínicas Assinadas no Mês e Produções BPA-i da Competência
        $evolucoesMes = EvolucaoClinica::query()
            ->whereBetween('assinado_em', [$inicioMes, $fimMes])
            ->count();

        $producoesComp = ProducaoBpai::query()
            ->where('competencia', $competenciaLimpa)
            ->get(['id', 'status_exportacao', 'quantidade']);

        $bpaiTotal = (int) $producoesComp->sum('quantidade');
        $bpaiAptos = $producoesComp->whereIn('status_exportacao', ['PENDENTE', 'VALIDADO', 'EXPORTADO'])->count();
        $bpaiExportados = $producoesComp->where('status_exportacao', 'EXPORTADO')->count();
        $bpaiInconsistentes = $producoesComp->where('status_exportacao', 'INCONSISTENTE')->count();

        // 6. Ocupação por Sala Terapêutica no Mês
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
            'totalPacientesAtivos' => $totalPacientesAtivos,
            'novosPacientesMes' => $novosPacientesMes,
            'totalProfissionaisAtivos' => $totalProfissionaisAtivos,
            'totalSalasAtivas' => $totalSalasAtivas,
            'totalProcedimentosAtivos' => $totalProcedimentosAtivos,
            'contagemModalidades' => $contagemModalidades,
            'agendamentosHoje' => $agendamentosHoje->take(8),
            'hojeTotal' => $hojeTotal,
            'hojePresentesAtendidos' => $hojePresentesAtendidos,
            'hojeAguardando' => $hojeAguardando,
            'hojeFaltas' => $hojeFaltas,
            'mesTotalAgendamentos' => $mesTotalAgendamentos,
            'mesRealizados' => $mesRealizados,
            'mesFaltasJustificadas' => $mesFaltasJustificadas,
            'mesFaltasInjustificadas' => $mesFaltasInjustificadas,
            'mesCompartilhados' => $mesCompartilhados,
            'taxaComparecimento' => $taxaComparecimento,
            'evolucoesMes' => $evolucoesMes,
            'bpaiTotal' => $bpaiTotal,
            'bpaiAptos' => $bpaiAptos,
            'bpaiExportados' => $bpaiExportados,
            'bpaiInconsistentes' => $bpaiInconsistentes,
            'salasOcupacao' => $salasOcupacao,
        ]);
    }
}
