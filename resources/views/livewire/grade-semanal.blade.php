<div class="space-y-4 sm:space-y-6">
    <!-- Sub-abas de Navegação da Agenda (Grade Operacional vs Mapa Mensal) -->
    <div class="flex items-center gap-2 border-b border-slate-200">
        <a href="{{ route('grade.semanal') }}"
           class="inline-flex items-center gap-2 border-b-2 border-teal-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-teal-700 transition">
            <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
            </svg>
            <span>Visualização Diária (Operacional)</span>
        </a>
        <a href="{{ route('agenda.mensal') }}"
           class="inline-flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs sm:text-sm font-medium text-slate-500 hover:border-slate-300 hover:text-slate-700 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm3-3h.008v.008H15V12zm0 3h.008v.008H15V15zm-6-3h.008v.008H9V12zm0 3h.008v.008H9V15z"/>
            </svg>
            <span>Mapa Mensal (Gestão & Ocupação)</span>
        </a>
    </div>

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
                    <button type="button" wire:click="fecharModal" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarAgendamento" class="mt-4 space-y-4 text-xs">
                    <!-- Campo de Busca de Paciente Sob Demanda -->
                    <div>
                        <label class="block font-semibold text-slate-700">
                            Paciente <span class="text-rose-600">*</span>
                        </label>

                        @if($pacienteSelecionado)
                            <!-- Card de Paciente Selecionado -->
                            <div class="mt-1 flex items-start justify-between gap-3 rounded-lg border border-teal-200 bg-teal-50/70 p-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center rounded-full bg-teal-600 text-white w-5 h-5 text-xs font-bold">
                                            ✓
                                        </span>
                                        <span class="font-bold text-slate-900 text-xs sm:text-sm">
                                            {{ $pacienteSelecionado['nome'] }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-0.5 text-[11px] text-slate-600 pl-7">
                                        <div>
                                            <span class="text-slate-400 font-medium">CPF:</span>
                                            <span class="font-mono text-slate-800">{{ $pacienteSelecionado['cpf'] ?? 'Não informado' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 font-medium">CNS:</span>
                                            <span class="font-mono text-slate-800">{{ $pacienteSelecionado['cns'] ?? 'Não informado' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 font-medium">Nasc:</span>
                                            <span class="text-slate-800">{{ $pacienteSelecionado['data_nascimento'] ?? 'Não informado' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="limparPacienteSelecionado"
                                    class="shrink-0 inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-rose-600 hover:border-rose-300 transition-colors cursor-pointer"
                                    title="Remover seleção e pesquisar outro paciente"
                                >
                                    <span>✕</span>
                                    <span>Trocar</span>
                                </button>
                            </div>

                            @if($alertaPaciente)
                                <!-- Banner Visual Claro de Pendência Cadastral (Sem Códigos Técnicos) -->
                                <div class="mt-2.5 rounded-lg border border-amber-300 bg-amber-50 p-3 text-amber-900 shadow-xs">
                                    <div class="flex items-start gap-2.5">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-900 text-xs font-bold">
                                            !
                                        </span>
                                        <div class="space-y-1.5 flex-1">
                                            <h4 class="font-bold text-xs text-amber-950">
                                                {{ $alertaPaciente['titulo'] }}
                                            </h4>
                                            <p class="text-[11px] leading-relaxed text-amber-900">
                                                {{ $alertaPaciente['mensagem'] }}
                                            </p>

                                            <!-- Ações de Resolução Cadastral para Perfis Autorizados -->
                                            <div class="pt-1.5 flex flex-wrap items-center gap-2">
                                                @if(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']))
                                                    <a
                                                        href="{{ route('configuracoes.secao', ['secao' => 'pacientes', 'editar_paciente' => $pacienteSelecionado['id']]) }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center gap-1 rounded-md bg-amber-700 hover:bg-amber-800 text-white px-2.5 py-1 text-[11px] font-semibold shadow-xs transition"
                                                        title="Abrir o cadastro deste paciente em nova aba para atualizar o Cartão SUS ou data de nascimento"
                                                    >
                                                        <span>Atualizar cadastro</span>
                                                        <span class="text-[10px]">↗</span>
                                                    </a>

                                                    <button
                                                        type="button"
                                                        wire:click="revalidarPacienteSelecionado"
                                                        class="inline-flex items-center gap-1 rounded-md border border-amber-400 bg-white hover:bg-amber-100 text-amber-900 px-2.5 py-1 text-[11px] font-semibold transition cursor-pointer"
                                                        title="Conferir se o cadastro foi corrigido no sistema"
                                                    >
                                                        <span>Revalidar dados</span>
                                                        <span class="text-[10px]">↻</span>
                                                    </button>
                                                @else
                                                    <span class="text-[10px] text-amber-800 italic">
                                                        Solicite a atualização do cadastro do paciente à Recepção ou Coordenação.
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <!-- Input de Pesquisa Sob Demanda -->
                            <div class="relative mt-1">
                                <div class="relative flex items-center">
                                    <input
                                        type="text"
                                        wire:model.live.debounce.400ms="pesquisaPaciente"
                                        placeholder="Digite o nome, CPF ou Cartão SUS do paciente"
                                        autocomplete="off"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10 text-xs placeholder:text-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                        autofocus
                                    >
                                    <!-- Indicador de Carregamento Discreto -->
                                    <div wire:loading wire:target="pesquisaPaciente" class="absolute right-3 text-teal-600">
                                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Feedback e Lista de Resultados -->
                                @if(mb_strlen(trim($pesquisaPaciente)) < 3)
                                    <p class="mt-1.5 text-[11px] text-slate-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Pesquise um paciente para iniciar
                                    </p>
                                @else
                                    <div class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm divide-y divide-slate-100">
                                        @forelse($pacientesResultados as $pac)
                                            <button
                                                type="button"
                                                wire:click="selecionarPaciente({{ $pac['id'] }})"
                                                wire:key="paciente-resultado-{{ $pac['id'] }}"
                                                class="w-full text-left p-2.5 hover:bg-teal-50/60 focus:bg-teal-50 transition-colors flex items-center justify-between gap-3 group cursor-pointer"
                                            >
                                                <div class="space-y-0.5 min-w-0">
                                                    <div class="font-bold text-slate-800 text-xs truncate group-hover:text-teal-700">
                                                        {{ $pac['nome'] }}
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-x-3 text-[11px] text-slate-500">
                                                        <span>CPF: <strong class="text-slate-700 font-mono">{{ $pac['cpf'] ?? '—' }}</strong></span>
                                                        <span>CNS: <strong class="text-slate-700 font-mono">{{ $pac['cns'] ?? '—' }}</strong></span>
                                                        <span>Nasc: <strong class="text-slate-700">{{ $pac['data_nascimento'] ?? '—' }}</strong></span>
                                                    </div>
                                                </div>
                                                <span class="shrink-0 text-xs font-semibold text-teal-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    Selecionar →
                                                </span>
                                            </button>
                                        @empty
                                            <div class="p-3 text-center text-xs text-slate-500" wire:loading.remove wire:target="pesquisaPaciente">
                                                Nenhum paciente encontrado
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endif

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
                        <button type="button" wire:click="fecharModal" class="w-full sm:w-auto rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 cursor-pointer">
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
