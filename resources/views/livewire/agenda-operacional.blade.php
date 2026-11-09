<div class="space-y-4 sm:space-y-6" @if($aba === 'dia' && !$modalNovoAgendamentoAberto) wire:poll.30s @endif>
    <!-- Cabeçalho Principal da Agenda Operacional -->
    <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900">Agenda Operacional do CER</h1>
                @php
                    $carbonData = \Carbon\CarbonImmutable::parse($data);
                    $nomeDiaExtenso = match($carbonData->dayOfWeekIso) {
                        1 => 'SEGUNDA-FEIRA',
                        2 => 'TERÇA-FEIRA',
                        3 => 'QUARTA-FEIRA',
                        4 => 'QUINTA-FEIRA',
                        5 => 'SEXTA-FEIRA',
                        6 => 'SÁBADO',
                        7 => 'DOMINGO',
                    };
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-teal-100 text-teal-800 border border-teal-200">
                    {{ $nomeDiaExtenso }}, {{ $carbonData->format('d/m/Y') }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Gestão diária de fluxo, controle de recepção, chamadas de consultório e atendimentos
            </p>
        </div>

        <div class="flex items-center gap-2">
            @can('create', \App\Models\Agendamento::class)
                <button
                    type="button"
                    wire:click="abrirModalNovoAgendamento"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Novo Agendamento</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- Navegação por Abas (Inspirado no CEO Digital) -->
    <div class="border-b border-slate-200 bg-white px-4 rounded-xl shadow-xs">
        <nav class="flex space-x-6 -mb-px" aria-label="Abas da Agenda">
            <button
                type="button"
                wire:click="definirAba('dia')"
                class="inline-flex items-center gap-2 py-3.5 px-1 border-b-2 font-semibold text-xs transition cursor-pointer {{ $aba === 'dia' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                <svg class="h-4 w-4 {{ $aba === 'dia' ? 'text-teal-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Visualização Diária (Operacional)</span>
                <span class="rounded-full px-2 py-0.2 text-[10px] font-bold {{ $aba === 'dia' ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $contadoresDia['total'] }}
                </span>
            </button>

            <button
                type="button"
                wire:click="definirAba('grade')"
                class="inline-flex items-center gap-2 py-3.5 px-1 border-b-2 font-semibold text-xs transition cursor-pointer {{ $aba === 'grade' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                <svg class="h-4 w-4 {{ $aba === 'grade' ? 'text-teal-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Grade Semanal (Planejamento)</span>
            </button>

            <button
                type="button"
                wire:click="definirAba('mensal')"
                class="inline-flex items-center gap-2 py-3.5 px-1 border-b-2 font-semibold text-xs transition cursor-pointer {{ $aba === 'mensal' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
            >
                <svg class="h-4 w-4 {{ $aba === 'mensal' ? 'text-teal-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span>Mapa Mensal (Gestão & Ocupação)</span>
            </button>
        </nav>
    </div>

    <!-- CONTEÚDO DA ABA 1: AGENDA DO DIA (VISUALIZAÇÃO DIÁRIA OPERACIONAL) -->
    @if($aba === 'dia')
        <!-- Barra de Navegação de Data & Filtros -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <!-- Seletor de Data -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="diaAnterior"
                        class="p-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 transition cursor-pointer"
                        title="Dia anterior"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <input
                        type="date"
                        wire:model.live="data"
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                    >

                    <button
                        type="button"
                        wire:click="proximoDia"
                        class="p-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 transition cursor-pointer"
                        title="Próximo dia"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <button
                        type="button"
                        wire:click="irParaHoje"
                        class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                    >
                        Hoje
                    </button>
                </div>

                <!-- Filtros Dropdown -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <select wire:model.live="filtroProfissionalId" class="rounded-lg border border-slate-300 px-3 py-1.5 bg-white text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="">Todos os Profissionais</option>
                        @foreach($profissionais as $prof)
                            <option value="{{ $prof->id }}">{{ $prof->nome }} ({{ $prof->especialidade }})</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filtroSalaId" class="rounded-lg border border-slate-300 px-3 py-1.5 bg-white text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="">Todas as Salas</option>
                        @foreach($salas as $sala)
                            <option value="{{ $sala->id }}">{{ $sala->nome }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filtroStatus" class="rounded-lg border border-slate-300 px-3 py-1.5 bg-white text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="">Todos os Status</option>
                        <option value="AGENDADO">Agendado</option>
                        <option value="PRESENTE">Na Recepção / Presente</option>
                        <option value="ATENDIDO">Concluído / Atendido</option>
                        <option value="FALTA_JUSTIFICADA">Falta Justificada</option>
                        <option value="FALTA_INJUSTIFICADA">Falta Injustificada</option>
                        <option value="CANCELADO">Cancelado</option>
                    </select>
                </div>
            </div>

            <!-- Botões de Turno / Período (Destaque idêntico ao CEO Digital) -->
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-xs">
                <button
                    type="button"
                    wire:click="definirPeriodo('TODOS')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer {{ $periodo === 'TODOS' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >
                    <span>Todos os Horários</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $periodo === 'TODOS' ? 'bg-slate-700 text-white' : 'bg-slate-200 text-slate-800' }}">
                        {{ $contadoresDia['total'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="definirPeriodo('MANHA')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer {{ $periodo === 'MANHA' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200' }}"
                >
                    <span class="flex h-2 w-2 rounded-full bg-amber-400"></span>
                    <span>Manhã (07h às 12h)</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold {{ $periodo === 'MANHA' ? 'bg-amber-700 text-white' : 'bg-amber-200 text-amber-900' }}">
                        {{ $contadoresDia['manha'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="definirPeriodo('TARDE')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer {{ $periodo === 'TARDE' ? 'bg-sky-600 text-white shadow-xs' : 'bg-sky-50 text-sky-900 hover:bg-sky-100 border border-sky-200' }}"
                >
                    <span class="flex h-2 w-2 rounded-full bg-sky-400"></span>
                    <span>Tarde (13h às 18h)</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold {{ $periodo === 'TARDE' ? 'bg-sky-700 text-white' : 'bg-sky-200 text-sky-900' }}">
                        {{ $contadoresDia['tarde'] }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="definirPeriodo('NOITE')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer {{ $periodo === 'NOITE' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-50 text-indigo-900 hover:bg-indigo-100 border border-indigo-200' }}"
                >
                    <span class="flex h-2 w-2 rounded-full bg-indigo-400"></span>
                    <span>Noite (18h às 22h)</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold {{ $periodo === 'NOITE' ? 'bg-indigo-700 text-white' : 'bg-indigo-200 text-indigo-900' }}">
                        {{ $contadoresDia['noite'] }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Cards de Indicadores do Dia (KPIs Operacionais) -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 text-xs">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
                <span class="text-slate-500 block text-[11px] font-medium">Agendados</span>
                <span class="text-xl font-bold text-slate-800">{{ $contadoresDia['agendados'] }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-teal-200 shadow-xs bg-linear-to-b from-teal-50/50 to-white">
                <span class="text-teal-700 block text-[11px] font-medium">Na Recepção / Presentes</span>
                <span class="text-xl font-bold text-teal-700">{{ $contadoresDia['presentes'] }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-emerald-200 shadow-xs bg-linear-to-b from-emerald-50/50 to-white">
                <span class="text-emerald-700 block text-[11px] font-medium">Atendidos / Concluídos</span>
                <span class="text-xl font-bold text-emerald-700">{{ $contadoresDia['atendidos'] }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-rose-200 shadow-xs bg-linear-to-b from-rose-50/50 to-white">
                <span class="text-rose-700 block text-[11px] font-medium">Faltas (Just. + Ausente)</span>
                <span class="text-xl font-bold text-rose-700">{{ $contadoresDia['faltas'] }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs bg-linear-to-b from-slate-50 to-white">
                <span class="text-slate-500 block text-[11px] font-medium">Cancelados</span>
                <span class="text-xl font-bold text-slate-600">{{ $contadoresDia['cancelados'] }}</span>
            </div>
        </div>

        <!-- Tabela Operacional de Atendimentos do Dia -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            @if($agendamentosDia->isEmpty())
                <div class="p-12 text-center text-xs text-slate-500">
                    <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm font-semibold text-slate-700">Nenhum agendamento encontrado para este dia e filtros selecionados.</p>
                    <p class="text-slate-400 mt-1">Utilize o botão "+ Novo Agendamento" acima para registrar um horário.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3.5 whitespace-nowrap">Ordem / Tipo</th>
                                <th class="px-4 py-3.5">Paciente</th>
                                <th class="px-4 py-3.5">Profissional / Sala</th>
                                <th class="px-4 py-3.5 whitespace-nowrap">Chegada Recepção</th>
                                <th class="px-4 py-3.5 whitespace-nowrap">Status Atual</th>
                                <th class="px-4 py-3.5 text-right whitespace-nowrap">Fluxo / Ações Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-sans">
                            @foreach($agendamentosDia as $index => $ag)
                                @php
                                    $alerta = $alertasAbsenteismo[$ag->paciente_id] ?? ['alerta' => false, 'motivo' => null];
                                    $ultimaChamada = $ag->chamadas->sortByDesc('id')->first();
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors {{ $ag->status === 'PRESENTE' ? 'bg-teal-50/30' : '' }}" wire:key="ag-dia-{{ $ag->id }}">
                                    <!-- Ordem / Tipo -->
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-slate-400 font-bold text-[11px]">#{{ $index + 1 }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider {{ $ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-purple-100 text-purple-800 border border-purple-200' : ($ag->tipo_atendimento === 'TRABALHO_INTERNO' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                                {{ $ag->tipo_atendimento }}
                                            </span>
                                        </div>
                                        <div class="font-bold text-slate-900 mt-1">
                                            {{ substr((string) $ag->hora_inicio, 0, 5) }} - {{ substr((string) $ag->hora_fim, 0, 5) }}
                                        </div>
                                    </td>

                                    <!-- Paciente (Privacidade e Alerta de Absenteísmo) -->
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-xs hover:text-teal-700 transition">
                                                {{ $ag->paciente->nome }}
                                            </span>
                                            @if($alerta['alerta'])
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200" title="{{ $alerta['motivo'] }}">
                                                    ⚠️ Absenteísmo: {{ $alerta['motivo'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-2">
                                            <span>Nasc: {{ $ag->paciente->data_nascimento?->format('d/m/Y') ?? '—' }}</span>
                                            @if($ag->paciente->cid_principal)
                                                <span>• <strong class="font-mono text-slate-600">CID {{ $ag->paciente->cid_principal }}</strong></span>
                                            @endif
                                            @if($ag->observacao)
                                                <span class="text-amber-700 font-medium italic truncate max-w-xs">• Obs: {{ $ag->observacao }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Profissional / Especialidade / Sala -->
                                    <td class="px-4 py-3.5">
                                        <div class="font-medium text-slate-800">
                                            {{ $ag->profissionais->pluck('nome')->implode(', ') }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] bg-slate-100 text-slate-700">
                                                {{ $ag->sala->nome }}
                                            </span>
                                            <span>• {{ $ag->profissionais->pluck('especialidade')->filter()->unique()->implode(', ') }}</span>
                                        </div>
                                    </td>

                                    <!-- Chegada Recepção -->
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @if($ag->chegada_em)
                                            <span class="inline-flex items-center gap-1.5 font-bold font-mono text-teal-700 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200">
                                                <svg class="h-3.5 w-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ $ag->chegada_em->format('H:i') }}
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="confirmarPresenca({{ $ag->id }})"
                                                class="inline-flex items-center gap-1 rounded-lg border border-teal-300 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800 hover:bg-teal-600 hover:text-white transition cursor-pointer"
                                            >
                                                <span>✓</span>
                                                <span>Registrar Chegada</span>
                                            </button>
                                        @endif
                                    </td>

                                    <!-- Status Atual -->
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @if($ag->status === 'PRESENTE')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-teal-600 animate-pulse"></span>
                                                Na Recepção
                                            </span>
                                        @elseif($ag->status === 'ATENDIDO')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                                Concluído
                                            </span>
                                        @elseif($ag->status === 'AGENDADO')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                Agendado
                                            </span>
                                        @elseif($ag->status === 'FALTA_JUSTIFICADA')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                Falta Justificada
                                            </span>
                                        @elseif($ag->status === 'FALTA_INJUSTIFICADA')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                Falta (Ausente)
                                            </span>
                                        @elseif($ag->status === 'CANCELADO')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-200 text-slate-600 line-through">
                                                Cancelado
                                            </span>
                                        @endif

                                        @if($ultimaChamada)
                                            <div class="text-[10px] text-sky-700 mt-1 flex items-center gap-1">
                                                <span class="h-1 w-1 rounded-full bg-sky-500"></span>
                                                Chamado às {{ $ultimaChamada->created_at->format('H:i') }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Fluxo / Ações Rápidas -->
                                    <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if($ag->status === 'PRESENTE')
                                                <!-- Chamar TV -->
                                                <button
                                                    type="button"
                                                    wire:click="{{ $ultimaChamada ? 'repetirChamada('.$ag->id.')' : 'chamarPaciente('.$ag->id.')' }}"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-sky-300 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800 hover:bg-sky-600 hover:text-white transition cursor-pointer"
                                                    title="Chamar paciente na TV do painel"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                    <span>{{ $ultimaChamada ? 'Chamar Novamente' : 'Chamar TV' }}</span>
                                                </button>

                                                @if(auth()->user()->hasAnyRole(['terapeuta', 'coordenacao']))
                                                    <a
                                                        href="{{ route('prontuario.index', ['paciente' => $ag->paciente_id]) }}"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-teal-700 transition"
                                                    >
                                                        <span>Atender →</span>
                                                    </a>
                                                @endif
                                            @elseif($ag->status === 'AGENDADO')
                                                <!-- Confirmar Presença -->
                                                <button
                                                    type="button"
                                                    wire:click="confirmarPresenca({{ $ag->id }})"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-teal-700 transition cursor-pointer"
                                                >
                                                    <span>Confirmar Presença</span>
                                                </button>
                                            @endif

                                            <!-- Menu de Falta / Cancelamento -->
                                            <div class="relative" x-data="{ aberto: false }">
                                                <button
                                                    type="button"
                                                    @click="aberto = !aberto"
                                                    class="p-1 rounded-lg border border-slate-300 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition cursor-pointer"
                                                    title="Mais opções de status"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                                </button>

                                                <div
                                                    x-show="aberto"
                                                    @click.outside="aberto = false"
                                                    x-transition
                                                    class="absolute right-0 z-50 mt-1 w-44 rounded-lg border border-slate-200 bg-white shadow-xl py-1 text-left text-xs text-slate-700 divide-y divide-slate-100"
                                                    style="display: none;"
                                                >
                                                    @if($ag->status !== 'PRESENTE')
                                                        <button
                                                            type="button"
                                                            wire:click="alterarStatus({{ $ag->id }}, 'PRESENTE')"
                                                            @click="aberto = false"
                                                            class="w-full px-3 py-1.5 text-left text-teal-700 hover:bg-teal-50 flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <span>✓</span>
                                                            <span>Confirmar Presença</span>
                                                        </button>
                                                    @endif

                                                    @if($ag->status !== 'ATENDIDO')
                                                        <button
                                                            type="button"
                                                            wire:click="alterarStatus({{ $ag->id }}, 'ATENDIDO')"
                                                            @click="aberto = false"
                                                            class="w-full px-3 py-1.5 text-left text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <span>✓</span>
                                                            <span>Marcar Atendido</span>
                                                        </button>
                                                    @endif

                                                    @if($ag->status !== 'FALTA_JUSTIFICADA')
                                                        <button
                                                            type="button"
                                                            wire:click="alterarStatus({{ $ag->id }}, 'FALTA_JUSTIFICADA')"
                                                            @click="aberto = false"
                                                            class="w-full px-3 py-1.5 text-left text-amber-700 hover:bg-amber-50 flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <span>⚠</span>
                                                            <span>Falta Justificada</span>
                                                        </button>
                                                    @endif

                                                    @if($ag->status !== 'FALTA_INJUSTIFICADA')
                                                        <button
                                                            type="button"
                                                            wire:click="alterarStatus({{ $ag->id }}, 'FALTA_INJUSTIFICADA')"
                                                            @click="aberto = false"
                                                            class="w-full px-3 py-1.5 text-left text-rose-700 hover:bg-rose-50 flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <span>✕</span>
                                                            <span>Falta (Ausente)</span>
                                                        </button>
                                                    @endif

                                                    @if($ag->status !== 'CANCELADO')
                                                        <button
                                                            type="button"
                                                            wire:click="alterarStatus({{ $ag->id }}, 'CANCELADO')"
                                                            @click="aberto = false"
                                                            class="w-full px-3 py-1.5 text-left text-slate-500 hover:bg-slate-100 flex items-center gap-2 cursor-pointer"
                                                        >
                                                            <span>🚫</span>
                                                            <span>Cancelar Horário</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <!-- CONTEÚDO DA ABA 2: GRADE SEMANAL (PLANEJAMENTO RECORRENTE) -->
    @if($aba === 'grade')
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <!-- Cabeçalho de Navegação e Filtros da Grade -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-slate-100 pb-3">
                <!-- Seletor dos Dias da Semana com Datas Reais -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    @foreach($diasSemanaInfo as $info)
                        <button
                            type="button"
                            wire:click="mudarDiaSemana('{{ $info['sigla'] }}')"
                            class="px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer flex items-center gap-1.5 {{ $diaSemana === $info['sigla'] ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                            title="Ver {{ $info['label'] }}-feira, {{ \Carbon\CarbonImmutable::parse($info['data'])->format('d/m/Y') }}"
                        >
                            <span>{{ $info['label'] }}</span>
                            <span class="text-[11px] font-mono {{ $diaSemana === $info['sigla'] ? 'text-teal-100' : 'text-slate-500 font-medium' }}">
                                {{ $info['dia_mes'] }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <!-- Indicador Visual do Dia Selecionado e Intervalo da Semana -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="text-left sm:text-right">
                        <div class="text-sm font-bold text-slate-900 flex items-center gap-1.5 sm:justify-end">
                            <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                            <span>{{ $diasSemanaInfo[$diaSemana]['label'] }}-feira, {{ \Carbon\CarbonImmutable::parse($dataRefGrade)->format('d/m/Y') }}</span>
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            Semana de {{ $semanaInicioCarbon->format('d/m/Y') }} a {{ $semanaFimCarbon->format('d/m/Y') }}
                        </div>
                    </div>

                    <!-- Controles: Turno, Modo de Visão e Navegação entre Semanas -->
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <!-- Alternador de Visão (Por Sala / Semana Completa) -->
                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5">
                            <button
                                type="button"
                                wire:click="alternarVisaoGrade('SALA')"
                                class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer {{ $visaoGrade === 'SALA' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
                            >
                                Por Sala
                            </button>
                            <button
                                type="button"
                                wire:click="alternarVisaoGrade('SEMANA_COMPLETA')"
                                class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer {{ $visaoGrade === 'SEMANA_COMPLETA' ? 'bg-white text-teal-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
                            >
                                Semana Completa
                            </button>
                        </div>

                        <select wire:model.live="turnoGrade" class="rounded-lg border border-slate-300 px-2.5 py-1.5 bg-white text-slate-700 font-medium">
                            <option value="MANHA">Manhã</option>
                            <option value="TARDE">Tarde</option>
                            <option value="TODOS">Todos os Turnos</option>
                        </select>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                wire:click="semanaAnterior"
                                class="p-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 transition cursor-pointer"
                                title="Semana anterior"
                            >
                                ◀
                            </button>
                            <button
                                type="button"
                                wire:click="proximaSemana"
                                class="p-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 transition cursor-pointer"
                                title="Próxima semana"
                            >
                                ▶
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if($visaoGrade === 'SEMANA_COMPLETA')
                <!-- ============================================================== -->
                <!-- VISÃO: SEMANA COMPLETA (Horário x Segunda a Sexta)             -->
                <!-- ============================================================== -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <label for="filtroSalaGrade" class="font-bold text-slate-700">Filtrar por Sala:</label>
                        <select id="filtroSalaGrade" wire:model.live="filtroSalaGrade" class="rounded-lg border border-slate-300 px-3 py-1.5 bg-white text-slate-700">
                            <option value="">Todas as Salas do CER</option>
                            @foreach($salas as $sala)
                                <option value="{{ $sala->id }}">{{ $sala->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-slate-500">Visualização consolidada de segunda a sexta para planejamento e visão geral.</span>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-2.5 w-24 border-r border-slate-200 text-center">Horário</th>
                                @foreach($diasSemanaInfo as $info)
                                    <th class="p-2.5 border-r border-slate-200 min-w-48 text-slate-800 {{ $diaSemana === $info['sigla'] ? 'bg-teal-50/70 text-teal-900 border-t-2 border-t-teal-600' : '' }}">
                                        <div class="flex items-center justify-between">
                                            <button
                                                type="button"
                                                wire:click="mudarDiaSemana('{{ $info['sigla'] }}')"
                                                class="font-bold hover:underline cursor-pointer text-left"
                                            >
                                                {{ $info['label'] }} ({{ $info['dia_mes'] }})
                                            </button>
                                            @if($diaSemana === $info['sigla'])
                                                <span class="text-[9px] bg-teal-200 text-teal-800 px-1.5 py-0.5 rounded font-bold uppercase">Ativo</span>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($slotsGrade as $slot)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-2 font-mono font-bold text-slate-600 bg-slate-50/70 border-r border-slate-200 text-center">
                                        {{ $slot }}
                                    </td>
                                    @foreach($diasSemanaInfo as $info)
                                        @php
                                            $agendamentosSemanaSlot = $gradeSemanaCompleta[$info['data']][$slot] ?? [];
                                        @endphp
                                        <td class="p-2 border-r border-slate-200 align-top {{ $diaSemana === $info['sigla'] ? 'bg-teal-50/20' : '' }}">
                                            @if(!empty($agendamentosSemanaSlot))
                                                <div class="space-y-1.5">
                                                    @foreach($agendamentosSemanaSlot as $ag)
                                                        <div class="p-2 rounded-lg border text-xs {{ $ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-purple-50 border-purple-200 text-purple-900' : 'bg-teal-50 border-teal-200 text-teal-900' }}">
                                                            <div class="font-bold flex items-center justify-between gap-1">
                                                                <span class="truncate">{{ $ag->paciente->nome }}</span>
                                                                <span class="text-[9px] bg-white border border-slate-200 text-slate-700 px-1 py-0.2 rounded font-medium">{{ $ag->sala->nome }}</span>
                                                            </div>
                                                            <div class="text-[11px] text-slate-600 truncate mt-0.5">
                                                                {{ $ag->profissionais->pluck('nome')->implode(', ') }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="abrirModalNovoAgendamento({{ !empty($filtroSalaGrade) ? (int)$filtroSalaGrade : 'null' }}, '{{ $slot }}', '{{ $info['data'] }}')"
                                                    class="w-full py-1.5 rounded border border-dashed border-slate-200 text-slate-400 hover:border-teal-400 hover:text-teal-600 hover:bg-teal-50/50 text-[11px] transition text-center cursor-pointer"
                                                >
                                                    + Agendar
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- ============================================================== -->
                <!-- VISÃO: POR SALA (Horário x Salas para o Dia Selecionado)       -->
                <!-- ============================================================== -->
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-2.5 w-24 border-r border-slate-200 text-center">Horário</th>
                                @foreach($salas as $sala)
                                    <th class="p-2.5 border-r border-slate-200 min-w-44 text-slate-800">
                                        {{ $sala->nome }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($slotsGrade as $slot)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-2 font-mono font-bold text-slate-600 bg-slate-50/70 border-r border-slate-200 text-center">
                                        {{ $slot }}
                                    </td>
                                    @foreach($salas as $sala)
                                        @php
                                            $agendamentosSlot = $gradeIndexada[$sala->id][$slot] ?? [];
                                        @endphp
                                        <td class="p-2 border-r border-slate-200 align-top">
                                            @if(!empty($agendamentosSlot))
                                                <div class="space-y-1.5">
                                                    @foreach($agendamentosSlot as $ag)
                                                        <div class="p-2 rounded-lg border text-xs {{ $ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-purple-50 border-purple-200 text-purple-900' : 'bg-teal-50 border-teal-200 text-teal-900' }}">
                                                            <div class="font-bold flex items-center justify-between gap-1">
                                                                <span class="truncate">{{ $ag->paciente->nome }}</span>
                                                                @if($ag->tipo_atendimento === 'COMPARTILHADO')
                                                                    <span class="text-[9px] bg-purple-200 text-purple-800 px-1 rounded font-bold">MULTI</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-[11px] text-slate-600 truncate mt-0.5">
                                                                {{ $ag->profissionais->pluck('nome')->implode(', ') }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="abrirModalNovoAgendamento({{ $sala->id }}, '{{ $slot }}', '{{ $dataRefGrade }}')"
                                                    class="w-full py-1.5 rounded border border-dashed border-slate-200 text-slate-400 hover:border-teal-400 hover:text-teal-600 hover:bg-teal-50/50 text-[11px] transition text-center cursor-pointer"
                                                >
                                                    + Agendar
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <!-- CONTEÚDO DA ABA 3: MAPA MENSAL (GESTÃO & OCUPAÇÃO) -->
    @if($aba === 'mensal')
        <div class="space-y-4 sm:space-y-5">
            <!-- Cards Gerenciais com Indicadores Oficiais do Mês -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Volume de Agendamentos -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Volume de Agendamentos</span>
                        <span class="rounded-lg bg-teal-50 p-2 text-teal-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">{{ $metricasMes['total'] }}</span>
                            <span class="text-xs text-slate-500 font-medium">atendimentos</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Somatório de agendamentos ativos no mês</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Aguardando atendimento:</span>
                        <span class="font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md font-mono">{{ $metricasMes['agendados'] }}</span>
                    </div>
                </div>

                <!-- Card 2: Realizados e Presenças -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Atendimentos Realizados</span>
                        <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 font-mono">{{ $metricasMes['realizados'] }}</span>
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                {{ $metricasMes['taxaComparecimento'] }}% comparecimento
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Pacientes com presença confirmada ou atendidos</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Situação:</span>
                        <span class="text-emerald-700 font-medium">Presentes & Atendidos</span>
                    </div>
                </div>

                <!-- Card 3: Faltas e Absenteísmo -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Faltas &amp; Absenteísmo</span>
                        <span class="rounded-lg bg-amber-50 p-2 text-amber-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 font-mono">{{ $metricasMes['faltas'] }}</span>
                            <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                {{ $metricasMes['taxaAbsenteismo'] }}% absenteísmo
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $metricasMes['faltasJustificadas'] }} justificadas • {{ $metricasMes['faltasInjustificadas'] }} injustificadas</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Cancelamentos no mês:</span>
                        <span class="font-semibold text-slate-600 font-mono">{{ $metricasMes['cancelados'] }}</span>
                    </div>
                </div>

                <!-- Card 4: Dias com Atendimento -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dias com Agenda</span>
                        <span class="rounded-lg bg-sky-50 p-2 text-sky-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">{{ $metricasMes['diasComAgenda'] }}</span>
                            <span class="text-xs text-slate-500 font-medium">dias ativos</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Média de {{ number_format($metricasMes['mediaDiaria'], 1, ',', '.') }} atendimentos por dia ativo</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Período:</span>
                        <span class="text-slate-700 font-medium font-mono">{{ $inicioMes->format('d/m') }} a {{ $fimMes->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Barra de Navegação Temporal e Filtros Gerenciais -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Navegação de Mês -->
                <div class="flex items-center gap-2">
                    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 shadow-2xs">
                        <button type="button"
                                wire:click="mesAnterior"
                                title="Mês Anterior"
                                class="rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                            </svg>
                        </button>

                        <div class="px-3 py-1.5 text-xs sm:text-sm font-bold text-slate-900 min-w-[150px] text-center flex items-center justify-center">
                            {{ $mesNome }} de {{ $ano }}
                        </div>

                        <button type="button"
                                wire:click="proximoMes"
                                title="Próximo Mês"
                                class="rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>
                    </div>

                    <button type="button"
                            wire:click="irParaMesAtual"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer">
                        Mês Atual
                    </button>
                </div>

                <!-- Filtros Gerenciais -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Especialidade -->
                    <div class="w-full sm:w-auto min-w-[160px]">
                        <select wire:model.live="filtroEspecialidadeMensal"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="">Todas as Especialidades</option>
                            @foreach($especialidadesDisponiveis as $esp)
                                <option value="{{ $esp }}">{{ $esp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Profissional -->
                    <div class="w-full sm:w-auto min-w-[180px]">
                        <select wire:model.live="filtroProfissionalIdMensal"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="">Todos os Profissionais</option>
                            @foreach($profissionaisDisponiveisMensal as $prof)
                                <option value="{{ $prof->id }}">{{ $prof->nome }} ({{ $prof->especialidade }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Turno -->
                    <div class="w-full sm:w-auto min-w-[140px]">
                        <select wire:model.live="filtroTurnoMensal"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="">Todos os Turnos</option>
                            <option value="MANHA">Manhã (08h-12h)</option>
                            <option value="TARDE">Tarde (13h-17h)</option>
                        </select>
                    </div>

                    @if($filtroEspecialidadeMensal !== '' || $filtroProfissionalIdMensal !== '' || $filtroTurnoMensal !== '')
                        <button type="button"
                                wire:click="limparFiltrosMensal"
                                class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 hover:text-slate-900 transition cursor-pointer">
                            Limpar Filtros
                        </button>
                    @endif
                </div>
            </div>

            <!-- Calendário Mensal Responsivo (Segunda a Domingo) -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <!-- Cabeçalho dos Dias da Semana -->
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <div class="py-3 px-2">
                        <span class="hidden sm:inline">Segunda</span>
                        <span class="sm:hidden">Seg</span>
                    </div>
                    <div class="py-3 px-2 border-l border-slate-200">
                        <span class="hidden sm:inline">Terça</span>
                        <span class="sm:hidden">Ter</span>
                    </div>
                    <div class="py-3 px-2 border-l border-slate-200">
                        <span class="hidden sm:inline">Quarta</span>
                        <span class="sm:hidden">Qua</span>
                    </div>
                    <div class="py-3 px-2 border-l border-slate-200">
                        <span class="hidden sm:inline">Quinta</span>
                        <span class="sm:hidden">Qui</span>
                    </div>
                    <div class="py-3 px-2 border-l border-slate-200">
                        <span class="hidden sm:inline">Sexta</span>
                        <span class="sm:hidden">Sex</span>
                    </div>
                    <div class="py-3 px-2 border-l border-slate-200 text-slate-400 bg-slate-100/60">
                        <span class="hidden sm:inline">Sábado</span>
                        <span class="sm:hidden">Sáb</span>
                    </div>
                    <div class="py-3 px-2 border-l border-slate-200 text-slate-400 bg-slate-100/60">
                        <span class="hidden sm:inline">Domingo</span>
                        <span class="sm:hidden">Dom</span>
                    </div>
                </div>

                <!-- Grade de Células dos Dias -->
                <div class="grid grid-cols-7 divide-x divide-y divide-slate-200">
                    @foreach($diasCalendarioMensal as $dia)
                        <div class="min-h-[115px] sm:min-h-[135px] p-2 sm:p-2.5 flex flex-col justify-between transition {{ $dia['ehMesAtual'] ? 'bg-white' : 'bg-slate-50/60 text-slate-400' }} {{ $dia['ehHoje'] ? 'ring-2 ring-teal-500 ring-inset bg-teal-50/20' : '' }}">
                            <!-- Topo do Dia: Número e Badges -->
                            <div class="flex items-start justify-between gap-1">
                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full text-xs font-bold {{ $dia['ehHoje'] ? 'bg-teal-600 text-white' : ($dia['ehMesAtual'] ? 'text-slate-800' : 'text-slate-400') }}">
                                    {{ $dia['dia'] }}
                                </span>

                                @if($dia['ehHoje'])
                                    <span class="hidden sm:inline-flex rounded-full bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800">
                                        Hoje
                                    </span>
                                @endif

                                @if($dia['total'] > 0)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] sm:text-[11px] font-bold font-mono text-slate-700">
                                        {{ $dia['total'] }} <span class="hidden sm:inline ml-0.5">atend.</span>
                                    </span>
                                @endif
                            </div>

                            <!-- Conteúdo Central: Indicadores e Situações -->
                            <div class="my-1.5 space-y-1">
                                @if($dia['total'] > 0)
                                    <div class="flex flex-col gap-1 text-[10px] sm:text-[11px]">
                                        @if($dia['realizados'] > 0)
                                            <span class="inline-flex items-center justify-between rounded px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/70 font-medium">
                                                <span class="truncate">Realizados</span>
                                                <span class="font-bold font-mono ml-1">{{ $dia['realizados'] }}</span>
                                            </span>
                                        @endif

                                        @if($dia['agendados'] > 0)
                                            <span class="inline-flex items-center justify-between rounded px-1.5 py-0.5 bg-sky-50 text-sky-700 border border-sky-200/70 font-medium">
                                                <span class="truncate">Agendados</span>
                                                <span class="font-bold font-mono ml-1">{{ $dia['agendados'] }}</span>
                                            </span>
                                        @endif

                                        @if($dia['faltas'] > 0)
                                            <span class="inline-flex items-center justify-between rounded px-1.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200/70 font-medium">
                                                <span class="truncate">Faltas</span>
                                                <span class="font-bold font-mono ml-1">{{ $dia['faltas'] }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center py-2 text-center">
                                        @if($dia['ehFimDeSemana'])
                                            <span class="text-[10px] text-slate-400 italic">Folga / Sem Escala</span>
                                        @else
                                            <span class="text-[10px] text-slate-400">Sem agendamentos</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Rodapé do Dia: Ação Abrir Dia e Indicador Visual de Volume -->
                            <div class="pt-1 border-t border-slate-100 flex items-center justify-between gap-1">
                                @if($dia['ehMesAtual'] && ! $dia['ehFimDeSemana'])
                                    <button type="button"
                                            wire:click="selecionarDiaDoMes('{{ $dia['data'] }}', 'dia')"
                                            title="Abrir Agenda de {{ \Carbon\CarbonImmutable::parse($dia['data'])->format('d/m/Y') }}"
                                            class="text-[10px] sm:text-[11px] font-semibold text-teal-700 hover:text-teal-900 transition flex items-center gap-1 group cursor-pointer">
                                        <span>Abrir dia</span>
                                        <span class="group-hover:translate-x-0.5 transition-transform">→</span>
                                    </button>
                                @else
                                    <span></span>
                                @endif

                                @if($dia['total'] > 0)
                                    <div class="flex items-center gap-0.5" title="Volume: {{ $dia['total'] }} atendimentos">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $dia['nivelIntensidade'] >= 1 ? 'bg-teal-500' : 'bg-slate-200' }}"></span>
                                        <span class="h-1.5 w-1.5 rounded-full {{ $dia['nivelIntensidade'] >= 2 ? 'bg-teal-500' : 'bg-slate-200' }}"></span>
                                        <span class="h-1.5 w-1.5 rounded-full {{ $dia['nivelIntensidade'] >= 3 ? 'bg-teal-500' : 'bg-slate-200' }}"></span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Legenda de Cores e Nota de Conformidade LGPD -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs text-slate-600">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="font-semibold text-slate-800">Legenda:</span>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span>Realizados (Presente / Atendido)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                        <span>Agendados (Aguardando atendimento)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        <span>Faltas (Justificadas / Injustificadas)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                        <span>Cancelados (Excluídos da carga ativa)</span>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 flex items-center gap-1.5 max-w-md">
                    <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                    <span>Mapa gerencial agregado: dados clínicos e nominais de pacientes são protegidos e visíveis apenas na Grade Operacional e no Prontuário.</span>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL NOVO AGENDAMENTO -->
    @if($modalNovoAgendamentoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden text-xs max-h-[90vh] flex flex-col">
                <div class="p-4 bg-linear-to-r from-slate-900 to-teal-950 text-white flex items-center justify-between">
                    <div>
                        <h2 class="text-sm sm:text-base font-bold">Novo Agendamento</h2>
                        <p class="text-xs text-slate-300">Reserve o horário e selecione paciente, sala e equipe</p>
                    </div>
                    <button type="button" wire:click="fecharModalNovoAgendamento" class="text-slate-400 hover:text-white cursor-pointer text-base">✕</button>
                </div>

                <div class="p-5 overflow-y-auto space-y-4">
                    <!-- Seletor de Paciente Sob Demanda -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Paciente *</label>
                        @if($pacienteSelecionado)
                            <div class="flex items-center justify-between p-2.5 rounded-lg border {{ $alertaPaciente ? 'border-amber-300 bg-amber-50/60' : 'border-teal-200 bg-teal-50' }} text-slate-900 transition">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold">{{ $pacienteSelecionado['nome'] }}</span>
                                        @if($alertaPaciente)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                Pendência Cadastral
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 text-slate-500 text-[11px] mt-0.5">
                                        <span>CPF: {{ $pacienteSelecionado['cpf'] ?? 'Não informado' }}</span>
                                        <span>CNS: {{ $pacienteSelecionado['cns'] ?? 'Não informado' }}</span>
                                        <span>Nasc: {{ $pacienteSelecionado['data_nascimento'] ?? 'Não informado' }}</span>
                                    </div>
                                </div>
                                <button type="button" wire:click="limparPacienteSelecionado" class="text-rose-600 font-semibold hover:underline cursor-pointer shrink-0 ml-2">
                                    Trocar
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
                            <div class="relative">
                                <input
                                    type="text"
                                    wire:model.live.debounce.400ms="pesquisaPaciente"
                                    placeholder="Digite o nome, CPF ou CNS do paciente..."
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                                >
                                @if(mb_strlen(trim($pesquisaPaciente)) >= 3)
                                    <div class="absolute z-50 left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl divide-y divide-slate-100">
                                        @forelse($pacientesResultados as $p)
                                            <button
                                                type="button"
                                                wire:click="selecionarPaciente({{ $p['id'] }})"
                                                class="w-full p-2.5 text-left hover:bg-teal-50 flex items-center justify-between cursor-pointer"
                                            >
                                                <div>
                                                    <span class="font-bold text-slate-800">{{ $p['nome'] }}</span>
                                                    <span class="text-[11px] text-slate-500 ml-2">CPF: {{ $p['cpf'] ?? '—' }} • CNS: {{ $p['cns'] ?? '—' }}</span>
                                                </div>
                                                <span class="text-teal-600 font-semibold">Selecionar →</span>
                                            </button>
                                        @empty
                                            <div class="p-3 text-center text-slate-500">Nenhum paciente encontrado.</div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if(! $alertaPaciente)
                            @error('novo_paciente_id') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    <!-- Sala e Data -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Sala / Consultório *</label>
                            <select wire:model="novo_sala_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="">Selecione uma sala...</option>
                                @foreach($salas as $sala)
                                    <option value="{{ $sala->id }}">{{ $sala->nome }}</option>
                                @endforeach
                            </select>
                            @error('novo_sala_id') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Data do Atendimento *</label>
                            <input type="date" wire:model.live="novo_data" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            @error('novo_data') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Horário Início / Fim / Tipo -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Hora Início *</label>
                            <input type="time" wire:model.live="novo_hora_inicio" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            @error('novo_hora_inicio') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Hora Fim *</label>
                            <input type="time" wire:model="novo_hora_fim" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            @error('novo_hora_fim') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tipo de Atendimento *</label>
                            <select wire:model="novo_tipo_atendimento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="INDIVIDUAL">Individual</option>
                                <option value="COMPARTILHADO">Compartilhado (Co-atendimento)</option>
                                <option value="TRABALHO_INTERNO">Trabalho Interno</option>
                            </select>
                        </div>
                    </div>

                    <!-- Profissionais Participantes -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Profissional(is) Escalado(s) *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 border border-slate-200 rounded-lg p-2.5 max-h-36 overflow-y-auto">
                            @foreach($profissionais as $prof)
                                <label class="flex items-center gap-2 p-1.5 rounded hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" wire:model="novo_profissionais" value="{{ $prof->id }}" class="rounded text-teal-600 focus:ring-teal-500">
                                    <span class="text-slate-800">{{ $prof->nome }}</span>
                                    <span class="text-[11px] text-slate-400">({{ $prof->especialidade }})</span>
                                </label>
                            @endforeach
                        </div>
                        @error('novo_profissionais') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Recorrência e Observação -->
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="novo_recorrente" class="rounded text-teal-600 focus:ring-teal-500">
                            <span class="font-semibold text-slate-700">Repetir semanalmente</span>
                        </label>

                        @if($novo_recorrente)
                            <div class="flex items-center gap-2">
                                <span class="text-slate-600">por</span>
                                <input type="number" wire:model="novo_semanas_recorrencia" min="1" max="12" class="w-16 rounded-lg border border-slate-300 px-2 py-1 text-xs">
                                <span class="text-slate-600">semanas</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Observações (opcional)</label>
                        <input type="text" wire:model="novo_observacao" placeholder="Informações clínicas ou de transporte..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" wire:click="fecharModalNovoAgendamento" class="px-4 py-2 rounded-lg border border-slate-300 bg-white font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" wire:click="salvarNovoAgendamento" class="px-4 py-2 rounded-lg bg-teal-600 font-semibold text-white hover:bg-teal-700 cursor-pointer shadow-xs">
                        Salvar Agendamento
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

