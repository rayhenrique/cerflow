<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Profissional;
use App\Models\Sala;
use App\Services\RelatorioService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Relatórios e Indicadores Gerenciais — CER Digital')]
class RelatoriosGerenciais extends Component
{
    use WithPagination;

    #[Url(as: 'aba')]
    public string $abaAtiva = 'visao-geral';

    #[Url(as: 'periodo')]
    public string $periodoPredefinido = 'mes_atual';

    #[Url(as: 'inicio')]
    public string $dataInicio = '';

    #[Url(as: 'fim')]
    public string $dataFim = '';

    #[Url(as: 'prof')]
    public ?int $profissionalId = null;

    #[Url(as: 'esp')]
    public string $especialidade = '';

    #[Url(as: 'sala')]
    public ?int $salaId = null;

    #[Url(as: 'mod')]
    public string $modalidadeAtendimento = '';

    #[Url(as: 'status')]
    public string $statusFiltro = '';

    #[Url(as: 'comp')]
    public string $competencia = '';

    public bool $filtrosAbertosMobile = false;

    public function mount(RelatorioService $relatorioService, ?string $aba = null): void
    {
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']),
            403
        );

        if ($aba !== null && $aba !== '') {
            $this->abaAtiva = $aba;
        }

        $hoje = CarbonImmutable::now();

        if (empty($this->dataInicio) || empty($this->dataFim)) {
            $periodoResolvido = $relatorioService->resolverPeriodo($this->periodoPredefinido);
            $this->dataInicio = $periodoResolvido['inicio']->format('Y-m-d');
            $this->dataFim = $periodoResolvido['fim']->format('Y-m-d');
        }

        if (empty($this->competencia)) {
            $this->competencia = $hoje->format('Ym');
        }

