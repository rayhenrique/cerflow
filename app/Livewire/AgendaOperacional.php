<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Services\AgendamentoService;
use App\Services\ChamadaPacienteService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Agenda Operacional — CER Digital')]
class AgendaOperacional extends Component
{
    #[Url]
    public string $aba = 'dia'; // 'dia', 'grade', 'mensal'

    #[Url]
    public string $data = '';

    #[Url]
    public string $periodo = 'TODOS'; // 'TODOS', 'MANHA', 'TARDE', 'NOITE'

    #[Url]
    public string $filtroProfissionalId = '';

    #[Url]
    public string $filtroSalaId = '';

    #[Url]
    public string $filtroStatus = '';

    // --- Aba Grade Semanal ---
    public string $diaSemana = 'SEGUNDA';

    public string $turnoGrade = 'MANHA';

    public ?string $semanaInicio = null;

    public string $visaoGrade = 'SALA'; // 'SALA', 'SEMANA_COMPLETA'

    public string $filtroSalaGrade = '';

    // --- Aba Mapa Mensal ---
    #[Url]
    public int $mes = 0;

    #[Url]
    public int $ano = 0;

    #[Url(as: 'esp_mensal')]
    public string $filtroEspecialidadeMensal = '';

    #[Url(as: 'prof_mensal')]
    public string $filtroProfissionalIdMensal = '';

    #[Url(as: 'turno_mensal')]
    public string $filtroTurnoMensal = '';

    // --- Modal Novo Agendamento ---
    public bool $modalNovoAgendamentoAberto = false;

    public ?int $novo_paciente_id = null;

    public string $pesquisaPaciente = '';

    /** @var ?array{id: int, nome: string, cpf: ?string, cns: ?string, data_nascimento: ?string} */
    public ?array $pacienteSelecionado = null;

    /** @var ?array{tipo: string, titulo: string, mensagem: string} */
    public ?array $alertaPaciente = null;

    public ?int $novo_sala_id = null;

    public string $novo_data = '';

    public string $novo_hora_inicio = '08:00';

    public string $novo_hora_fim = '08:30';

    public string $novo_dia_semana = 'SEGUNDA';

    public string $novo_tipo_atendimento = 'INDIVIDUAL';

    /** @var array<int, int|string> */
    public array $novo_profissionais = [];

    public bool $novo_recorrente = false;

    public int $novo_semanas_recorrencia = 4;

    public string $novo_observacao = '';

    public function mount(?string $data = null, ?string $aba = null): void
    {
        Gate::authorize('viewAny', Agendamento::class);
        abort_unless(
            auth()->check() && auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']),
            403
        );

        $abaInformada = $aba ?? request()->query('aba');
        if (in_array($abaInformada, ['dia', 'grade', 'mensal'], true)) {
            $this->aba = $abaInformada;
        } else {
            $this->aba = 'dia';
        }

        $dataInforme = $data ?? request()->query('data');
        if (is_string($dataInforme) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInforme) === 1) {
            try {
                $parsed = CarbonImmutable::parse($dataInforme);
                $this->data = $parsed->toDateString();
            } catch (\Throwable) {
                $this->data = CarbonImmutable::now()->toDateString();
            }
        } else {
            $this->data = CarbonImmutable::now()->toDateString();
        }

        $parsedData = CarbonImmutable::parse($this->data);
        $mapaDiaSemana = [
            1 => 'SEGUNDA',
            2 => 'TERCA',
            3 => 'QUARTA',
            4 => 'QUINTA',
            5 => 'SEXTA',
            6 => 'SEGUNDA',
            7 => 'SEGUNDA',
        ];
        $this->diaSemana = $mapaDiaSemana[$parsedData->dayOfWeekIso] ?? 'SEGUNDA';
        $this->semanaInicio = $parsedData->startOfWeek(CarbonImmutable::MONDAY)->toDateString();

        $mesQuery = (int) request()->query('mes', $this->mes);
        $anoQuery = (int) request()->query('ano', $this->ano);

        if ($mesQuery >= 1 && $mesQuery <= 12) {
            $this->mes = $mesQuery;
        } else {
            $this->mes = (int) $parsedData->format('n');
        }

