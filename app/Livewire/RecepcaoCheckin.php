<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Recepção e Check-in — CER Flow')]
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

        $agendamento->update(['status' => $novoStatus]);
    }

    public function render(AgendamentoService $agendamentoService): View
    {
        $agendamentos = Agendamento::query()
            ->with(['paciente', 'sala', 'profissionais'])
            ->whereDate('data_atendimento', $this->dataFiltro)
            ->when($this->statusFiltro !== '', fn ($q) => $q->where('status', $this->statusFiltro))
            ->orderBy('hora_inicio')
            ->get();

        $alertasPorPaciente = [];
        foreach ($agendamentos->pluck('paciente_id')->unique()->filter() as $pacienteId) {
            $alertasPorPaciente[(int) $pacienteId] = $agendamentoService->verificarAlertaAbsenteismo((int) $pacienteId);
        }

        return view('livewire.recepcao-checkin', [
            'agendamentos' => $agendamentos,
            'alertasPorPaciente' => $alertasPorPaciente,
        ]);
    }
}
