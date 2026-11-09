<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\LogAuditoria;
use App\Services\AgendamentoService;
use App\Services\ChamadaPacienteService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Recepção e Check-in — CER Digital')]
class RecepcaoCheckin extends Component
{
    public string $dataFiltro = '';

    public string $statusFiltro = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Agendamento::class);
        $this->dataFiltro = CarbonImmutable::now()->toDateString();
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
            'PRESENTE', 'ATENDIDO' => 'sucesso',
            'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'CANCELADO' => 'alerta',
            default => 'info',
        };

        $this->dispatch(
            'toast',
            tipo: $tipoToast,
            titulo: 'Status atualizado na Recepção',
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

    public function render(AgendamentoService $agendamentoService): View
    {
        $agendamentos = Agendamento::query()
            ->with([
                'paciente:id,nome,cns,cid_principal',
                'sala:id,nome',
                'profissionais:id,nome',
                'chamadas',
            ])
            ->whereDate('data_atendimento', $this->dataFiltro)
            ->when($this->statusFiltro !== '', fn ($q) => $q->where('status', $this->statusFiltro))
            ->orderBy('hora_inicio')
            ->get();

        $pacientesIds = $agendamentos->pluck('paciente_id')->unique()->filter()->map(fn ($id): int => (int) $id)->values()->all();
        $alertasPorPaciente = $agendamentoService->verificarAlertasAbsenteismoEmLote($pacientesIds);

        return view('livewire.recepcao-checkin', [
            'agendamentos' => $agendamentos,
            'alertasPorPaciente' => $alertasPorPaciente,
        ]);
    }
}