        if ($anoQuery >= 2000 && $anoQuery <= 2100) {
            $this->ano = $anoQuery;
        } else {
            $this->ano = (int) $parsedData->format('Y');
        }

        $this->novo_data = $this->data;
        $this->novo_dia_semana = $this->diaSemana;

        if (request()->boolean('novo')) {
            $this->modalNovoAgendamentoAberto = true;
        }
    }

    public function definirAba(string $novaAba): void
    {
        if (in_array($novaAba, ['dia', 'grade', 'mensal'], true)) {
            $this->aba = $novaAba;
            if ($novaAba === 'grade') {
                $parsed = CarbonImmutable::parse($this->data);
                $this->semanaInicio = $parsed->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
                $mapa = [
                    1 => 'SEGUNDA',
                    2 => 'TERCA',
                    3 => 'QUARTA',
                    4 => 'QUINTA',
                    5 => 'SEXTA',
                    6 => 'SEGUNDA',
                    7 => 'SEGUNDA',
                ];
                $this->diaSemana = $mapa[$parsed->dayOfWeekIso] ?? 'SEGUNDA';
                $this->data = $this->calcularDataDoDiaDaSemana($this->diaSemana);
                $this->novo_data = $this->data;
                $this->novo_dia_semana = $this->diaSemana;
            } elseif ($novaAba === 'dia') {
                $this->sincronizarDataComGradeEMensal(CarbonImmutable::parse($this->data));
            }
        }
    }

    public function definirPeriodo(string $periodo): void
    {
        if (in_array($periodo, ['TODOS', 'MANHA', 'TARDE', 'NOITE'], true)) {
            $this->periodo = $periodo;
        }
    }

    public function diaAnterior(): void
    {
        $nova = CarbonImmutable::parse($this->data)->subDay();
        $this->data = $nova->toDateString();
        $this->sincronizarDataComGradeEMensal($nova);
    }

    public function proximoDia(): void
    {
        $nova = CarbonImmutable::parse($this->data)->addDay();
        $this->data = $nova->toDateString();
        $this->sincronizarDataComGradeEMensal($nova);
    }

    public function irParaHoje(): void
    {
        $hoje = CarbonImmutable::now();
        $this->data = $hoje->toDateString();
        $this->sincronizarDataComGradeEMensal($hoje);
    }

    public function updatedData(string $valor): void
    {
        try {
            $parsed = CarbonImmutable::parse($valor);
            $this->data = $parsed->toDateString();
            $this->sincronizarDataComGradeEMensal($parsed);
        } catch (\Throwable) {
            $this->data = CarbonImmutable::now()->toDateString();
        }
    }

    private function sincronizarDataComGradeEMensal(CarbonImmutable $data): void
    {
        $mapaDiaSemana = [
            1 => 'SEGUNDA',
            2 => 'TERCA',
            3 => 'QUARTA',
            4 => 'QUINTA',
            5 => 'SEXTA',
            6 => 'SEGUNDA',
            7 => 'SEGUNDA',
        ];
        $this->diaSemana = $mapaDiaSemana[$data->dayOfWeekIso] ?? 'SEGUNDA';
        $this->semanaInicio = $data->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
        $this->mes = (int) $data->format('n');
        $this->ano = (int) $data->format('Y');
        $this->novo_data = $this->data;
        $this->novo_dia_semana = $this->diaSemana;
    }

    // --- Ações de Fluxo e Recepção ---
    public function confirmarPresenca(int $agendamentoId): void
    {
        $this->alterarStatus($agendamentoId, 'PRESENTE');
    }

    public function alterarStatus(int $agendamentoId, string $novoStatus): void
    {
        $agendamento = Agendamento::findOrFail($agendamentoId);
        Gate::authorize('checkin', $agendamento);

        validator(
            ['status' => $novoStatus],
            ['status' => ['required', Rule::in(['AGENDADO', 'PRESENTE', 'ATENDIDO', 'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'CANCELADO'])]],
        )->validate();

        $statusAnterior = $agendamento->status;
        $dadosUpdate = ['status' => $novoStatus];
        if (in_array($novoStatus, ['PRESENTE', 'ATENDIDO'], true) && $agendamento->chegada_em === null) {
            $dadosUpdate['chegada_em'] = now();
        }

        $agendamento->update($dadosUpdate);

        if (auth()->check()) {
            LogAuditoria::create([
                'user_id' => auth()->id(),
                'acao' => $novoStatus === 'PRESENTE' ? 'CHECKIN_PRESENCA' : 'ALTERACAO_STATUS_AGENDAMENTO',
                'tabela_afetada' => 'agendamentos',
                'registro_id' => $agendamento->id,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'detalhes' => [
                    'agendamento_id' => $agendamento->id,
                    'paciente_id' => $agendamento->paciente_id,
                    'status_anterior' => $statusAnterior,
                    'novo_status' => $novoStatus,
                    'chegada_em' => $agendamento->chegada_em?->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
        }

        $tipoToast = match ($novoStatus) {
            'PRESENTE' => 'sucesso',
            'ATENDIDO' => 'sucesso',
            'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'CANCELADO' => 'alerta',
            default => 'info',
        };

        $tituloToast = match ($novoStatus) {
            'PRESENTE' => 'Presença confirmada',
            'ATENDIDO' => 'Atendimento concluído',
            'FALTA_JUSTIFICADA' => 'Falta justificada registrada',
            'FALTA_INJUSTIFICADA' => 'Falta injustificada registrada',
            'CANCELADO' => 'Agendamento cancelado',
            default => 'Status atualizado',
        };

        $this->dispatch(
            'toast',
            tipo: $tipoToast,
            titulo: $tituloToast,
            mensagem: "Agendamento #{$agendamento->id} alterado para {$novoStatus}."
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

    // --- Modal Novo Agendamento ---
    public function abrirModalNovoAgendamento(?int $salaId = null, ?string $horaInicio = null, ?string $data = null): void
    {
        Gate::authorize('create', Agendamento::class);

        $this->resetValidation();
        $this->limparPacienteSelecionado();

        $this->novo_sala_id = $salaId;
        $this->novo_hora_inicio = $horaInicio ?? '08:00';
        $this->novo_hora_fim = CarbonImmutable::createFromFormat('H:i', $this->novo_hora_inicio)->addMinutes(30)->format('H:i');

        if ($data !== null) {
            $this->novo_data = $data;
            $parsed = CarbonImmutable::parse($data);
            $mapa = [
                1 => 'SEGUNDA',
                2 => 'TERCA',
                3 => 'QUARTA',
                4 => 'QUINTA',
                5 => 'SEXTA',
                6 => 'SEGUNDA',
                7 => 'SEGUNDA',
            ];
            $this->novo_dia_semana = $mapa[$parsed->dayOfWeekIso] ?? 'SEGUNDA';
        } else {
            $this->novo_data = $this->data;
            $this->novo_dia_semana = $this->diaSemana;
        }

        $this->novo_profissionais = [];
        $this->novo_recorrente = false;
        $this->novo_semanas_recorrencia = 4;
        $this->novo_observacao = '';
        $this->novo_tipo_atendimento = 'INDIVIDUAL';

        $this->modalNovoAgendamentoAberto = true;
    }

    public function fecharModalNovoAgendamento(): void
    {
        $this->modalNovoAgendamentoAberto = false;
        $this->limparPacienteSelecionado();
        $this->resetValidation();
    }

    public function updatedNovoHoraInicio(string $valor): void
    {
        try {
            $this->novo_hora_fim = CarbonImmutable::createFromFormat('H:i', $valor)->addMinutes(30)->format('H:i');
        } catch (\Throwable) {
            // Ignora formato temporariamente incompleto
        }
    }

    public function updatedNovoData(string $valor): void
    {
        try {
            $parsed = CarbonImmutable::parse($valor);
            $mapa = [
                1 => 'SEGUNDA',
                2 => 'TERCA',
                3 => 'QUARTA',
                4 => 'QUINTA',
                5 => 'SEXTA',
                6 => 'SEGUNDA',
                7 => 'SEGUNDA',
            ];
            $this->novo_dia_semana = $mapa[$parsed->dayOfWeekIso] ?? 'SEGUNDA';
        } catch (\Throwable) {
            // Ignora
        }
    }

    public function selecionarPaciente(int $id): void
    {
        $paciente = Paciente::query()->ativos()->find($id, ['id', 'nome', 'cpf', 'cns', 'data_nascimento']);
        if ($paciente !== null) {
            $this->novo_paciente_id = (int) $paciente->id;
            $this->pacienteSelecionado = [
                'id' => (int) $paciente->id,
                'nome' => (string) $paciente->nome,
                'cpf' => $paciente->cpf ? $this->formatarCpf((string) $paciente->cpf) : null,
                'cns' => $paciente->cns,
                'data_nascimento' => $paciente->data_nascimento?->format('d/m/Y'),
            ];
            $this->pesquisaPaciente = '';
            $this->resetValidation('novo_paciente_id');

            // Validação cadastral imediata ao selecionar o paciente
            $service = app(AgendamentoService::class);
            $this->alertaPaciente = $service->validarDadosCadastraisPaciente($paciente);
            if ($this->alertaPaciente !== null) {
                $this->addError('novo_paciente_id', $this->alertaPaciente['mensagem']);
            }
        }
    }

    public function limparPacienteSelecionado(): void
    {
        $this->novo_paciente_id = null;
        $this->pacienteSelecionado = null;
        $this->alertaPaciente = null;
        $this->pesquisaPaciente = '';
        $this->resetValidation('novo_paciente_id');
    }

    public function revalidarPacienteSelecionado(): void
    {
        if ($this->novo_paciente_id === null) {
            return;
        }

        $paciente = Paciente::query()->ativos()->find($this->novo_paciente_id, ['id', 'nome', 'cpf', 'cns', 'data_nascimento']);
        if ($paciente === null) {
            $this->limparPacienteSelecionado();

            return;
        }

        $this->pacienteSelecionado = [
            'id' => (int) $paciente->id,
            'nome' => (string) $paciente->nome,
            'cpf' => $paciente->cpf ? $this->formatarCpf((string) $paciente->cpf) : null,
            'cns' => $paciente->cns,
            'data_nascimento' => $paciente->data_nascimento?->format('d/m/Y'),
        ];

        $service = app(AgendamentoService::class);
        $this->alertaPaciente = $service->validarDadosCadastraisPaciente($paciente);

        if ($this->alertaPaciente === null) {
            $this->resetValidation('novo_paciente_id');
            $this->dispatch('toast', tipo: 'sucesso', titulo: 'Cadastro Validado', mensagem: 'Os dados do paciente foram atualizados e estão regulares para agendamento.');
        } else {
            $this->addError('novo_paciente_id', $this->alertaPaciente['mensagem']);
            $this->dispatch('toast', tipo: 'alerta', titulo: $this->alertaPaciente['titulo'], mensagem: $this->alertaPaciente['mensagem']);
        }
    }

    /**
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

        // Revalidação dos dados essenciais do paciente no servidor no momento do clique em salvar
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

            if (isset($erros['paciente_id'])) {
                $msg = is_array($erros['paciente_id']) ? $erros['paciente_id'][0] : $erros['paciente_id'];
                $this->alertaPaciente = $agendamentoService->validarDadosCadastraisPaciente($paciente) ?? [
                    'tipo' => 'paciente_invalido',
                    'titulo' => 'Atenção: pendência cadastral do paciente',
                    'mensagem' => $msg,
                ];
            }

            return;
        }

        $this->modalNovoAgendamentoAberto = false;
        $this->limparPacienteSelecionado();
        $this->reset(['novo_profissionais', 'novo_observacao']);

        $mensagem = 'Agendamento registrado com sucesso.';
        session()->flash('sucesso', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Agendamento Realizado', mensagem: $mensagem);
    }

    // --- Navegação Grade Semanal ---
    public function mudarDiaSemana(string $dia): void
    {
        $diasPermitidos = ['SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA'];
        $diaUpper = strtoupper(trim($dia));
        if (! in_array($diaUpper, $diasPermitidos, true)) {
            return;
        }

        $this->diaSemana = $diaUpper;

        if (empty($this->semanaInicio)) {
            $baseData = ! empty($this->data) ? CarbonImmutable::parse($this->data) : CarbonImmutable::now();
            $this->semanaInicio = $baseData->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
        }

        $this->data = $this->calcularDataDoDiaDaSemana($this->diaSemana);

        $parsed = CarbonImmutable::parse($this->data);
        $this->mes = (int) $parsed->format('n');
        $this->ano = (int) $parsed->format('Y');
        $this->novo_data = $this->data;
        $this->novo_dia_semana = $this->diaSemana;
    }

    public function semanaAnterior(): void
    {
        $base = $this->semanaInicio !== null
            ? CarbonImmutable::parse($this->semanaInicio)->startOfWeek(CarbonImmutable::MONDAY)
            : (! empty($this->data) ? CarbonImmutable::parse($this->data)->startOfWeek(CarbonImmutable::MONDAY) : CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY));

        $novaSemana = $base->subWeek();
        $this->semanaInicio = $novaSemana->toDateString();
        $this->data = $this->calcularDataDoDiaDaSemana($this->diaSemana);

        $parsed = CarbonImmutable::parse($this->data);
        $this->mes = (int) $parsed->format('n');
        $this->ano = (int) $parsed->format('Y');
        $this->novo_data = $this->data;
        $this->novo_dia_semana = $this->diaSemana;
    }

    public function proximaSemana(): void
    {
        $base = $this->semanaInicio !== null
            ? CarbonImmutable::parse($this->semanaInicio)->startOfWeek(CarbonImmutable::MONDAY)
            : (! empty($this->data) ? CarbonImmutable::parse($this->data)->startOfWeek(CarbonImmutable::MONDAY) : CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY));

        $novaSemana = $base->addWeek();
        $this->semanaInicio = $novaSemana->toDateString();
        $this->data = $this->calcularDataDoDiaDaSemana($this->diaSemana);

        $parsed = CarbonImmutable::parse($this->data);
        $this->mes = (int) $parsed->format('n');
        $this->ano = (int) $parsed->format('Y');
        $this->novo_data = $this->data;
        $this->novo_dia_semana = $this->diaSemana;
    }

    public function alternarVisaoGrade(string $visao): void
    {
        if (in_array($visao, ['SALA', 'SEMANA_COMPLETA'], true)) {
            $this->visaoGrade = $visao;
        }
    }

    public function calcularDataDoDiaDaSemana(string $diaSemana): string
    {
        $mapaIso = [
            'SEGUNDA' => 1,
            'TERCA' => 2,
            'QUARTA' => 3,
            'QUINTA' => 4,
            'SEXTA' => 5,
        ];
        $diaUpper = strtoupper(trim($diaSemana));
        $isoTarget = $mapaIso[$diaUpper] ?? 1;

        $base = $this->semanaInicio !== null
            ? CarbonImmutable::parse($this->semanaInicio)->startOfWeek(CarbonImmutable::MONDAY)
            : (! empty($this->data) ? CarbonImmutable::parse($this->data)->startOfWeek(CarbonImmutable::MONDAY) : CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY));

        return $base->addDays($isoTarget - 1)->toDateString();
    }

    public function obterSlotsHorarioGrade(): array
    {
        $manha = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
        $tarde = ['13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];

        return match ($this->turnoGrade) {
            'MANHA' => $manha,
            'TARDE' => $tarde,
            default => array_merge($manha, $tarde),
        };
    }

    // --- Navegação Mapa Mensal ---
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

    public function selecionarDiaDoMes(string $dataStr, string $destinoAba = 'dia'): void
    {
        $this->data = $dataStr;
        $this->sincronizarDataComGradeEMensal(CarbonImmutable::parse($dataStr));
        $this->definirAba(in_array($destinoAba, ['dia', 'grade'], true) ? $destinoAba : 'dia');
    }

    public function limparFiltrosMensal(): void
    {
        $this->filtroEspecialidadeMensal = '';
        $this->filtroProfissionalIdMensal = '';
        $this->filtroTurnoMensal = '';
    }

    public function nomeMesPtBr(int $mes): string
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

    // --- Render Principal ---
    public function render(AgendamentoService $agendamentoService): View
    {
        Gate::authorize('viewAny', Agendamento::class);

        $salas = Sala::query()->ativas()->orderBy('nome')->get(['id', 'nome']);
        $profissionais = Profissional::query()->ativos()->orderBy('nome')->get(['id', 'nome', 'especialidade', 'cbo_padrao']);

        // 1. Dados da Agenda do Dia (carregados EXCLUSIVAMENTE se aba ativa for 'dia')
        $agendamentosDiaTodos = collect();
        $agendamentosDiaFiltrados = collect();
        $contadoresDia = [
            'total' => 0,
            'agendados' => 0,
            'presentes' => 0,
            'atendidos' => 0,
            'faltas' => 0,
            'cancelados' => 0,
            'manha' => 0,
            'tarde' => 0,
            'noite' => 0,
        ];
        $alertasAbsenteismo = [];

        if ($this->aba === 'dia') {
            $agendamentosDiaTodos = Agendamento::query()
                ->with([
                    'paciente:id,nome,cns,cpf,data_nascimento,cid_principal',
                    'sala:id,nome',
                    'profissionais:id,nome,especialidade',
                    'evolucoes:id,agendamento_id,profissional_id,assinado_em',
                    'chamadas',
                ])
                ->whereDate('data_atendimento', $this->data)
                ->orderBy('hora_inicio')
                ->get();

            // Contadores Globais do Dia
            $contadoresDia = [
                'total' => $agendamentosDiaTodos->count(),
                'agendados' => $agendamentosDiaTodos->where('status', 'AGENDADO')->count(),
                'presentes' => $agendamentosDiaTodos->where('status', 'PRESENTE')->count(),
                'atendidos' => $agendamentosDiaTodos->where('status', 'ATENDIDO')->count(),
                'faltas' => $agendamentosDiaTodos->whereIn('status', ['FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA'])->count(),
                'cancelados' => $agendamentosDiaTodos->where('status', 'CANCELADO')->count(),
                'manha' => $agendamentosDiaTodos->filter(fn ($ag) => $ag->hora_inicio >= '07:00' && $ag->hora_inicio < '13:00')->count(),
                'tarde' => $agendamentosDiaTodos->filter(fn ($ag) => $ag->hora_inicio >= '13:00' && $ag->hora_inicio < '18:00')->count(),
                'noite' => $agendamentosDiaTodos->filter(fn ($ag) => $ag->hora_inicio >= '18:00' && $ag->hora_inicio <= '22:00')->count(),
            ];

            // Filtros da Agenda do Dia
            $agendamentosDiaFiltrados = $agendamentosDiaTodos->filter(function (Agendamento $ag): bool {
                // Filtro por Período
                if ($this->periodo === 'MANHA') {
                    if ($ag->hora_inicio < '07:00' || $ag->hora_inicio >= '13:00') {
                        return false;
                    }
                } elseif ($this->periodo === 'TARDE') {
                    if ($ag->hora_inicio < '13:00' || $ag->hora_inicio >= '18:00') {
                        return false;
                    }
                } elseif ($this->periodo === 'NOITE') {
                    if ($ag->hora_inicio < '18:00' || $ag->hora_inicio > '22:00') {
                        return false;
                    }
                }

                // Filtro por Profissional
                if ($this->filtroProfissionalId !== '') {
                    $profId = (int) $this->filtroProfissionalId;
                    if (! $ag->profissionais->contains('id', $profId)) {
                        return false;
                    }
                }

                // Filtro por Sala
                if ($this->filtroSalaId !== '') {
                    if ((int) $ag->sala_id !== (int) $this->filtroSalaId) {
                        return false;
                    }
                }

                // Filtro por Status
                if ($this->filtroStatus !== '') {
                    if ($ag->status !== $this->filtroStatus) {
                        return false;
                    }
                }

                return true;
            })->values();

            // Alertas de absenteísmo em lote para evitar N+1
            $pacientesIds = $agendamentosDiaFiltrados->pluck('paciente_id')->unique()->filter()->map(fn ($id): int => (int) $id)->values()->all();
            $alertasAbsenteismo = $agendamentoService->verificarAlertasAbsenteismoEmLote($pacientesIds);
        }

        // 2. Dados da Aba Grade Semanal
        $gradeIndexada = [];
        $gradeSemanaCompleta = [];
        $semanaInicioCarbon = $this->semanaInicio !== null
            ? CarbonImmutable::parse($this->semanaInicio)->startOfWeek(CarbonImmutable::MONDAY)
            : (! empty($this->data) ? CarbonImmutable::parse($this->data)->startOfWeek(CarbonImmutable::MONDAY) : CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY));
        $semanaFimCarbon = $semanaInicioCarbon->addDays(4); // Sexta-feira

        $diasSemanaInfo = [
            'SEGUNDA' => [
                'sigla' => 'SEGUNDA',
                'label' => 'Segunda',
                'data' => $semanaInicioCarbon->addDays(0)->toDateString(),
                'dia_mes' => $semanaInicioCarbon->addDays(0)->format('d/m'),
                'dia_numero' => $semanaInicioCarbon->addDays(0)->format('d'),
            ],
            'TERCA' => [
                'sigla' => 'TERCA',
                'label' => 'Terça',
                'data' => $semanaInicioCarbon->addDays(1)->toDateString(),
                'dia_mes' => $semanaInicioCarbon->addDays(1)->format('d/m'),
                'dia_numero' => $semanaInicioCarbon->addDays(1)->format('d'),
            ],
            'QUARTA' => [
                'sigla' => 'QUARTA',
                'label' => 'Quarta',
                'data' => $semanaInicioCarbon->addDays(2)->toDateString(),
                'dia_mes' => $semanaInicioCarbon->addDays(2)->format('d/m'),
                'dia_numero' => $semanaInicioCarbon->addDays(2)->format('d'),
            ],
            'QUINTA' => [
                'sigla' => 'QUINTA',
                'label' => 'Quinta',
                'data' => $semanaInicioCarbon->addDays(3)->toDateString(),
                'dia_mes' => $semanaInicioCarbon->addDays(3)->format('d/m'),
                'dia_numero' => $semanaInicioCarbon->addDays(3)->format('d'),
            ],
            'SEXTA' => [
                'sigla' => 'SEXTA',
                'label' => 'Sexta',
                'data' => $semanaInicioCarbon->addDays(4)->toDateString(),
                'dia_mes' => $semanaInicioCarbon->addDays(4)->format('d/m'),
                'dia_numero' => $semanaInicioCarbon->addDays(4)->format('d'),
            ],
        ];

        $dataRefGrade = $this->calcularDataDoDiaDaSemana($this->diaSemana);

        if ($this->aba === 'grade') {
            if ($this->visaoGrade === 'SEMANA_COMPLETA') {
                $querySemana = Agendamento::query()
                    ->with(['paciente:id,nome', 'profissionais:id,nome', 'sala:id,nome'])
                    ->whereBetween('data_atendimento', [$semanaInicioCarbon->toDateString(), $semanaFimCarbon->toDateString()])
                    ->where('status', '!=', 'CANCELADO');

                if ($this->filtroSalaGrade !== '') {
                    $querySemana->where('sala_id', (int) $this->filtroSalaGrade);
                }

                foreach ($querySemana->get() as $ag) {
                    $dataStr = $ag->data_atendimento->toDateString();
                    $horaSlot = substr((string) $ag->hora_inicio, 0, 5);
                    $gradeSemanaCompleta[$dataStr][$horaSlot][] = $ag;
                }
            } else {
                $queryGrade = Agendamento::query()
                    ->with(['paciente:id,nome', 'profissionais:id,nome', 'sala:id,nome'])
                    ->whereDate('data_atendimento', $dataRefGrade)
                    ->where('status', '!=', 'CANCELADO');

                if ($this->filtroSalaGrade !== '') {
                    $queryGrade->where('sala_id', (int) $this->filtroSalaGrade);
                }

                foreach ($queryGrade->get() as $ag) {
                    $horaSlot = substr((string) $ag->hora_inicio, 0, 5);
                    $gradeIndexada[$ag->sala_id][$horaSlot][] = $ag;
                }
            }
        }

        // 3. Dados da Aba Mapa Mensal
        $metricasMes = [
            'total' => 0,
            'realizados' => 0,
            'agendados' => 0,
            'faltas' => 0,
            'faltasJustificadas' => 0,
            'faltasInjustificadas' => 0,
            'cancelados' => 0,
            'diasComAgenda' => 0,
            'mediaDiaria' => 0.0,
            'taxaComparecimento' => 0,
            'taxaAbsenteismo' => 0,
        ];
        $diasCalendarioMensal = [];
        $especialidadesDisponiveis = collect();
        $profissionaisDisponiveisMensal = collect();
        $inicioMes = CarbonImmutable::create($this->ano, $this->mes, 1)->startOfMonth();
        $fimMes = $inicioMes->endOfMonth();

        if ($this->aba === 'mensal') {
            $inicioCalendario = $inicioMes->startOfWeek(CarbonImmutable::MONDAY);
            $fimCalendario = $fimMes->endOfWeek(CarbonImmutable::SUNDAY);

            $queryMensal = Agendamento::query()
                ->whereBetween('data_atendimento', [$inicioCalendario->toDateString(), $fimCalendario->toDateString()]);

            if ($this->filtroEspecialidadeMensal !== '') {
                $queryMensal->whereHas('profissionais', function ($q): void {
                    $q->where('especialidade', $this->filtroEspecialidadeMensal);
                });
            }

            if ($this->filtroProfissionalIdMensal !== '') {
                $profId = (int) $this->filtroProfissionalIdMensal;
                $queryMensal->whereHas('profissionais', function ($q) use ($profId): void {
                    $q->where('profissionais.id', $profId);
                });
            }

            if ($this->filtroTurnoMensal === 'MANHA') {
                $queryMensal->whereTime('hora_inicio', '<', '12:00:00');
            } elseif ($this->filtroTurnoMensal === 'TARDE') {
                $queryMensal->whereTime('hora_inicio', '>=', '12:00:00');
            }

            $agendamentosMes = $queryMensal->get([
                'id',
                'data_atendimento',
                'hora_inicio',
                'hora_fim',
                'status',
                'tipo_atendimento',
                'sala_id',
            ]);

            $metricasMes = $agendamentoService->calcularMetricasMes($agendamentosMes, $inicioMes, $fimMes);
            $diasCalendarioMensal = $agendamentoService->montarDiasCalendario($agendamentosMes, $inicioCalendario, $fimCalendario, $this->mes);

            $especialidadesDisponiveis = Profissional::query()
                ->ativos()
                ->whereNotNull('especialidade')
                ->where('especialidade', '!=', '')
                ->distinct()
                ->orderBy('especialidade')
                ->pluck('especialidade');

            $profissionaisDisponiveisMensal = Profissional::query()
                ->ativos()
                ->orderBy('nome')
                ->get(['id', 'nome', 'especialidade']);
        }

        return view('livewire.agenda-operacional', [
            'salas' => $salas,
            'profissionais' => $profissionais,
            'agendamentosDia' => $agendamentosDiaFiltrados,
            'contadoresDia' => $contadoresDia,
            'alertasAbsenteismo' => $alertasAbsenteismo,
            'gradeIndexada' => $gradeIndexada,
            'gradeSemanaCompleta' => $gradeSemanaCompleta,
            'diasSemanaInfo' => $diasSemanaInfo,
            'semanaInicioCarbon' => $semanaInicioCarbon,
            'semanaFimCarbon' => $semanaFimCarbon,
            'slotsGrade' => $this->obterSlotsHorarioGrade(),
            'dataRefGrade' => $dataRefGrade,
            'metricasMes' => $metricasMes,
            'diasCalendarioMensal' => $diasCalendarioMensal,
            'especialidadesDisponiveis' => $especialidadesDisponiveis,
            'profissionaisDisponiveisMensal' => $profissionaisDisponiveisMensal,
            'mesNome' => $this->nomeMesPtBr($this->mes),
            'inicioMes' => $inicioMes,
            'fimMes' => $fimMes,
            'pacientesResultados' => $this->modalNovoAgendamentoAberto ? $this->buscarPacientes() : [],
        ]);
    }
}
