<div class="space-y-4 sm:space-y-6">
    <!-- Barra de Filtros e Ações da Recepção -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                <span>Recepção, Check-in & Painel de Chamadas</span>
            </h2>
            <p class="text-xs text-slate-500">Controle de presença em 1 clique, chamada para TV e monitoramento preventivo de absenteísmo</p>
        </div>

        <div class="grid grid-cols-1 sm:flex sm:flex-wrap items-center gap-2.5 text-xs">
            <input type="date" wire:model.live="dataFiltro" class="w-full sm:w-auto rounded-lg border border-slate-300 px-3 py-2 font-medium">

            <select wire:model.live="statusFiltro" class="w-full sm:w-auto rounded-lg border border-slate-300 px-3 py-2 font-medium">
                <option value="">Todos os Status</option>
                <option value="AGENDADO">Agendado</option>
                <option value="PRESENTE">Presente</option>
                <option value="ATENDIDO">Atendido</option>
                <option value="FALTA_JUSTIFICADA">Falta Justificada</option>
                <option value="FALTA_INJUSTIFICADA">Falta Injustificada</option>
            </select>

            <a href="{{ route('painel.chamadas') }}" target="_blank"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-teal-300 bg-teal-50 px-3.5 py-2 font-bold text-teal-800 hover:bg-teal-100 transition shadow-xs cursor-pointer"
               title="Abrir o Painel de Chamadas para TV em nova janela">
                <svg class="h-4 w-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Painel TV</span>
            </a>
        </div>
    </div>

    <!-- Lista Diária de Pacientes -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead class="bg-slate-50 font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap">Horário</th>
                    <th class="px-4 py-3">Paciente / CNS</th>
                    <th class="px-4 py-3">Sala / Profissionais</th>
                    <th class="px-4 py-3 whitespace-nowrap">Status Atual</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">Chamada TV</th>
                    <th class="px-4 py-3 text-right whitespace-nowrap">Check-in Rápido</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($agendamentos as $ag)
                    @php
                        $alerta = $alertasPorPaciente[$ag->paciente_id] ?? ['alerta' => false, 'motivo' => null];
                        $qtdChamadas = $ag->chamadas->count();
                        $ultimaChamada = $ag->chamadas->sortByDesc('chamado_em')->first();
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-mono font-semibold text-slate-800 whitespace-nowrap">
                            {{ substr((string) $ag->hora_inicio, 0, 5) }} - {{ substr((string) $ag->hora_fim, 0, 5) }}
                        </td>
                        <td class="px-4 py-3 min-w-[200px]">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-semibold text-slate-900">{{ $ag->paciente?->nome }}</span>
                                @if($alerta['alerta'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800 border border-red-300">
                                        ⚠ Alerta Absenteísmo ({{ $alerta['motivo'] }})
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] font-mono text-slate-500 block">CNS: {{ $ag->paciente?->cns ?? 'Pendente' }} • CID: {{ $ag->paciente?->cid_principal ?? 'Pendente' }}</span>
                        </td>
                        <td class="px-4 py-3 min-w-[160px]">
                            <span class="font-medium text-slate-800 block">{{ $ag->sala?->nome }}</span>
                            <span class="text-[11px] text-slate-500">
                                {{ $ag->profissionais->pluck('nome')->join(' + ') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                                {{ $ag->status === 'PRESENTE' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                {{ $ag->status === 'ATENDIDO' ? 'bg-teal-100 text-teal-800' : '' }}
                                {{ $ag->status === 'AGENDADO' ? 'bg-slate-100 text-slate-700' : '' }}
                                {{ str_starts_with($ag->status, 'FALTA') ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $ag->status }}
                            </span>
                        </td>

                        <!-- Coluna de Chamada para TV -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($ag->status === 'PRESENTE')
                                @if($qtdChamadas === 0)
                                    <button type="button"
                                            wire:click="chamarPaciente({{ $ag->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-2.5 py-1.5 text-[11px] font-bold text-white hover:bg-teal-700 transition shadow-xs cursor-pointer"
                                            title="Chamar paciente no Painel da TV com aviso sonoro e voz">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        <span>Chamar TV</span>
                                    </button>
                                @else
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="rounded-md bg-teal-50 px-2 py-1 text-[10px] font-mono font-bold text-teal-800 border border-teal-200"
                                              title="Última chamada realizada às {{ $ultimaChamada?->chamado_em?->format('H:i') }}">
                                            Chamado ({{ $qtdChamadas }}x)
                                        </span>
                                        <button type="button"
                                                wire:click="repetirChamada({{ $ag->id }})"
                                                class="rounded-md bg-amber-500 px-2 py-1 text-[11px] font-bold text-white hover:bg-amber-600 transition shadow-xs cursor-pointer"
                                                title="Repetir a chamada na TV">
                                            Repetir
                                        </button>
                                    </div>
                                @endif
                            @elseif($qtdChamadas > 0)
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-mono text-slate-500">
                                    Chamado às {{ $ultimaChamada?->chamado_em?->format('H:i') }}
                                </span>
                            @else
                                <span class="text-slate-300 text-xs">—</span>
                            @endif
                        </td>

                        <!-- Coluna de Check-in Rápido -->
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                            <button type="button"
                                    wire:click="alterarStatus({{ $ag->id }}, 'PRESENTE')"
                                    class="rounded-md bg-emerald-600 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-500 cursor-pointer">
                                Presente
                            </button>
                            <button type="button"
                                    wire:click="alterarStatus({{ $ag->id }}, 'FALTA_INJUSTIFICADA')"
                                    class="rounded-md bg-red-600 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-red-500 cursor-pointer">
                                Falta
                            </button>
                            <button type="button"
                                    wire:click="alterarStatus({{ $ag->id }}, 'FALTA_JUSTIFICADA')"
                                    class="rounded-md bg-amber-500 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-amber-400 cursor-pointer">
                                Falta Just.
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            Nenhum agendamento localizado para a data e filtro selecionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
