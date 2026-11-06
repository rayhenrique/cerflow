<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\EvolucaoClinica;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Services\EvolucaoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Prontuário Multiprofissional (PEP) — CER Flow')]
class ProntuarioPaciente extends Component
{
    public ?int $pacienteId = null;

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

    public function mount(?int $paciente = null): void
    {
        Gate::authorize('viewAny', EvolucaoClinica::class);

        if ($paciente !== null) {
            $this->pacienteId = $paciente;
        } else {
            $this->pacienteId = Paciente::query()->ativos()->orderBy('nome')->value('id');
        }
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

        $this->pin_assinatura = '';
        $this->modalPinAberto = true;
    }

    public function confirmarAssinaturaEvolucao(EvolucaoService $evolucaoService): void
    {
        Gate::authorize('create', EvolucaoClinica::class);
        $this->validate();

        $profissional = auth()->user()?->profissional;
        $agendamento = Agendamento::findOrFail($this->agendamento_id);
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
        session()->flash('sucesso_pep', 'Evolução clínica assinada eletronicamente (SHA-256) e produção BPA-i gerada.');
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
        session()->flash('sucesso_pep', 'Termo de adendo retificador vinculado à evolução clínica.');
    }

    public function render(): View
    {
        $pacientes = Paciente::query()->ativos()->orderBy('nome')->get();
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
                ->with(['sala', 'profissionais'])
                ->where('paciente_id', $pacienteSelecionado->id)
                ->whereIn('status', ['AGENDADO', 'PRESENTE', 'ATENDIDO'])
                ->orderByDesc('data_atendimento')
                ->limit(20)
                ->get()
            : collect();

        $procedimentos = ProcedimentoSigtap::query()->ativos()->orderBy('codigo')->get();

        return view('livewire.prontuario-paciente', [
            'pacientes' => $pacientes,
            'pacienteSelecionado' => $pacienteSelecionado,
            'evolucoes' => $evolucoes,
            'agendamentosPaciente' => $agendamentosPaciente,
            'procedimentos' => $procedimentos,
        ]);
    }
}
