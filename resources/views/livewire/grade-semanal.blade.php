<div class="space-y-4 sm:space-y-6">
    <!-- Cabeçalho e Filtros de Dia e Turno -->
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900">Grade Semanal de Reabilitação</h2>
            <p class="text-xs text-slate-500">Espelhamento por Salas e Terapeutas • Slots de 30 minutos ({{ \Carbon\CarbonImmutable::parse($dataReferencia)->format('d/m/Y') }})</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-2.5">
            <!-- Filtro Dia da Semana (Seg a Sex) -->
            <div class="grid grid-cols-5 sm:inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1">
                @foreach(['SEGUNDA' => 'Seg', 'TERCA' => 'Ter', 'QUARTA' => 'Qua', 'QUINTA' => 'Qui', 'SEXTA' => 'Sex'] as $chave => $rotulo)
                    <button type="button"
                            wire:click="$set('diaSemana', '{{ $chave }}')"
                            class="rounded-md px-2.5 sm:px-3 py-1.5 text-xs font-semibold text-center transition cursor-pointer {{ $diaSemana === $chave ? 'bg-white text-teal-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $rotulo }}
                    </button>
                @endforeach
            </div>

            <!-- Filtro Turno -->
            <div class="grid grid-cols-3 sm:inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1">
                @foreach(['MANHA' => 'Manhã (08h-12h)', 'TARDE' => 'Tarde (13h-17h)', 'INTEGRAL' => 'Integral'] as $chaveTurno => $rotuloTurno)
                    <button type="button"
                            wire:click="$set('turno', '{{ $chaveTurno }}')"
                            class="rounded-md px-2 sm:px-3 py-1.5 text-[11px] sm:text-xs font-semibold text-center transition cursor-pointer {{ $turno === $chaveTurno ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $rotuloTurno }}
                    </button>
                @endforeach
            </div>

            @can('create', \App\Models\Agendamento::class)
                <button type="button"
                        wire:click="abrirModalSlot()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-teal-500 cursor-pointer">
                    + Novo Agendamento
                </button>
            @endcan
        </div>
    </div>

    <!-- Matriz Colunas por Sala x Linhas por Slots de 30 min -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th class="sticky left-0 z-10 bg-slate-50 px-2.5 sm:px-3 py-3 font-semibold text-slate-700 border-r border-slate-200 w-20 sm:w-24">
                        Horário
                    </th>
                    @foreach($salas as $sala)
                        <th class="px-3 py-3 font-semibold text-slate-800 border-r border-slate-200 min-w-[190px] sm:min-w-[210px]">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate">{{ $sala->nome }}</span>
                                <span class="rounded bg-slate-200/80 px-1.5 py-0.5 text-[10px] font-mono text-slate-600 shrink-0">
                                    {{ $sala->tipo }}
                                </span>
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($slots as $slot)
                    <tr class="hover:bg-slate-50/60">
                        <td class="sticky left-0 z-10 bg-white px-2.5 sm:px-3 py-2.5 font-mono font-semibold text-slate-600 border-r border-slate-200">
                            {{ $slot }}
                        </td>
                        @foreach($salas as $sala)
                            @php
                                $itensSlot = $gradeIndexada[$sala->id][$slot] ?? [];
                            @endphp
                            <td class="p-2 align-top border-r border-slate-100">
                                @if(count($itensSlot) > 0)
                                    <div class="space-y-1.5">
                                        @foreach($itensSlot as $ag)
                                            <div class="rounded-lg border p-2 {{ $ag->tipo_atendimento === 'TRABALHO_INTERNO' ? 'bg-amber-50 border-amber-200' : ($ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-indigo-50 border-indigo-200' : 'bg-teal-50/70 border-teal-200') }}">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="font-semibold text-slate-900 truncate">
                                                        {{ $ag->tipo_atendimento === 'TRABALHO_INTERNO' ? 'EVOLUÇÃO / INTERNO' : $ag->paciente?->nome }}
                                                    </span>
                                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold shrink-0 {{ $ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-indigo-100 text-indigo-800' : 'bg-white/80 text-slate-700' }}">
                                                        {{ $ag->tipo_atendimento }}
                                                    </span>
                                                </div>
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach($ag->profissionais as $prof)
                                                        <span class="inline-flex items-center rounded bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-700 border border-slate-200">
                                                            {{ $prof->nome }} ({{ $prof->especialidade }})
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    @can('create', \App\Models\Agendamento::class)
                                        <button type="button"
                                                wire:click="abrirModalSlot({{ $sala->id }}, '{{ $slot }}')"
                                                class="w-full rounded-lg border border-dashed border-slate-200 py-3 text-center text-[11px] text-slate-400 hover:border-teal-400 hover:text-teal-600 transition cursor-pointer">
                                            + Alocar {{ $slot }}
                                        </button>
                                    @else
                                        <span class="block py-3 text-center text-[11px] text-slate-300">Livre</span>
                                    @endcan
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal de Agendamento Rápido com Múltiplos Profissionais -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-3 sm:p-4 overflow-y-auto">
            <div class="w-full max-w-lg rounded-xl bg-white p-4 sm:p-6 shadow-xl border border-slate-200 my-auto max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">Agendamento na Grade Semanal</h3>
                    <button type="button" wire:click="$set('modalAberto', false)" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarAgendamento" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700">Paciente (CNS Válido)</label>
                        <select wire:model="paciente_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Selecione o usuário do CER...</option>
                            @foreach($pacientes as $pac)
                                <option value="{{ $pac->id }}">{{ $pac->nome }} — CNS: {{ $pac->cns }}</option>
                            @endforeach
                        </select>
                        @error('paciente_id') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700">Sala / Espaço</label>
                            <select wire:model="sala_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecione a sala...</option>
                                @foreach($salas as $s)
                                    <option value="{{ $s->id }}">{{ $s->nome }}</option>
                                @endforeach
                            </select>
                            @error('sala_id') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700">Modalidade de Encontro</label>
                            <select wire:model="tipo_atendimento" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="INDIVIDUAL">Individual</option>
                                <option value="COMPARTILHADO">Atendimento Compartilhado</option>
                                <option value="TRABALHO_INTERNO">Trabalho Interno (EVOLUÇÃO)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700">Data Base</label>
                            <input type="date" wire:model="dataReferencia" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">Início</label>
                            <input type="time" wire:model="hora_inicio" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">Fim</label>
                            <input type="time" wire:model="hora_fim" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">Profissionais Alocados (Selecione 1 ou mais para Co-Atendimento)</label>
                        <div class="mt-1 max-h-36 overflow-y-auto rounded-lg border border-slate-300 p-2 space-y-1.5">
                            @foreach($profissionaisDisponiveis as $prof)
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="profissionaisSelecionados" value="{{ $prof->id }}" class="mt-0.5 rounded border-slate-300 text-teal-600">
                                    <span>{{ $prof->nome }} — <span class="text-slate-500">{{ $prof->especialidade }} (CBO {{ $prof->cbo_padrao }})</span></span>
                                </label>
                            @endforeach
                        </div>
                        @error('profissionaisSelecionados') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <label class="inline-flex items-center gap-2 font-medium text-slate-700 cursor-pointer">
                            <input type="checkbox" wire:model="recorrente" class="rounded border-slate-300 text-teal-600">
                            Repetir semanalmente (Horário Cativo)
                        </label>
                        <div class="flex items-center gap-1.5">
                            <span>Semanas:</span>
                            <input type="number" min="1" max="12" wire:model="semanasRecorrencia" class="w-16 rounded border border-slate-300 px-2 py-1">
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('modalAberto', false)" class="w-full sm:w-auto rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" class="w-full sm:w-auto rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white hover:bg-teal-500 cursor-pointer">
                            Confirmar Agendamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
