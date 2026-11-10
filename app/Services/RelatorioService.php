<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\EvolucaoClinica;
use App\Models\LogAuditoria;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioService
{
    public const CNES_PADRAO = '2000001';

    /**
     * Resolve o período de datas com validação, limites de segurança e predefinições.
     *
     * @return array{inicio: CarbonImmutable, fim: CarbonImmutable, descricao: string, periodo: string}
     */
    public function resolverPeriodo(
        string $periodo = 'mes_atual',
        ?string $dataInicio = null,
        ?string $dataFim = null
    ): array {
        $hoje = CarbonImmutable::now();

        switch ($periodo) {
            case 'hoje':
                $inicio = $hoje->startOfDay();
                $fim = $hoje->endOfDay();
                $descricao = 'Hoje ('.$hoje->format('d/m/Y').')';
                break;

            case 'ultimos_7_dias':
                $inicio = $hoje->subDays(6)->startOfDay();
                $fim = $hoje->endOfDay();
                $descricao = 'Últimos 7 dias ('.$inicio->format('d/m/Y').' a '.$fim->format('d/m/Y').')';
                break;

            case 'mes_anterior':
                $mesPassado = $hoje->subMonthNoOverflow();
                $inicio = $mesPassado->startOfMonth();
                $fim = $mesPassado->endOfMonth();
                $descricao = 'Mês anterior ('.$inicio->format('d/m/Y').' a '.$fim->format('d/m/Y').')';
                break;

            case 'ano_atual':
                $inicio = $hoje->startOfYear();
                $fim = $hoje->endOfYear();
                $descricao = 'Ano atual ('.$inicio->format('d/m/Y').' a '.$fim->format('d/m/Y').')';
                break;

            case 'personalizado':
                try {
                    $inicio = ! empty($dataInicio) ? CarbonImmutable::parse($dataInicio)->startOfDay() : $hoje->startOfMonth();
                    $fim = ! empty($dataFim) ? CarbonImmutable::parse($dataFim)->endOfDay() : $hoje->endOfMonth();
                } catch (\Throwable) {
                    $inicio = $hoje->startOfMonth();
                    $fim = $hoje->endOfMonth();
                }

                // Inverte se início for maior que fim
                if ($inicio->greaterThan($fim)) {
                    $temp = $inicio;
                    $inicio = $fim->startOfDay();
                    $fim = $temp->endOfDay();
                }

                // Limite de segurança de 365 dias para proteger o MySQL na VPS
                if ($inicio->diffInDays($fim) > 366) {
                    $fim = $inicio->addDays(365)->endOfDay();
                }

                $descricao = 'Período: '.$inicio->format('d/m/Y').' a '.$fim->format('d/m/Y');
                break;

            case 'mes_atual':
            default:
                $periodo = 'mes_atual';
                $inicio = $hoje->startOfMonth();
                $fim = $hoje->endOfMonth();
                $descricao = 'Mês atual ('.$inicio->format('d/m/Y').' a '.$fim->format('d/m/Y').')';
                break;
        }

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'descricao' => $descricao,
            'periodo' => $periodo,
        ];
    }

    /**
     * Gera os indicadores consolidados da Visão Geral Gerencial.
     *
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable, profissionalId?: ?int, especialidade?: ?string, salaId?: ?int, modalidade?: ?string}  $filtros
     * @return array<string, mixed>
     */
    public function obterVisaoGeral(array $filtros): array
    {
        $inicio = $filtros['inicio']->startOfDay()->toDateTimeString();
        $fim = $filtros['fim']->endOfDay()->toDateTimeString();

        $query = Agendamento::query()
            ->whereBetween('data_atendimento', [$inicio, $fim]);

        $this->aplicarFiltrosAgendamento($query, $filtros);

        $totais = (clone $query)->selectRaw("
            COUNT(*) as total_agendamentos,
            COUNT(DISTINCT paciente_id) as pacientes_distintos,
            SUM(CASE WHEN status = 'PRESENTE' THEN 1 ELSE 0 END) as presencas,
            SUM(CASE WHEN status = 'ATENDIDO' THEN 1 ELSE 0 END) as atendidos,
            SUM(CASE WHEN status = 'AGENDADO' THEN 1 ELSE 0 END) as aguardando,
            SUM(CASE WHEN status = 'FALTA_JUSTIFICADA' THEN 1 ELSE 0 END) as faltas_justificadas,
            SUM(CASE WHEN status IN ('FALTA_INJUSTIFICADA', 'FALTOU') THEN 1 ELSE 0 END) as faltas_injustificadas,
            SUM(CASE WHEN status = 'CANCELADO' THEN 1 ELSE 0 END) as cancelamentos,
            SUM(CASE WHEN tipo_atendimento = 'INDIVIDUAL' THEN 1 ELSE 0 END) as individuais,
            SUM(CASE WHEN tipo_atendimento = 'COMPARTILHADO' THEN 1 ELSE 0 END) as compartilhados,
            SUM(CASE WHEN tipo_atendimento = 'TRABALHO_INTERNO' THEN 1 ELSE 0 END) as trabalho_interno
        ")->first();

        $totalAgendamentos = (int) ($totais->total_agendamentos ?? 0);
        $presencas = (int) ($totais->presencas ?? 0);
        $atendidos = (int) ($totais->atendidos ?? 0);
        $aguardando = (int) ($totais->aguardando ?? 0);
        $faltasJustificadas = (int) ($totais->faltas_justificadas ?? 0);
        $faltasInjustificadas = (int) ($totais->faltas_injustificadas ?? 0);
        $cancelamentos = (int) ($totais->cancelamentos ?? 0);
        $pacientesDistintos = (int) ($totais->pacientes_distintos ?? 0);

        $totalFaltas = $faltasJustificadas + $faltasInjustificadas;
        $totalCompareceram = $atendidos + $presencas;
        $totalComDesfecho = $totalCompareceram + $totalFaltas;

        $taxaComparecimento = $totalComDesfecho > 0
            ? round(($totalCompareceram / $totalComDesfecho) * 100, 1)
            : 0.0;

        $taxaAbsenteismo = $totalComDesfecho > 0
            ? round(($totalFaltas / $totalComDesfecho) * 100, 1)
            : 0.0;

        // Evoluções clínicas assinadas no período
        $evolucoesAssinadas = EvolucaoClinica::query()
            ->whereNotNull('assinado_em')
            ->whereBetween('assinado_em', [$filtros['inicio'], $filtros['fim']])
            ->when(! empty($filtros['profissionalId']), fn ($q) => $q->where('profissional_id', $filtros['profissionalId']))
            ->count();

        // Produções BPA-i vinculadas no período
        $producoesRegistradas = ProducaoBpai::query()
            ->whereBetween('data_atendimento', [$inicio, $fim])
            ->when(! empty($filtros['profissionalId']), fn ($q) => $q->where('profissional_id', $filtros['profissionalId']))
            ->count();

        // Distribuição de faltas por dia da semana
        $faltasPorDia = (clone $query)
            ->whereIn('status', ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'FALTOU'])
            ->selectRaw("
                dia_semana,
                SUM(CASE WHEN status = 'FALTA_JUSTIFICADA' THEN 1 ELSE 0 END) as justificadas,
                SUM(CASE WHEN status IN ('FALTA_INJUSTIFICADA', 'FALTOU') THEN 1 ELSE 0 END) as injustificadas,
                COUNT(*) as total
            ")
            ->groupBy('dia_semana')
            ->get()
            ->keyBy('dia_semana');

        $diasOrdenados = ['SEGUNDA' => 'Segunda', 'TERCA' => 'Terça', 'QUARTA' => 'Quarta', 'QUINTA' => 'Quinta', 'SEXTA' => 'Sexta'];
        $dadosFaltasDia = [];
        foreach ($diasOrdenados as $chave => $rotulo) {
            $item = $faltasPorDia->get($chave);
            $dadosFaltasDia[] = [
                'chave' => $chave,
                'rotulo' => $rotulo,
                'total' => (int) ($item->total ?? 0),
                'justificadas' => (int) ($item->justificadas ?? 0),
                'injustificadas' => (int) ($item->injustificadas ?? 0),
            ];
        }

        // Evolução de atendimentos por data (últimos dias do intervalo para gráfico de linha/barras)
        $evolucaoDiaria = (clone $query)
            ->where('status', '!=', 'CANCELADO')
            ->selectRaw("
                data_atendimento,
                COUNT(*) as agendados,
                SUM(CASE WHEN status = 'ATENDIDO' THEN 1 ELSE 0 END) as atendidos,
                SUM(CASE WHEN status = 'PRESENTE' THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN status IN ('FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'FALTOU') THEN 1 ELSE 0 END) as faltas
            ")
            ->groupBy('data_atendimento')
            ->orderBy('data_atendimento')
            ->limit(31)
            ->get()
            ->map(fn ($r) => [
                'data' => CarbonImmutable::parse($r->data_atendimento)->format('d/m'),
                'data_completa' => CarbonImmutable::parse($r->data_atendimento)->format('d/m/Y'),
                'agendados' => (int) $r->agendados,
                'atendidos' => (int) $r->atendidos,
                'presentes' => (int) $r->presentes,
                'faltas' => (int) $r->faltas,
            ])
            ->all();

        return [
            'total_agendamentos' => $totalAgendamentos,
            'pacientes_distintos' => $pacientesDistintos,
            'presencas' => $presencas,
            'atendidos' => $atendidos,
            'aguardando' => $aguardando,
            'faltas_justificadas' => $faltasJustificadas,
            'faltas_injustificadas' => $faltasInjustificadas,
            'total_faltas' => $totalFaltas,
            'cancelamentos' => $cancelamentos,
            'individuais' => (int) ($totais->individuais ?? 0),
            'compartilhados' => (int) ($totais->compartilhados ?? 0),
            'trabalho_interno' => (int) ($totais->trabalho_interno ?? 0),
            'total_compareceram' => $totalCompareceram,
            'total_com_desfecho' => $totalComDesfecho,
            'taxa_comparecimento' => $taxaComparecimento,
            'taxa_absenteismo' => $taxaAbsenteismo,
            'evolucoes_assinadas' => $evolucoesAssinadas,
            'producoes_registradas' => $producoesRegistradas,
            'dados_faltas_dia' => $dadosFaltasDia,
            'evolucao_diaria' => $evolucaoDiaria,
        ];
    }

    /**
     * Relatório operacional de Atendimentos detalhados e paginados.
     *
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable, profissionalId?: ?int, especialidade?: ?string, salaId?: ?int, modalidade?: ?string, status?: ?string}  $filtros
     */
    public function obterAtendimentosPaginados(array $filtros, int $porPagina = 15): LengthAwarePaginator
    {
        $inicio = $filtros['inicio']->startOfDay()->toDateTimeString();
        $fim = $filtros['fim']->endOfDay()->toDateTimeString();

        $query = Agendamento::query()
            ->with([
                'paciente:id,nome,cns,data_nascimento,telefone_contato',
                'sala:id,nome,tipo',
                'profissionais:id,nome,especialidade',
            ])
            ->whereBetween('data_atendimento', [$inicio, $fim]);

        $this->aplicarFiltrosAgendamento($query, $filtros);

        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query->orderBy('data_atendimento', 'desc')
            ->orderBy('hora_inicio', 'asc')
            ->paginate($porPagina);
    }

    /**
     * Relatório de Absenteísmo detalhado e lista de faltas recorrentes.
     *
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable, profissionalId?: ?int, especialidade?: ?string, salaId?: ?int, modalidade?: ?string}  $filtros
     * @return array<string, mixed>
     */
    public function obterAbsenteismo(array $filtros): array
    {
        $inicio = $filtros['inicio']->startOfDay()->toDateTimeString();
        $fim = $filtros['fim']->endOfDay()->toDateTimeString();

        $baseQuery = Agendamento::query()
            ->whereBetween('data_atendimento', [$inicio, $fim]);

        $this->aplicarFiltrosAgendamento($baseQuery, $filtros);

        // Faltas por turno (Manhã < 12:00, Tarde >= 12:00)
        $faltasTurno = (clone $baseQuery)
            ->whereIn('status', ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'FALTOU'])
            ->selectRaw("
                SUM(CASE WHEN hora_inicio < '12:00:00' THEN 1 ELSE 0 END) as manha,
                SUM(CASE WHEN hora_inicio >= '12:00:00' THEN 1 ELSE 0 END) as tarde,
                COUNT(*) as total
            ")
            ->first();

        // Faltas por especialidade dos profissionais envolvidos
        $faltasEspecialidade = DB::table('agendamentos')
            ->join('agendamento_profissionais', 'agendamentos.id', '=', 'agendamento_profissionais.agendamento_id')
            ->join('profissionais', 'agendamento_profissionais.profissional_id', '=', 'profissionais.id')
            ->whereBetween('agendamentos.data_atendimento', [$inicio, $fim])
            ->whereNull('agendamentos.deleted_at')
            ->whereIn('agendamentos.status', ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'FALTOU'])
            ->when(! empty($filtros['profissionalId']), fn ($q) => $q->where('profissionais.id', $filtros['profissionalId']))
            ->when(! empty($filtros['especialidade']), fn ($q) => $q->where('profissionais.especialidade', $filtros['especialidade']))
            ->when(! empty($filtros['salaId']), fn ($q) => $q->where('agendamentos.sala_id', $filtros['salaId']))
            ->selectRaw('profissionais.especialidade, COUNT(DISTINCT agendamentos.id) as total')
            ->groupBy('profissionais.especialidade')
            ->orderByDesc('total')
            ->get();

        // Pacientes com faltas recorrentes (2 ou mais faltas no período analisado)
        $faltasRecorrentes = DB::table('agendamentos')
            ->join('pacientes', 'agendamentos.paciente_id', '=', 'pacientes.id')
            ->whereBetween('agendamentos.data_atendimento', [$inicio, $fim])
            ->whereNull('agendamentos.deleted_at')
            ->whereIn('agendamentos.status', ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'FALTOU'])
            ->selectRaw("
                pacientes.id,
                pacientes.nome,
                pacientes.telefone_contato,
                pacientes.ubs_referencia,
                SUM(CASE WHEN agendamentos.status = 'FALTA_JUSTIFICADA' THEN 1 ELSE 0 END) as justificadas,
                SUM(CASE WHEN agendamentos.status IN ('FALTA_INJUSTIFICADA', 'FALTOU') THEN 1 ELSE 0 END) as injustificadas,
                COUNT(*) as total_faltas
            ")
            ->groupBy('pacientes.id', 'pacientes.nome', 'pacientes.telefone_contato', 'pacientes.ubs_referencia')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total_faltas')
            ->limit(30)
            ->get();

        return [
            'turno_manha' => (int) ($faltasTurno->manha ?? 0),
            'turno_tarde' => (int) ($faltasTurno->tarde ?? 0),
            'turno_total' => (int) ($faltasTurno->total ?? 0),
            'por_especialidade' => $faltasEspecialidade,
            'pacientes_recorrentes' => $faltasRecorrentes,
        ];
    }

    /**
     * Relatório consolidado da população de Pacientes e Qualidade Cadastral.
     * Executa agregações otimizadas diretamente no banco, evitando carregar os 38.000+ pacientes em memória.
     *
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable}  $filtros
     * @return array<string, mixed>
     */
    public function obterPacientes(array $filtros): array
    {
        $inicio = $filtros['inicio']->startOfDay()->toDateTimeString();
        $fim = $filtros['fim']->endOfDay()->toDateTimeString();

        // Estatísticas gerais da base ativa
        $statsCadastrais = DB::table('pacientes')
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_ativos,
                SUM(CASE WHEN cns IS NULL OR TRIM(cns) = '' THEN 1 ELSE 0 END) as sem_cns,
                SUM(CASE WHEN data_nascimento IS NULL THEN 1 ELSE 0 END) as sem_nascimento,
                SUM(CASE WHEN ubs_referencia IS NULL OR TRIM(ubs_referencia) = '' THEN 1 ELSE 0 END) as sem_ubs,
                SUM(CASE WHEN nome_acs IS NULL OR TRIM(nome_acs) = '' THEN 1 ELSE 0 END) as sem_acs,
                SUM(CASE WHEN origem_cadastro = 'ESUS_PEC' OR id_externo_esus IS NOT NULL THEN 1 ELSE 0 END) as origem_esus,
                SUM(CASE WHEN origem_cadastro != 'ESUS_PEC' AND id_externo_esus IS NULL THEN 1 ELSE 0 END) as origem_manual
            ")
            ->first();

        $totalAtivos = (int) ($statsCadastrais->total_ativos ?? 0);
        $semCns = (int) ($statsCadastrais->sem_cns ?? 0);
        $semNascimento = (int) ($statsCadastrais->sem_nascimento ?? 0);
        $semUbs = (int) ($statsCadastrais->sem_ubs ?? 0);
        $semAcs = (int) ($statsCadastrais->sem_acs ?? 0);
        $origemEsus = (int) ($statsCadastrais->origem_esus ?? 0);
        $origemManual = (int) ($statsCadastrais->origem_manual ?? 0);

        // Novos cadastros no período
        $novosCadastrosPeriodo = DB::table('pacientes')
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$filtros['inicio']->startOfDay(), $filtros['fim']->endOfDay()])
            ->count();

        // Pacientes que utilizaram os serviços no período (agendamentos presentes ou atendidos)
        $pacientesAtendidosPeriodo = DB::table('agendamentos')
            ->whereBetween('data_atendimento', [$inicio, $fim])
            ->whereNull('deleted_at')
            ->whereIn('status', ['PRESENTE', 'ATENDIDO'])
            ->distinct('paciente_id')
            ->count('paciente_id');

        // Distribuição etária calculada no banco de forma compatível com MySQL e SQLite
        $driver = DB::connection()->getDriverName();
        $idadeExpr = match ($driver) {
            'sqlite' => "(cast(strftime('%Y', 'now') as integer) - cast(strftime('%Y', data_nascimento) as integer))",
            default => 'TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE())',
        };

        $faixasEtarias = DB::table('pacientes')
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->whereNotNull('data_nascimento')
            ->selectRaw("
                SUM(CASE WHEN {$idadeExpr} <= 12 THEN 1 ELSE 0 END) as criancas,
                SUM(CASE WHEN {$idadeExpr} BETWEEN 13 AND 17 THEN 1 ELSE 0 END) as adolescentes,
                SUM(CASE WHEN {$idadeExpr} BETWEEN 18 AND 59 THEN 1 ELSE 0 END) as adultos,
                SUM(CASE WHEN {$idadeExpr} >= 60 THEN 1 ELSE 0 END) as idosos
            ")
            ->first();

        // Modalidades de Reabilitação
        $castType = match ($driver) {
            'mysql' => 'CHAR',
            default => 'TEXT',
        };

        $modalidades = DB::table('pacientes')
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->whereNotNull('modalidades_reabilitacao')
            ->selectRaw("
                SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%FISICA%' THEN 1 ELSE 0 END) as fisica,
                SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%INTELECTUAL%' THEN 1 ELSE 0 END) as intelectual,
                SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%AUDITIVA%' THEN 1 ELSE 0 END) as auditiva,
                SUM(CASE WHEN CAST(modalidades_reabilitacao AS {$castType}) LIKE '%VISUAL%' THEN 1 ELSE 0 END) as visual
            ")
            ->first();

        // Top 8 UBS de referência
        $topUbs = DB::table('pacientes')
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->whereNotNull('ubs_referencia')
            ->where('ubs_referencia', '!=', '')
            ->selectRaw('ubs_referencia, COUNT(*) as total')
            ->groupBy('ubs_referencia')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Percentuais de completude cadastral
        $percCns = $totalAtivos > 0 ? round((($totalAtivos - $semCns) / $totalAtivos) * 100, 1) : 0.0;
        $percNascimento = $totalAtivos > 0 ? round((($totalAtivos - $semNascimento) / $totalAtivos) * 100, 1) : 0.0;
        $percUbs = $totalAtivos > 0 ? round((($totalAtivos - $semUbs) / $totalAtivos) * 100, 1) : 0.0;
        $percAcs = $totalAtivos > 0 ? round((($totalAtivos - $semAcs) / $totalAtivos) * 100, 1) : 0.0;

        return [
            'total_ativos' => $totalAtivos,
            'novos_periodo' => $novosCadastrosPeriodo,
            'atendidos_periodo' => $pacientesAtendidosPeriodo,
            'sem_cns' => $semCns,
            'sem_nascimento' => $semNascimento,
            'sem_ubs' => $semUbs,
            'sem_acs' => $semAcs,
            'perc_cns' => $percCns,
            'perc_nascimento' => $percNascimento,
            'perc_ubs' => $percUbs,
            'perc_acs' => $percAcs,
            'origem_esus' => $origemEsus,
            'origem_manual' => $origemManual,
            'faixas_etarias' => [
                'criancas' => (int) ($faixasEtarias->criancas ?? 0),
                'adolescentes' => (int) ($faixasEtarias->adolescentes ?? 0),
                'adultos' => (int) ($faixasEtarias->adultos ?? 0),
                'idosos' => (int) ($faixasEtarias->idosos ?? 0),
                'nao_informado' => $semNascimento,
            ],
            'modalidades' => [
                'FISICA' => (int) ($modalidades->fisica ?? 0),
                'INTELECTUAL' => (int) ($modalidades->intelectual ?? 0),
                'AUDITIVA' => (int) ($modalidades->auditiva ?? 0),
                'VISUAL' => (int) ($modalidades->visual ?? 0),
            ],
            'top_ubs' => $topUbs,
        ];
    }

    /**
     * Relatório por Profissional e Especialidades com preservação da regra de atendimentos compartilhados.
     *
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable, especialidade?: ?string}  $filtros
     * @return array<string, mixed>
     */
    public function obterProfissionais(array $filtros): array
    {
        $inicio = $filtros['inicio']->startOfDay()->toDateTimeString();
        $fim = $filtros['fim']->endOfDay()->toDateTimeString();

        $profissionais = Profissional::query()
            ->ativos()
            ->when(! empty($filtros['especialidade']), fn ($q) => $q->where('especialidade', $filtros['especialidade']))
            ->orderBy('nome')
            ->get();

        $linhas = [];
        $totalAtendimentosVinculados = 0;
        $totalEvolucoesAssinadas = 0;
        $totalProducoesBpai = 0;

        foreach ($profissionais as $prof) {
            // Contagem de agendamentos em que o profissional participou
            $agendStats = DB::table('agendamento_profissionais')
                ->join('agendamentos', 'agendamento_profissionais.agendamento_id', '=', 'agendamentos.id')
                ->where('agendamento_profissionais.profissional_id', $prof->id)
                ->whereBetween('agendamentos.data_atendimento', [$inicio, $fim])
                ->whereNull('agendamentos.deleted_at')
                ->selectRaw("
                    COUNT(DISTINCT agendamentos.id) as total_agendamentos,
                    SUM(CASE WHEN agendamentos.status = 'ATENDIDO' THEN 1 ELSE 0 END) as concluidos,
                    SUM(CASE WHEN agendamentos.status = 'PRESENTE' THEN 1 ELSE 0 END) as presentes,
                    SUM(CASE WHEN agendamentos.status IN ('FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'FALTOU') THEN 1 ELSE 0 END) as faltas,
                    SUM(CASE WHEN agendamentos.tipo_atendimento = 'COMPARTILHADO' THEN 1 ELSE 0 END) as compartilhados,
                    SUM(CASE WHEN agendamentos.tipo_atendimento = 'INDIVIDUAL' THEN 1 ELSE 0 END) as individuais
                ")
                ->first();

            // Evoluções clínicas assinadas pelo profissional
            $evolucoes = EvolucaoClinica::query()
                ->where('profissional_id', $prof->id)
                ->whereNotNull('assinado_em')
                ->whereBetween('assinado_em', [$filtros['inicio'], $filtros['fim']])
                ->count();

            // Produções BPA-i
            $producoes = ProducaoBpai::query()
                ->where('profissional_id', $prof->id)
                ->whereBetween('data_atendimento', [$inicio, $fim])
                ->count();

            $totalAgend = (int) ($agendStats->total_agendamentos ?? 0);
            $concluidos = (int) ($agendStats->concluidos ?? 0);
            $presentes = (int) ($agendStats->presentes ?? 0);
            $faltas = (int) ($agendStats->faltas ?? 0);
            $compartilhados = (int) ($agendStats->compartilhados ?? 0);
            $individuais = (int) ($agendStats->individuais ?? 0);

            $linhas[] = [
                'id' => $prof->id,
                'nome' => $prof->nome,
                'especialidade' => $prof->especialidade ?: ($prof->cbo?->titulo_oficial ?? 'Geral'),
                'cbo' => $prof->cbo_padrao ?? '-',
                'total_agendamentos' => $totalAgend,
                'concluidos' => $concluidos,
                'presentes' => $presentes,
                'faltas' => $faltas,
                'compartilhados' => $compartilhados,
                'individuais' => $individuais,
                'evolucoes_assinadas' => $evolucoes,
                'producoes_bpai' => $producoes,
            ];

            $totalAtendimentosVinculados += $totalAgend;
            $totalEvolucoesAssinadas += $evolucoes;
            $totalProducoesBpai += $producoes;
        }

        // Distribuição por Especialidade
        $porEspecialidade = DB::table('profissionais')
            ->where('ativo', true)
            ->selectRaw('especialidade, COUNT(*) as total_profissionais')
            ->groupBy('especialidade')
            ->orderByDesc('total_profissionais')
            ->get();

        return [
            'linhas' => $linhas,
            'total_profissionais_ativos' => $profissionais->count(),
            'total_atendimentos_vinculados' => $totalAtendimentosVinculados,
            'total_evolucoes_assinadas' => $totalEvolucoesAssinadas,
            'total_producoes_bpai' => $totalProducoesBpai,
            'por_especialidade' => $porEspecialidade,
        ];
    }

    /**
     * Relatório de Salas e Utilização de Horários.
     *
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable}  $filtros
     * @return array<string, mixed>
     */
    public function obterSalas(array $filtros): array
    {
        $inicio = $filtros['inicio']->startOfDay()->toDateTimeString();
        $fim = $filtros['fim']->endOfDay()->toDateTimeString();

        $salas = Sala::query()->ativas()->orderBy('nome')->get();

        $linhasSalas = [];
        $totalAgendamentosUnidade = 0;

        foreach ($salas as $sala) {
            $stats = DB::table('agendamentos')
                ->where('sala_id', $sala->id)
                ->whereBetween('data_atendimento', [$inicio, $fim])
                ->whereNull('deleted_at')
                ->where('status', '!=', 'CANCELADO')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN hora_inicio < '12:00:00' THEN 1 ELSE 0 END) as turno_manha,
                    SUM(CASE WHEN hora_inicio >= '12:00:00' THEN 1 ELSE 0 END) as turno_tarde,
                    SUM(CASE WHEN tipo_atendimento = 'COMPARTILHADO' THEN 1 ELSE 0 END) as compartilhados,
                    SUM(CASE WHEN tipo_atendimento = 'INDIVIDUAL' THEN 1 ELSE 0 END) as individuais,
                    SUM(CASE WHEN tipo_atendimento = 'TRABALHO_INTERNO' THEN 1 ELSE 0 END) as trabalho_interno
                ")
                ->first();

            $total = (int) ($stats->total ?? 0);
            $totalAgendamentosUnidade += $total;

            $linhasSalas[] = [
                'id' => $sala->id,
                'nome' => $sala->nome,
                'tipo' => $sala->tipo ?? 'Atendimento',
                'total_agendamentos' => $total,
                'turno_manha' => (int) ($stats->turno_manha ?? 0),
                'turno_tarde' => (int) ($stats->turno_tarde ?? 0),
                'compartilhados' => (int) ($stats->compartilhados ?? 0),
                'individuais' => (int) ($stats->individuais ?? 0),
                'trabalho_interno' => (int) ($stats->trabalho_interno ?? 0),
            ];
        }

        // Calcula ocupação relativa
        foreach ($linhasSalas as &$linha) {
            $linha['ocupacao_relativa_pct'] = $totalAgendamentosUnidade > 0
                ? round(($linha['total_agendamentos'] / $totalAgendamentosUnidade) * 100, 1)
                : 0.0;
        }
        unset($linha);

        // Agendamentos por faixa horária consolidada
        $faixasHorarias = DB::table('agendamentos')
            ->whereBetween('data_atendimento', [$inicio, $fim])
            ->whereNull('deleted_at')
            ->where('status', '!=', 'CANCELADO')
            ->selectRaw('SUBSTRING(hora_inicio, 1, 2) as hora_prefixo, COUNT(*) as total')
            ->groupBy('hora_prefixo')
            ->orderBy('hora_prefixo')
            ->get()
            ->map(fn ($r) => [
                'faixa' => $r->hora_prefixo.':00',
                'total' => (int) $r->total,
            ])
            ->all();

        return [
            'salas' => $linhasSalas,
            'total_salas_ativas' => $salas->count(),
            'total_agendamentos' => $totalAgendamentosUnidade,
            'faixas_horarias' => $faixasHorarias,
        ];
    }

    /**
     * Relatório gerencial de Produção SUS / BPA-i (EXCLUSIVAMENTE CONSULTIVO E SOMENTE LEITURA).
     *
     * @return array<string, mixed>
     */
    public function obterProducaoBpai(string $competencia, ?int $profissionalId = null): array
    {
        $compLimpa = preg_replace('/\D/', '', $competencia) ?? '';
        if (strlen($compLimpa) !== 6) {
            $compLimpa = CarbonImmutable::now()->format('Ym');
        }

        $baseQuery = ProducaoBpai::query()
            ->where('competencia', $compLimpa)
            ->when($profissionalId !== null, fn ($q) => $q->where('profissional_id', $profissionalId));

        $totais = (clone $baseQuery)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status_exportacao = 'PENDENTE' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN status_exportacao = 'VALIDADO' THEN 1 ELSE 0 END) as validados,
            SUM(CASE WHEN status_exportacao = 'INCONSISTENTE' THEN 1 ELSE 0 END) as inconsistentes,
            SUM(CASE WHEN status_exportacao = 'EXPORTADO' THEN 1 ELSE 0 END) as exportados
        ")->first();

        $total = (int) ($totais->total ?? 0);
        $pendentes = (int) ($totais->pendentes ?? 0);
        $validados = (int) ($totais->validados ?? 0);
        $inconsistentes = (int) ($totais->inconsistentes ?? 0);
        $exportados = (int) ($totais->exportados ?? 0);

        $aptos = $validados + $exportados;
        $taxaConformidade = $total > 0 ? round(($aptos / $total) * 100, 1) : 0.0;

        // Produção por Procedimento SIGTAP
        $porProcedimento = DB::table('producoes_bpai')
            ->join('procedimentos_sigtap', 'producoes_bpai.procedimento_id', '=', 'procedimentos_sigtap.id')
            ->where('producoes_bpai.competencia', $compLimpa)
            ->when($profissionalId !== null, fn ($q) => $q->where('producoes_bpai.profissional_id', $profissionalId))
            ->selectRaw('procedimentos_sigtap.codigo, procedimentos_sigtap.descricao, COUNT(*) as quantidade')
            ->groupBy('procedimentos_sigtap.codigo', 'procedimentos_sigtap.descricao')
            ->orderByDesc('quantidade')
            ->limit(15)
            ->get();

        // Produção por CBO
        $porCbo = DB::table('producoes_bpai')
            ->where('competencia', $compLimpa)
            ->when($profissionalId !== null, fn ($q) => $q->where('profissional_id', $profissionalId))
            ->selectRaw('cbo_executante, COUNT(*) as quantidade')
            ->groupBy('cbo_executante')
            ->orderByDesc('quantidade')
            ->get();

        // Produção por Profissional
        $porProfissional = DB::table('producoes_bpai')
            ->join('profissionais', 'producoes_bpai.profissional_id', '=', 'profissionais.id')
            ->where('producoes_bpai.competencia', $compLimpa)
            ->when($profissionalId !== null, fn ($q) => $q->where('producoes_bpai.profissional_id', $profissionalId))
            ->selectRaw("
                profissionais.id,
                profissionais.nome,
                profissionais.especialidade,
                COUNT(*) as total,
                SUM(CASE WHEN producoes_bpai.status_exportacao IN ('VALIDADO', 'EXPORTADO') THEN 1 ELSE 0 END) as aptos,
                SUM(CASE WHEN producoes_bpai.status_exportacao = 'INCONSISTENTE' THEN 1 ELSE 0 END) as inconsistentes
            ")
            ->groupBy('profissionais.id', 'profissionais.nome', 'profissionais.especialidade')
            ->orderByDesc('total')
            ->get();

        // Pendências de Produção (somente leitura para investigação)
        $inconsistencias = ProducaoBpai::query()
            ->with(['paciente:id,nome,cns', 'profissional:id,nome', 'procedimento:id,codigo,nome'])
            ->where('competencia', $compLimpa)
            ->where('status_exportacao', 'INCONSISTENTE')
            ->when($profissionalId !== null, fn ($q) => $q->where('profissional_id', $profissionalId))
            ->orderBy('data_atendimento', 'desc')
            ->limit(20)
            ->get();

        return [
            'competencia' => $compLimpa,
            'competencia_formatada' => substr($compLimpa, 4, 2).'/'.substr($compLimpa, 0, 4),
            'total' => $total,
            'pendentes' => $pendentes,
            'validados' => $validados,
            'inconsistentes' => $inconsistentes,
            'exportados' => $exportados,
            'aptos' => $aptos,
            'taxa_conformidade' => $taxaConformidade,
            'por_procedimento' => $porProcedimento,
            'por_cbo' => $porCbo,
            'por_profissional' => $porProfissional,
            'inconsistencias' => $inconsistencias,
        ];
    }

    /**
     * Exportação CSV em streaming com UTF-8 BOM, delimitador ';' e auditoria LGPD.
     */
    public function exportarCsv(string $aba, array $filtros, ?User $usuario = null): StreamedResponse
    {
        $hoje = CarbonImmutable::now();
        $config = ConfiguracaoEstabelecimento::atual();
        $nomeUnidade = 'CER Digital - Centro Especializado em Reabilitação';
        $cnes = $config->cnes ?? self::CNES_PADRAO;

        $nomeArquivo = sprintf(
            'relatorio-%s-%s.csv',
            $aba,
            $hoje->format('Ymd_His')
        );

        // Registro de auditoria LGPD
        LogAuditoria::create([
            'user_id' => $usuario?->id ?? auth()->id(),
            'acao' => 'EXPORTACAO_RELATORIO',
            'tabela_afetada' => 'relatorios',
            'registro_id' => null,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'aba' => $aba,
                'filtros' => [
                    'inicio' => $filtros['inicio']->toDateString(),
                    'fim' => $filtros['fim']->toDateString(),
                    'periodo' => $filtros['periodo'] ?? 'mes_atual',
                ],
                'emitido_em' => $hoje->toIso8601String(),
            ],
            'created_at' => now(),
        ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nomeArquivo}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($aba, $filtros, $hoje, $nomeUnidade, $cnes): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM para Excel em português abrir com acentos corretos
            fwrite($handle, "\xEF\xBB\xBF");

            // Cabeçalho institucional
            fputcsv($handle, [$nomeUnidade], ';');
            fputcsv($handle, ["CNES: {$cnes}", "Emissão: {$hoje->format('d/m/Y H:i:s')}"], ';');
            fputcsv($handle, ["Período Analisado: {$filtros['inicio']->format('d/m/Y')} a {$filtros['fim']->format('d/m/Y')}"], ';');
            fputcsv($handle, [], ';');

            match ($aba) {
                'visao-geral' => $this->escreverCsvVisaoGeral($handle, $filtros),
                'atendimentos' => $this->escreverCsvAtendimentos($handle, $filtros),
                'absenteismo' => $this->escreverCsvAbsenteismo($handle, $filtros),
                'pacientes' => $this->escreverCsvPacientes($handle, $filtros),
                'profissionais' => $this->escreverCsvProfissionais($handle, $filtros),
                'salas' => $this->escreverCsvSalas($handle, $filtros),
                'producao-bpai' => $this->escreverCsvProducaoBpai($handle, $filtros),
                default => fputcsv($handle, ['Relatório', 'Sem dados'], ';'),
            };

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Aplica filtros comuns de profissional, especialidade, sala e modalidade.
     */
    private function aplicarFiltrosAgendamento($query, array $filtros): void
    {
        if (! empty($filtros['profissionalId'])) {
            $query->whereHas('profissionais', function ($q) use ($filtros): void {
                $q->where('profissionais.id', $filtros['profissionalId']);
            });
        }

        if (! empty($filtros['especialidade'])) {
            $query->whereHas('profissionais', function ($q) use ($filtros): void {
                $q->where('profissionais.especialidade', $filtros['especialidade']);
            });
        }

        if (! empty($filtros['salaId'])) {
            $query->where('sala_id', $filtros['salaId']);
        }

        if (! empty($filtros['modalidade'])) {
            $query->where('tipo_atendimento', $filtros['modalidade']);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvVisaoGeral($handle, array $filtros): void
    {
        $dados = $this->obterVisaoGeral($filtros);

        fputcsv($handle, ['INDICADOR', 'QUANTIDADE / VALOR', 'CRITÉRIO DE CÁLCULO'], ';');
        fputcsv($handle, ['Total de Agendamentos', $dados['total_agendamentos'], 'Total de horários marcados no período'], ';');
        fputcsv($handle, ['Pacientes Distintos', $dados['pacientes_distintos'], 'Contagem única de pacientes com horário'], ';');
        fputcsv($handle, ['Presenças Confirmadas', $dados['presencas'], "Pacientes com check-in realizado ('PRESENTE')"], ';');
        fputcsv($handle, ['Atendimentos Concluídos', $dados['atendidos'], "Atendimentos finalizados ('ATENDIDO')"], ';');
        fputcsv($handle, ['Aguardando Atendimento', $dados['aguardando'], "Horários agendados sem check-in ('AGENDADO')"], ';');
        fputcsv($handle, ['Faltas Justificadas', $dados['faltas_justificadas'], 'Faltas comunicadas com justificativa'], ';');
        fputcsv($handle, ['Faltas Injustificadas', $dados['faltas_injustificadas'], 'Faltas sem aviso ou justificativa'], ';');
        fputcsv($handle, ['Cancelamentos', $dados['cancelamentos'], 'Horários cancelados formalmente'], ';');
        fputcsv($handle, ['Taxa de Comparecimento', $dados['taxa_comparecimento'].'%', '(Presentes + Atendidos) / Total com desfecho'], ';');
        fputcsv($handle, ['Taxa de Absenteísmo', $dados['taxa_absenteismo'].'%', 'Faltas totais / Total com desfecho'], ';');
        fputcsv($handle, ['Evoluções Clínicas Assinadas', $dados['evolucoes_assinadas'], 'Evoluções de prontuário com assinatura eletrônica'], ';');
        fputcsv($handle, ['Produções BPA-i Registradas', $dados['producoes_registradas'], 'Procedimentos gerados para faturamento SUS'], ';');
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvAtendimentos($handle, array $filtros): void
    {
        fputcsv($handle, ['DATA', 'HORÁRIO', 'PACIENTE', 'CNS', 'PROFISSIONAL(IS)', 'SALA', 'TIPO', 'STATUS'], ';');

        $query = Agendamento::query()
            ->with(['paciente:id,nome,cns', 'sala:id,nome', 'profissionais:id,nome,especialidade'])
            ->whereBetween('data_atendimento', [$filtros['inicio']->toDateString(), $filtros['fim']->toDateString()]);

        $this->aplicarFiltrosAgendamento($query, $filtros);

        $query->orderBy('data_atendimento')->orderBy('hora_inicio')->chunk(200, function ($agendamentos) use ($handle): void {
            foreach ($agendamentos as $agendamento) {
                $profs = $agendamento->profissionais->pluck('nome')->implode('; ');
                fputcsv($handle, [
                    $agendamento->data_atendimento?->format('d/m/Y') ?? '-',
                    substr((string) $agendamento->hora_inicio, 0, 5),
                    $agendamento->paciente->nome ?? '-',
                    $agendamento->paciente->cns ?? '-',
                    $profs !== '' ? $profs : 'Não atribuído',
                    $agendamento->sala->nome ?? '-',
                    $agendamento->tipo_atendimento,
                    $agendamento->status,
                ], ';');
            }
        });
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvAbsenteismo($handle, array $filtros): void
    {
        $dados = $this->obterAbsenteismo($filtros);

        fputcsv($handle, ['ANÁLISE DE ABSENTEÍSMO POR TURNO'], ';');
        fputcsv($handle, ['Turno Manhã (< 12:00)', $dados['turno_manha']], ';');
        fputcsv($handle, ['Turno Tarde (>= 12:00)', $dados['turno_tarde']], ';');
        fputcsv($handle, ['Total de Faltas no Período', $dados['turno_total']], ';');
        fputcsv($handle, [], ';');

        fputcsv($handle, ['PACIENTES COM FALTAS RECORRENTES (>= 2 FALTAS NO PERÍODO)'], ';');
        fputcsv($handle, ['PACIENTE', 'TELEFONE', 'UBS REFERÊNCIA', 'JUSTIFICADAS', 'INJUSTIFICADAS', 'TOTAL FALTAS'], ';');
        foreach ($dados['pacientes_recorrentes'] as $pac) {
            fputcsv($handle, [
                $pac->nome,
                $pac->telefone_contato ?? 'Não informado',
                $pac->ubs_referencia ?? 'Não informada',
                $pac->justificadas,
                $pac->injustificadas,
                $pac->total_faltas,
            ], ';');
        }
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvPacientes($handle, array $filtros): void
    {
        $dados = $this->obterPacientes($filtros);

        fputcsv($handle, ['INDICADORES DA POPULAÇÃO DE PACIENTES'], ';');
        fputcsv($handle, ['Total de Pacientes Ativos', $dados['total_ativos']], ';');
        fputcsv($handle, ['Novos Pacientes Cadastrados no Período', $dados['novos_periodo']], ';');
        fputcsv($handle, ['Pacientes Atendidos no Período', $dados['atendidos_periodo']], ';');
        fputcsv($handle, [], ';');

        fputcsv($handle, ['QUALIDADE CADASTRAL', 'QUANTIDADE PENDENTE', 'PERCENTUAL COMPLETUDE'], ';');
        fputcsv($handle, ['Cartão SUS (CNS)', "{$dados['sem_cns']} sem preenchimento", "{$dados['perc_cns']}% preenchidos"], ';');
        fputcsv($handle, ['Data de Nascimento', "{$dados['sem_nascimento']} sem preenchimento", "{$dados['perc_nascimento']}% preenchidos"], ';');
        fputcsv($handle, ['UBS de Referência', "{$dados['sem_ubs']} sem preenchimento", "{$dados['perc_ubs']}% preenchidos"], ';');
        fputcsv($handle, ['Agente Comunitário (ACS)', "{$dados['sem_acs']} sem preenchimento", "{$dados['perc_acs']}% preenchidos"], ';');
        fputcsv($handle, [], ';');

        fputcsv($handle, ['DISTRIBUIÇÃO POR FAIXA ETÁRIA'], ';');
        fputcsv($handle, ['Crianças (0 a 12 anos)', $dados['faixas_etarias']['criancas']], ';');
        fputcsv($handle, ['Adolescentes (13 a 17 anos)', $dados['faixas_etarias']['adolescentes']], ';');
        fputcsv($handle, ['Adultos (18 a 59 anos)', $dados['faixas_etarias']['adultos']], ';');
        fputcsv($handle, ['Idosos (60+ anos)', $dados['faixas_etarias']['idosos']], ';');
        fputcsv($handle, [], ';');

        fputcsv($handle, ['ORIGEM DO CADASTRO'], ';');
        fputcsv($handle, ['Importação e-SUS / APS', $dados['origem_esus']], ';');
        fputcsv($handle, ['Cadastro Manual na Unidade', $dados['origem_manual']], ';');
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvProfissionais($handle, array $filtros): void
    {
        $dados = $this->obterProfissionais($filtros);

        fputcsv($handle, ['PROFISSIONAL', 'ESPECIALIDADE', 'CBO', 'TOTAL AGENDAMENTOS', 'CONCLUÍDOS', 'FALTAS', 'COMPARTILHADOS', 'EVOLUÇÕES ASSINADAS', 'PRODUÇÃO BPA-I'], ';');
        foreach ($dados['linhas'] as $l) {
            fputcsv($handle, [
                $l['nome'],
                $l['especialidade'],
                $l['cbo'],
                $l['total_agendamentos'],
                $l['concluidos'],
                $l['faltas'],
                $l['compartilhados'],
                $l['evolucoes_assinadas'],
                $l['producoes_bpai'],
            ], ';');
        }
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvSalas($handle, array $filtros): void
    {
        $dados = $this->obterSalas($filtros);

        fputcsv($handle, ['SALA', 'TIPO', 'TOTAL AGENDAMENTOS', 'MANHÃ', 'TARDE', 'COMPARTILHADOS', 'OCUPAÇÃO RELATIVA (%)'], ';');
        foreach ($dados['salas'] as $s) {
            fputcsv($handle, [
                $s['nome'],
                $s['tipo'],
                $s['total_agendamentos'],
                $s['turno_manha'],
                $s['turno_tarde'],
                $s['compartilhados'],
                $s['ocupacao_relativa_pct'].'%',
            ], ';');
        }
    }

    /**
     * @param  resource  $handle
     */
    private function escreverCsvProducaoBpai($handle, array $filtros): void
    {
        $competencia = $filtros['competencia'] ?? CarbonImmutable::now()->format('Ym');
        $dados = $this->obterProducaoBpai($competencia);

        fputcsv($handle, ["COMPETÊNCIA ANALISADA: {$dados['competencia_formatada']}"], ';');
        fputcsv($handle, ['Total de Produções', $dados['total']], ';');
        fputcsv($handle, ['Produções Aptas (Validadas/Exportadas)', $dados['aptos']], ';');
        fputcsv($handle, ['Produções Inconsistentes', $dados['inconsistentes']], ';');
        fputcsv($handle, ['Taxa de Conformidade', $dados['taxa_conformidade'].'%'], ';');
        fputcsv($handle, [], ';');

        fputcsv($handle, ['PRODUÇÃO POR PROCEDIMENTO SIGTAP'], ';');
        fputcsv($handle, ['CÓDIGO SIGTAP', 'PROCEDIMENTO', 'QUANTIDADE PRODUZIDA'], ';');
        foreach ($dados['por_procedimento'] as $proc) {
            fputcsv($handle, [$proc->codigo, $proc->descricao, $proc->quantidade], ';');
        }
    }
}
