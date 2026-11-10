<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\EvolucaoClinica;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Services\AgendamentoService;
use App\Services\ChamadaPacienteService;
use App\Services\EvolucaoService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Prontuário Multiprofissional (PEP) — CER Digital')]
class ProntuarioPaciente extends Component
{
    use WithPagination;

    public string $abaPep = 'prontuario'; // 'prontuario' | 'meus_atendimentos'

    public ?int $pacienteId = null;

    public string $pesquisaPaciente = '';

    public ?int $agendamento_id = null;

    public ?int $procedimento_id = null;

    public string $queixa_estado_atual = '';

    public string $conduta_terapeutica = '';

    public string $intercorrencias = '';

    public string $pin_assinatura = '';

    public bool $modalPinAberto = false;

    public bool $modalAdendoAberto = false;

    public ?int $evolucaoAdendoId = null;

    public string $justificativa_adendo = '';

    public string $texto_adendo = '';

    public string $pin_adendo = '';

    // --- Filtros da Seção Meus Atendimentos ---
    public string $filtroPeriodo = 'hoje'; // 'hoje' | 'proximos' | 'anteriores' | 'todos'

    public string $filtroStatus = 'todos'; // 'todos' | 'AGENDADO' | 'PRESENTE' | 'ATENDIDO' | 'FALTA' | 'CANCELADO'

    // --- Avaliação & Diagnóstico Clínico (CID-10 e Modalidades) ---
    public bool $modalDiagnosticoAberto = false;

    public string $diag_cid_principal = '';

    public string $diag_cid_secundario = '';

    /** @var array<int, string> */
    public array $diag_modalidades = [];

    // --- Modal Novo Atendimento / Agendamento no PEP ---
    public bool $modalNovoAgendamentoAberto = false;

    public ?int $novo_paciente_id = null;

    public ?int $novo_sala_id = null;

    public string $novo_data = '';

    public string $novo_hora_inicio = '08:00';

    public string $novo_hora_fim = '08:45';

    public string $novo_dia_semana = 'SEGUNDA';

    public string $novo_tipo_atendimento = 'INDIVIDUAL';

    /** @var array<int, int> */
    public array $novo_profissionais = [];

    public bool $novo_recorrente = false;

    public int $novo_semanas_recorrencia = 1;

    public string $novo_observacao = '';

    /** @var ?array{tipo: string, titulo: string, mensagem: string} */
    public ?array $alertaPaciente = null;

    public function mount(?int $paciente = null): void
    {
        Gate::authorize('viewAny', EvolucaoClinica::class);

        if ($paciente !== null) {
            $this->pacienteId = $paciente;
        }
    }

    public function selecionarAbaPep(string $aba): void
    {
        if (in_array($aba, ['prontuario', 'meus_atendimentos'], true)) {
            $this->abaPep = $aba;
        }
    }

    public function updatedFiltroPeriodo(): void
    {
        $this->resetPage('pageMeusAtendimentos');
    }

    public function updatedFiltroStatus(): void
    {
        $this->resetPage('pageMeusAtendimentos');
    }

    public function selecionarPaciente(int $id): void
    {
        $paciente = Paciente::query()->ativos()->find($id);
        if ($paciente !== null) {
            Gate::authorize('viewProntuario', $paciente);
            $this->pacienteId = (int) $paciente->id;
            $this->pesquisaPaciente = '';
            $this->reset(['agendamento_id', 'procedimento_id', 'queixa_estado_atual', 'conduta_terapeutica', 'intercorrencias', 'pin_assinatura']);
            $this->resetPage('pageAtendimentosPaciente');
            $this->resetValidation();
        }
    }

    public function limparPacienteSelecionado(): void
    {
        $this->pacienteId = null;
        $this->pesquisaPaciente = '';
        $this->reset(['agendamento_id', 'procedimento_id', 'queixa_estado_atual', 'conduta_terapeutica', 'intercorrencias', 'pin_assinatura']);
        $this->resetValidation();
    }