        $this->garantirAbaPermitida();
    }

    public function selecionarAba(string $aba): void
    {
        $abasPermitidas = $this->abasPermitidasUsuario();
        if (in_array($aba, $abasPermitidas, true)) {
            $this->abaAtiva = $aba;
            $this->resetPage();
        }
    }

    public function aplicarPeriodoPredefinido(string $periodo, RelatorioService $relatorioService): void
    {
        $this->periodoPredefinido = $periodo;
        $resolvido = $relatorioService->resolverPeriodo($periodo);
        $this->dataInicio = $resolvido['inicio']->format('Y-m-d');
        $this->dataFim = $resolvido['fim']->format('Y-m-d');
        $this->resetPage();
    }

    public function atualizarPeriodoPersonalizado(): void
    {
        $this->periodoPredefinido = 'personalizado';
        $this->resetPage();
    }

    public function limparFiltros(RelatorioService $relatorioService): void
    {
        $hoje = CarbonImmutable::now();
        $this->periodoPredefinido = 'mes_atual';
        $resolvido = $relatorioService->resolverPeriodo('mes_atual');
        $this->dataInicio = $resolvido['inicio']->format('Y-m-d');
        $this->dataFim = $resolvido['fim']->format('Y-m-d');
        $this->profissionalId = null;
        $this->especialidade = '';
        $this->salaId = null;
        $this->modalidadeAtendimento = '';
        $this->statusFiltro = '';
        $this->competencia = $hoje->format('Ym');
        $this->resetPage();
    }

    public function toggleFiltrosMobile(): void
    {
        $this->filtrosAbertosMobile = ! $this->filtrosAbertosMobile;
    }

    public function exportarCsv(RelatorioService $relatorioService): StreamedResponse
    {
        $filtros = $this->montarFiltrosArray($relatorioService);

        return $relatorioService->exportarCsv($this->abaAtiva, $filtros, auth()->user());
    }

    public function render(RelatorioService $relatorioService): View
    {
        $this->garantirAbaPermitida();

        $filtros = $this->montarFiltrosArray($relatorioService);

        // Listas auxiliares para preenchimento de filtros
        $listaProfissionais = Profissional::query()->ativos()->orderBy('nome')->get(['id', 'nome', 'especialidade']);
        $listaEspecialidades = Profissional::query()->ativos()->whereNotNull('especialidade')->where('especialidade', '!=', '')->distinct()->orderBy('especialidade')->pluck('especialidade');
        $listaSalas = Sala::query()->ativas()->orderBy('nome')->get(['id', 'nome']);

        // Competências disponíveis (últimas 12 competências)
        $competenciasDisponiveis = $this->gerarCompetenciasDisponiveis();

        // Carrega dados exclusivamente da aba ativa para poupar a CPU e o banco
        $dadosAba = match ($this->abaAtiva) {
            'visao-geral' => [
                'visaoGeral' => $relatorioService->obterVisaoGeral($filtros),
                'absenteismo' => $relatorioService->obterAbsenteismo($filtros),
            ],
            'atendimentos' => [
                'visaoGeral' => $relatorioService->obterVisaoGeral($filtros),
                'absenteismo' => $relatorioService->obterAbsenteismo($filtros),
                'atendimentosPaginados' => $relatorioService->obterAtendimentosPaginados($filtros, 15),
            ],
            'pacientes' => [
                'dadosPacientes' => $relatorioService->obterPacientes($filtros),
            ],
            'profissionais' => [
                'dadosProfissionais' => $relatorioService->obterProfissionais($filtros),
            ],
            'salas' => [
                'dadosSalas' => $relatorioService->obterSalas($filtros),
            ],
            'producao-bpai' => [
                'dadosProducaoBpai' => $relatorioService->obterProducaoBpai($this->competencia, $this->profissionalId),
            ],
            default => [],
        };

        return view('livewire.relatorios-gerenciais', array_merge([
            'filtros' => $filtros,
            'listaProfissionais' => $listaProfissionais,
            'listaEspecialidades' => $listaEspecialidades,
            'listaSalas' => $listaSalas,
            'competenciasDisponiveis' => $competenciasDisponiveis,
            'abasPermitidas' => $this->abasPermitidasUsuario(),
        ], $dadosAba));
    }

    /**
     * @return array{
     *     inicio: CarbonImmutable,
     *     fim: CarbonImmutable,
     *     descricao: string,
     *     periodo: string,
     *     profissionalId: ?int,
     *     especialidade: ?string,
     *     salaId: ?int,
     *     modalidade: ?string,
     *     status: ?string,
     *     competencia: string
     * }
     */
    private function montarFiltrosArray(RelatorioService $relatorioService): array
    {
        $periodoResolvido = $relatorioService->resolverPeriodo(
            $this->periodoPredefinido,
            $this->dataInicio,
            $this->dataFim
        );

        return [
            'inicio' => $periodoResolvido['inicio'],
            'fim' => $periodoResolvido['fim'],
            'descricao' => $periodoResolvido['descricao'],
            'periodo' => $this->periodoPredefinido,
            'profissionalId' => $this->profissionalId,
            'especialidade' => $this->especialidade !== '' ? $this->especialidade : null,
            'salaId' => $this->salaId,
            'modalidade' => $this->modalidadeAtendimento !== '' ? $this->modalidadeAtendimento : null,
            'status' => $this->statusFiltro !== '' ? $this->statusFiltro : null,
            'competencia' => $this->competencia,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function abasPermitidasUsuario(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        if ($user->hasRole('coordenacao')) {
            return ['visao-geral', 'atendimentos', 'pacientes', 'profissionais', 'salas', 'producao-bpai'];
        }

        if ($user->hasRole('faturamento')) {
            return ['visao-geral', 'atendimentos', 'producao-bpai'];
        }

        if ($user->hasRole('recepcao')) {
            return ['visao-geral', 'atendimentos', 'pacientes', 'salas'];
        }

        if ($user->hasRole('terapeuta')) {
            return ['visao-geral', 'atendimentos', 'profissionais', 'salas'];
        }

        return ['visao-geral'];
    }

    private function garantirAbaPermitida(): void
    {
        $permitidas = $this->abasPermitidasUsuario();
        if (! in_array($this->abaAtiva, $permitidas, true)) {
            $this->abaAtiva = $permitidas[0] ?? 'visao-geral';
        }
    }

    /**
     * @return array<string, string>
     */
    private function gerarCompetenciasDisponiveis(): array
    {
        $atual = CarbonImmutable::now()->startOfMonth();
        $competencias = [];

        for ($i = 0; $i < 12; $i++) {
            $mes = $atual->subMonthsNoOverflow($i);
            $chave = $mes->format('Ym');
            $competencias[$chave] = $mes->translatedFormat('F/Y').' ('.$mes->format('m/Y').')';
        }

        return $competencias;
    }
}
