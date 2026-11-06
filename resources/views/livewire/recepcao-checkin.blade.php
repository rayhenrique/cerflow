<div class="space-y-6">
    <!-- Barra de Filtros da Recepção -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Recepção & Check-in Diário</h2>
            <p class="text-xs text-slate-500">Controle de presença em 1 clique e monitoramento preventivo de absenteísmo</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs">
            <input type="date" wire:model.live="dataFiltro" class="rounded-lg border border-slate-300 px-3 py-2 font-medium">

            <select wire:model.live="statusFiltro" class="rounded-lg border border-slate-300 px-3 py-2 font-medium">
                <option value="">Todos os Status</option>
                <option value="AGENDADO">Agendado</option>
                <option value="PRESENTE">Presente</option>
                <option value="ATENDIDO">Atendido</option>
                <option value="FALTA_JUSTIFICADA">Falta Justificada</option>
                <option value="FALTA_INJUSTIFICADA">Falta Injustificada</option>
            </select>
        </div>
    </div>

    <!-- Lista Diária de Pacientes -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead class="bg-slate-50 font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3">Horário</th>
                    <th class="px-4 py-3">Paciente / CNS</th>
                    <th class="px-4 py-3">Sala / Profissionais</th>
                    <th class="px-4 py-3">Status Atual</th>
                    <th class="px-4 py-3 text-right">Check-in Rápido (1 Clique)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($agendamentos as $ag)
                    @php
                        $alerta = $alertasPorPaciente[$ag->paciente_id] ?? ['alerta' => false, 'motivo' => null];
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-mono font-semibold text-slate-800">
                            {{ substr((string) $ag->hora_inicio, 0, 5) }} - {{ substr((string) $ag->hora_fim, 0, 5) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ $ag->paciente?->nome }}</span>
                                @if($alerta['alerta'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-800 border border-red-300">
                                        ⚠ Alerta Absenteísmo ({{ $alerta['motivo'] }})
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] font-mono text-slate-500">CNS: {{ $ag->paciente?->cns }} • CID: {{ $ag->paciente?->cid_principal }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-800 block">{{ $ag->sala?->nome }}</span>
                            <span class="text-[11px] text-slate-500">
                                {{ $ag->profissionais->pluck('nome')->join(' + ') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                                {{ $ag->status === 'PRESENTE' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                {{ $ag->status === 'ATENDIDO' ? 'bg-teal-100 text-teal-800' : '' }}
                                {{ $ag->status === 'AGENDADO' ? 'bg-slate-100 text-slate-700' : '' }}
                                {{ str_starts_with($ag->status, 'FALTA') ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $ag->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-1">
                            <button type="button"
                                    wire:click="alterarStatus({{ $ag->id }}, 'PRESENTE')"
                                    class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-500">
                                Presente
                            </button>
                            <button type="button"
                                    wire:click="alterarStatus({{ $ag->id }}, 'FALTA_INJUSTIFICADA')"
                                    class="rounded-md bg-red-600 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-red-500">
                                Falta
                            </button>
                            <button type="button"
                                    wire:click="alterarStatus({{ $ag->id }}, 'FALTA_JUSTIFICADA')"
                                    class="rounded-md bg-amber-500 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-amber-400">
                                Falta Just.
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                            Nenhum agendamento localizado para a data e filtro selecionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