    /**
     * Realiza a busca sob demanda de pacientes no banco de dados.
     *
     * @return array<int, array{id: int, nome: string, cpf: ?string, cns: ?string, data_nascimento: ?string}>
     */
    public function buscarPacientes(): array
    {
        $pacientes = Paciente::buscarParaSelecao($this->pesquisaPaciente, 20);

        return $pacientes->map(fn (Paciente $p): array => [
            'id' => (int) $p->id,
            'nome' => (string) $p->nome,
            'cpf' => $p->cpf ? $this->formatarCpf((string) $p->cpf) : null,
            'cns' => $p->cns,
            'data_nascimento' => $p->data_nascimento?->format('d/m/Y'),
        ])->all();
    }

    private function formatarCpf(string $cpf): string
    {
        $d = preg_replace('/\D/', '', $cpf) ?? '';
        if (strlen($d) === 11) {
            return substr($d, 0, 3).'.'.substr($d, 3, 3).'.'.substr($d, 6, 3).'-'.substr($d, 9, 2);
        }

        return $cpf;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'agendamento_id' => ['required', 'integer', 'exists:agendamentos,id'],
            'procedimento_id' => ['required', 'integer', 'exists:procedimentos_sigtap,id'],
            'queixa_estado_atual' => ['required', 'string', 'min:5'],
            'conduta_terapeutica' => ['required', 'string', 'min:5'],
            'intercorrencias' => ['nullable', 'string'],
            'pin_assinatura' => ['required', 'string', 'min:4', 'max:12'],
        ];
    }

    public function solicitarPinAssinatura(): void
    {
        Gate::authorize('create', EvolucaoClinica::class);

        $this->validate([
            'agendamento_id' => ['required', 'integer', 'exists:agendamentos,id'],
            'procedimento_id' => ['required', 'integer', 'exists:procedimentos_sigtap,id'],
            'queixa_estado_atual' => ['required', 'string', 'min:5'],
            'conduta_terapeutica' => ['required', 'string', 'min:5'],
        ]);

        $agendamento = Agendamento::findOrFail($this->agendamento_id);
        if (! in_array($agendamento->status, ['PRESENTE', 'ATENDIDO'], true) && $agendamento->chegada_em === null) {
            $this->addError('agendamento_id', 'Apenas agendamentos com presença confirmada na recepção (PRESENTE ou ATENDIDO) podem receber evolução clínica e gerar produção BPA-i.');
            $this->dispatch('toast', tipo: 'alerta', titulo: 'Presença não confirmada', mensagem: 'O paciente ainda não teve sua presença registrada na recepção para este agendamento.');

            return;
        }

        $this->pin_assinatura = '';
        $this->modalPinAberto = true;
    }

    public function confirmarAssinaturaEvolucao(EvolucaoService $evolucaoService): void
    {
        Gate::authorize('create', EvolucaoClinica::class);
        $this->validate();

        $agendamento = Agendamento::findOrFail($this->agendamento_id);
        if (! in_array($agendamento->status, ['PRESENTE', 'ATENDIDO'], true) && $agendamento->chegada_em === null) {
            $this->addError('agendamento_id', 'Apenas agendamentos com presença confirmada na recepção (PRESENTE ou ATENDIDO) podem receber evolução clínica e gerar produção BPA-i.');
            $this->dispatch('toast', tipo: 'alerta', titulo: 'Presença não confirmada', mensagem: 'O paciente ainda não teve sua presença registrada na recepção para este agendamento.');

            return;
        }

        $profissional = auth()->user()?->profissional;
        $procedimento = ProcedimentoSigtap::findOrFail($this->procedimento_id);

        $evolucaoService->registrarEAssinar(
            agendamento: $agendamento,
            profissional: $profissional,
            procedimento: $procedimento,
            dadosClinicos: [
                'queixa_estado_atual' => $this->queixa_estado_atual,
                'conduta_terapeutica' => $this->conduta_terapeutica,
                'intercorrencias' => $this->intercorrencias !== '' ? $this->intercorrencias : null,
            ],
            pinAssinatura: $this->pin_assinatura,
            ipAddress: request()->ip(),
        );

        $this->modalPinAberto = false;
        $this->reset(['queixa_estado_atual', 'conduta_terapeutica', 'intercorrencias', 'pin_assinatura']);
        $mensagem = 'Evolução clínica assinada eletronicamente (SHA-256) e produção BPA-i gerada.';
        session()->flash('sucesso_pep', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Evolução assinada', mensagem: $mensagem);
    }

    public function abrirModalAdendo(int $evolucaoId): void
    {
        $evolucao = EvolucaoClinica::findOrFail($evolucaoId);
        Gate::authorize('createAdendo', $evolucao);

        $this->evolucaoAdendoId = $evolucao->id;
        $this->justificativa_adendo = '';
        $this->texto_adendo = '';
        $this->pin_adendo = '';
        $this->modalAdendoAberto = true;
    }

    public function salvarAdendo(EvolucaoService $evolucaoService): void
    {
        $evolucao = EvolucaoClinica::findOrFail($this->evolucaoAdendoId);
        Gate::authorize('createAdendo', $evolucao);

        $this->validate([
            'justificativa_adendo' => ['required', 'string', 'min:5'],
            'texto_adendo' => ['required', 'string', 'min:5'],
            'pin_adendo' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        $evolucaoService->registrarAdendo(
            user: auth()->user(),
            evolucao: $evolucao,
            justificativa: $this->justificativa_adendo,
            textoAdendo: $this->texto_adendo,
            pinAssinatura: $this->pin_adendo,
        );

        $this->modalAdendoAberto = false;
        $this->reset(['evolucaoAdendoId', 'justificativa_adendo', 'texto_adendo', 'pin_adendo']);
        $mensagem = 'Termo de adendo retificador vinculado à evolução clínica.';
        session()->flash('sucesso_pep', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Adendo registrado', mensagem: $mensagem);
    }

    public function abrirModalDiagnostico(): void
    {
        $paciente = Paciente::findOrFail($this->pacienteId);
        Gate::authorize('updateClinicalData', $paciente);

        $this->resetValidation();
        $this->diag_cid_principal = (string) ($paciente->cid_principal ?? '');
        $this->diag_cid_secundario = (string) ($paciente->cid_secundario ?? '');
        $this->diag_modalidades = is_array($paciente->modalidades_reabilitacao) ? $paciente->modalidades_reabilitacao : [];
        $this->modalDiagnosticoAberto = true;
    }

    public function fecharModalDiagnostico(): void
    {
        $this->modalDiagnosticoAberto = false;
        $this->resetValidation();
    }

    public function salvarDiagnostico(): void
    {
        $paciente = Paciente::findOrFail($this->pacienteId);
        Gate::authorize('updateClinicalData', $paciente);

        $this->validate([
            'diag_cid_principal' => ['required', 'string', 'max:10'],
            'diag_cid_secundario' => ['nullable', 'string', 'max:10'],
            'diag_modalidades' => ['nullable', 'array'],
            'diag_modalidades.*' => ['string', 'in:FISICA,INTELECTUAL,AUDITIVA,VISUAL'],
        ]);

        $paciente->update([
            'cid_principal' => strtoupper(trim($this->diag_cid_principal)),
            'cid_secundario' => $this->diag_cid_secundario !== '' ? strtoupper(trim($this->diag_cid_secundario)) : null,
            'modalidades_reabilitacao' => $this->diag_modalidades !== [] ? $this->diag_modalidades : null,
        ]);

        $this->modalDiagnosticoAberto = false;
        $mensagem = 'Diagnóstico clínico (CID-10) e modalidades de reabilitação atualizados com sucesso.';
        session()->flash('sucesso_pep', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Diagnóstico atualizado', mensagem: $mensagem);
    }

    public function abrirProntuarioAtendimento(int $agendamentoId): void
    {
        $agendamento = Agendamento::with(['paciente', 'sala', 'profissionais'])->findOrFail($agendamentoId);

        $profissional = auth()->user()?->profissional;
        $ehParticipante = $profissional !== null && $agendamento->profissionais->contains('id', $profissional->id);
        $ehCoordenacao = auth()->user()?->hasRole('coordenacao');

        abort_unless($ehParticipante || $ehCoordenacao, 403, 'Acesso não autorizado para este agendamento.');

        Gate::authorize('viewProntuario', $agendamento->paciente);

        $this->pacienteId = (int) $agendamento->paciente_id;
        $this->agendamento_id = (int) $agendamento->id;
        $this->pesquisaPaciente = '';
        $this->abaPep = 'prontuario';
        $this->reset(['queixa_estado_atual', 'conduta_terapeutica', 'intercorrencias', 'pin_assinatura']);
        $this->resetValidation();

        if ($profissional?->cbo_padrao) {
            $cbo = $profissional->cbo_padrao;
            $procedimentoCompativel = ProcedimentoSigtap::query()
                ->ativos()
                ->whereJsonContains('cbos_permitidos', $cbo)
                ->first();

            if ($procedimentoCompativel) {
                $this->procedimento_id = (int) $procedimentoCompativel->id;
            }
        }

        if ($this->procedimento_id === null || $this->procedimento_id === 0) {
            $primeiroAtivo = ProcedimentoSigtap::query()->ativos()->first();
            if ($primeiroAtivo) {
                $this->procedimento_id = (int) $primeiroAtivo->id;
            }
        }

        $this->dispatch(
            'toast',
            tipo: 'sucesso',
            titulo: 'Prontuário aberto',
            mensagem: "Paciente {$agendamento->paciente->nome} carregado para atendimento."
        );
    }

    public function chamarPaciente(int $agendamentoId, ChamadaPacienteService $chamadaService): void
    {
        $agendamento = Agendamento::with(['paciente', 'sala', 'profissionais'])->findOrFail($agendamentoId);

        try {
            $chamada = $chamadaService->chamarPaciente($agendamento, auth()->user());

            $this->dispatch(
                'toast',
                tipo: 'sucesso',
                titulo: 'Paciente chamado na TV',
                mensagem: "Chamada enviada para o painel: {$chamada->paciente_nome} ➔ {$chamada->sala_nome}."
            );
        } catch (\Throwable $e) {
            $this->dispatch(
                'toast',
                tipo: 'erro',
                titulo: 'Não foi possível chamar o paciente',
                mensagem: $e->getMessage()
            );
        }
    }

    public function repetirChamada(int $agendamentoId, ChamadaPacienteService $chamadaService): void
    {
        try {
            $chamada = $chamadaService->repetirChamada($agendamentoId, auth()->user());

            $this->dispatch(
                'toast',
                tipo: 'alerta',
                titulo: 'Chamada repetida na TV',
                mensagem: "Repetição enviada para o painel: {$chamada->paciente_nome} ➔ {$chamada->sala_nome}."
            );
        } catch (\Throwable $e) {
            $this->dispatch(
                'toast',
                tipo: 'erro',
                titulo: 'Não foi possível repetir a chamada',
                mensagem: $e->getMessage()
            );
        }
    }

    // =========================================================================
    // FLUXO CENTRALIZADO DE NOVO ATENDIMENTO / AGENDAMENTO A PARTIR DO PEP
    // =========================================================================

    public function abrirModalNovoAgendamento(?int $pacienteId = null): void
    {
        Gate::authorize('create', Agendamento::class);

        $this->resetValidation();
        $this->alertaPaciente = null;

        $alvoPacienteId = $pacienteId ?? $this->pacienteId;
        $this->novo_paciente_id = $alvoPacienteId;

        $hoje = CarbonImmutable::now();
        if ($hoje->isWeekend()) {
            $hoje = $hoje->next(CarbonInterface::MONDAY);
        }
        $this->novo_data = $hoje->toDateString();
        $this->atualizarDiaSemanaNovoAgendamento();

        $this->novo_hora_inicio = '08:00';
        $this->novo_hora_fim = '08:45';
        $this->novo_tipo_atendimento = 'INDIVIDUAL';
        $this->novo_recorrente = false;
        $this->novo_semanas_recorrencia = 1;
        $this->novo_observacao = '';

        $primeiraSala = Sala::query()->where('ativo', true)->orderBy('nome')->first();
        $this->novo_sala_id = $primeiraSala?->id;

        $prof = auth()->user()?->profissional;
        if ($prof !== null && $prof->ativo) {
            $this->novo_profissionais = [$prof->id];
        } else {
            $primeiroProf = Profissional::query()->where('ativo', true)->orderBy('nome')->first();
            $this->novo_profissionais = $primeiroProf ? [$primeiroProf->id] : [];
        }

        if ($alvoPacienteId !== null) {
            $paciente = Paciente::find($alvoPacienteId);
            if ($paciente !== null) {
                $service = app(AgendamentoService::class);
                $this->alertaPaciente = $service->validarDadosCadastraisPaciente($paciente);
            }
        }

        $this->modalNovoAgendamentoAberto = true;
    }

    public function updatedNovoData(): void
    {
        $this->atualizarDiaSemanaNovoAgendamento();
    }

    private function atualizarDiaSemanaNovoAgendamento(): void
    {
        if (! empty($this->novo_data)) {
            try {
                $data = CarbonImmutable::parse($this->novo_data);
                $diasMap = [
                    1 => 'SEGUNDA',
                    2 => 'TERCA',
                    3 => 'QUARTA',
                    4 => 'QUINTA',
                    5 => 'SEXTA',
                    6 => 'SABADO',
                    7 => 'DOMINGO',
                ];
                $this->novo_dia_semana = $diasMap[$data->dayOfWeekIso] ?? 'SEGUNDA';
            } catch (\Throwable) {
                $this->novo_dia_semana = 'SEGUNDA';
            }
        }
    }

    public function updatedNovoPacienteId(): void
    {
        if ($this->novo_paciente_id) {
            $paciente = Paciente::find($this->novo_paciente_id);
            $service = app(AgendamentoService::class);
            $this->alertaPaciente = $service->validarDadosCadastraisPaciente($paciente);
        } else {
            $this->alertaPaciente = null;
        }
    }

    public function fecharModalNovoAgendamento(): void
    {
        $this->modalNovoAgendamentoAberto = false;
        $this->resetValidation();
        $this->alertaPaciente = null;
    }

    public function salvarNovoAgendamento(AgendamentoService $agendamentoService): void
    {
        Gate::authorize('create', Agendamento::class);

        $this->validate([
            'novo_paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'novo_sala_id' => ['required', 'integer', 'exists:salas,id'],
            'novo_data' => ['required', 'date'],
            'novo_hora_inicio' => ['required', 'date_format:H:i'],
            'novo_hora_fim' => ['required', 'date_format:H:i', 'after:novo_hora_inicio'],
            'novo_dia_semana' => ['required', 'in:SEGUNDA,TERCA,QUARTA,QUINTA,SEXTA'],
            'novo_tipo_atendimento' => ['required', 'in:INDIVIDUAL,COMPARTILHADO,TRABALHO_INTERNO'],
            'novo_profissionais' => ['required', 'array', 'min:1'],
            'novo_profissionais.*' => ['integer', 'exists:profissionais,id'],
            'novo_recorrente' => ['boolean'],
            'novo_semanas_recorrencia' => ['integer', 'min:1', 'max:12'],
            'novo_observacao' => ['nullable', 'string', 'max:255'],
        ], [
            'novo_paciente_id.required' => 'É obrigatório selecionar um paciente para o agendamento.',
            'novo_sala_id.required' => 'Selecione uma sala ou espaço para o agendamento.',
            'novo_profissionais.required' => 'Selecione ao menos um profissional para o agendamento.',
            'novo_profissionais.min' => 'Selecione ao menos um profissional para o agendamento.',
        ]);

        $paciente = Paciente::find($this->novo_paciente_id);
        $pendencia = $agendamentoService->validarDadosCadastraisPaciente($paciente);
        if ($pendencia !== null) {
            $this->alertaPaciente = $pendencia;
            $this->addError('novo_paciente_id', $pendencia['mensagem']);
            $this->dispatch('toast', tipo: 'alerta', titulo: $pendencia['titulo'], mensagem: $pendencia['mensagem']);

            return;
        }

        $tipoAtendimentoFinal = count($this->novo_profissionais) > 1 && $this->novo_tipo_atendimento === 'INDIVIDUAL'
            ? 'COMPARTILHADO'
            : $this->novo_tipo_atendimento;

        try {
            $agendamentoService->criarGrade(
                dados: [
                    'paciente_id' => $this->novo_paciente_id,
                    'sala_id' => $this->novo_sala_id,
                    'data_atendimento' => $this->novo_data,
                    'hora_inicio' => $this->novo_hora_inicio,
                    'hora_fim' => $this->novo_hora_fim,
                    'dia_semana' => $this->novo_dia_semana,
                    'tipo_atendimento' => $tipoAtendimentoFinal,
                    'status' => 'AGENDADO',
                    'recorrente' => $this->novo_recorrente,
                    'observacao' => $this->novo_observacao !== '' ? $this->novo_observacao : null,
                ],
                profissionaisIds: $this->novo_profissionais,
                semanasRecorrencia: $this->novo_recorrente ? $this->novo_semanas_recorrencia : 1,
            );
        } catch (ValidationException $e) {
            $erros = $e->errors();
            foreach ($erros as $campo => $mensagens) {
                $campoMapeado = match ($campo) {
                    'paciente_id' => 'novo_paciente_id',
                    'sala_id' => 'novo_sala_id',
                    'data_atendimento' => 'novo_data',
                    'hora_inicio' => 'novo_hora_inicio',
                    'hora_fim' => 'novo_hora_fim',
                    'dia_semana' => 'novo_dia_semana',
                    'tipo_atendimento' => 'novo_tipo_atendimento',
                    'profissionais' => 'novo_profissionais',
                    default => $campo,
                };
                foreach ((array) $mensagens as $msg) {
                    $this->addError($campoMapeado, $msg);
                }
            }

            return;
        }

        $pacienteAgendadoId = $this->novo_paciente_id;
        $this->modalNovoAgendamentoAberto = false;
        $this->resetValidation();

        // Retorna ao prontuário do paciente se apropriado
        $this->selecionarPaciente($pacienteAgendadoId);
        $this->abaPep = 'prontuario';

        $mensagem = 'Atendimento agendado com sucesso e integrado à grade operacional.';
        session()->flash('sucesso_pep', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Agendamento criado', mensagem: $mensagem);
    }

    public function render(): View
    {
        $pacienteSelecionado = $this->pacienteId !== null
            ? Paciente::find($this->pacienteId)
            : null;

        if ($pacienteSelecionado !== null) {
            Gate::authorize('viewProntuario', $pacienteSelecionado);
        }

        $evolucoes = $pacienteSelecionado !== null
            ? EvolucaoClinica::query()
                ->with(['profissional', 'agendamento.sala', 'producaoBpai.procedimento', 'adendos.profissional'])
                ->where('paciente_id', $pacienteSelecionado->id)
                ->orderByDesc('assinado_em')
                ->get()
            : collect();

        $agendamentosPaciente = $pacienteSelecionado !== null
            ? Agendamento::query()
                ->with(['sala', 'profissionais', 'chamadas', 'evolucoes'])
                ->where('paciente_id', $pacienteSelecionado->id)
                ->whereIn('status', ['AGENDADO', 'PRESENTE', 'ATENDIDO'])
                ->orderByDesc('data_atendimento')
                ->orderBy('hora_inicio')
                ->limit(20)
                ->get()
            : collect();

        // Paginação do histórico e atendimentos futuros do paciente selecionado
        $atendimentosPacientePaginados = $pacienteSelecionado !== null
            ? Agendamento::query()
                ->with(['sala', 'profissionais', 'chamadas', 'evolucoes'])
                ->where('paciente_id', $pacienteSelecionado->id)
                ->orderByDesc('data_atendimento')
                ->orderByDesc('hora_inicio')
                ->paginate(10, ['*'], 'pageAtendimentosPaciente')
            : null;

        $procedimentos = ProcedimentoSigtap::query()->ativos()->orderBy('codigo')->get();

        $profissional = auth()->user()?->profissional;
        $filaAtendimentosHoje = collect();

        if ($profissional !== null) {
            $hoje = CarbonImmutable::now()->toDateString();
            $filaAtendimentosHoje = Agendamento::query()
                ->whereDate('data_atendimento', $hoje)
                ->whereHas('profissionais', fn ($q) => $q->where('profissionais.id', $profissional->id))
                ->where(function ($q): void {
                    $q->whereIn('status', ['PRESENTE', 'ATENDIDO'])
                        ->orWhereNotNull('chegada_em');
                })
                ->with([
                    'paciente:id,nome,cns,cpf,data_nascimento,cid_principal',
                    'sala:id,nome',
                    'profissionais:id,nome,especialidade',
                    'evolucoes:id,agendamento_id,profissional_id,assinado_em',
                    'chamadas',
                ])
                ->orderBy('hora_inicio')
                ->get()
                ->map(function (Agendamento $ag) use ($profissional): array {
                    $evolucaoAssinada = $ag->evolucoes->first(
                        fn ($ev): bool => (int) $ev->profissional_id === (int) $profissional->id && $ev->assinado_em !== null
                    );

                    $estadoFila = match (true) {
                        $evolucaoAssinada !== null => 'EVOLUCAO_REGISTRADA',
                        $ag->chamadas->isNotEmpty() => 'CHAMADO',
                        default => 'AGUARDANDO',
                    };

                    return [
                        'agendamento' => $ag,
                        'estado_fila' => $estadoFila,
                        'evolucao_assinada' => $evolucaoAssinada,
                        'ultima_chamada' => $ag->chamadas->sortByDesc('id')->first(),
                    ];
                });
        }

        // Consulta de "Meus Atendimentos" com filtros de período e status
        $hojeStr = CarbonImmutable::now()->toDateString();
        $meusAtendimentosQuery = Agendamento::query()
            ->with(['paciente', 'sala', 'profissionais', 'evolucoes']);

        if ($profissional !== null) {
            $meusAtendimentosQuery->whereHas('profissionais', fn ($q) => $q->where('profissionais.id', $profissional->id));
        }

        match ($this->filtroPeriodo) {
            'hoje' => $meusAtendimentosQuery->whereDate('data_atendimento', $hojeStr),
            'proximos' => $meusAtendimentosQuery->whereDate('data_atendimento', '>', $hojeStr),
            'anteriores' => $meusAtendimentosQuery->whereDate('data_atendimento', '<', $hojeStr),
            default => null,
        };

        if ($this->filtroStatus !== 'todos') {
            $meusAtendimentosQuery->where('status', $this->filtroStatus);
        }

        if ($this->filtroPeriodo === 'anteriores') {
            $meusAtendimentosQuery->orderByDesc('data_atendimento')->orderByDesc('hora_inicio');
        } else {
            $meusAtendimentosQuery->orderBy('data_atendimento')->orderBy('hora_inicio');
        }

        $meusAtendimentos = $meusAtendimentosQuery->paginate(15, ['*'], 'pageMeusAtendimentos');

        // Salas e profissionais disponíveis para novo agendamento
        $salas = Sala::query()->where('ativo', true)->orderBy('nome')->get();
        $profissionaisLista = Profissional::query()->where('ativo', true)->orderBy('nome')->get();

        return view('livewire.prontuario-paciente', [
            'pacientesResultados' => $this->buscarPacientes(),
            'pacienteSelecionado' => $pacienteSelecionado,
            'evolucoes' => $evolucoes,
            'agendamentosPaciente' => $agendamentosPaciente,
            'atendimentosPacientePaginados' => $atendimentosPacientePaginados,
            'procedimentos' => $procedimentos,
            'profissionalAutenticado' => $profissional,
            'filaAtendimentosHoje' => $filaAtendimentosHoje,
            'meusAtendimentos' => $meusAtendimentos,
            'salas' => $salas,
            'profissionaisLista' => $profissionaisLista,
        ]);
    }
}
