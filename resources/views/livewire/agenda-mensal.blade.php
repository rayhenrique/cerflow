<div class="space-y-5 sm:space-y-6">
    <!-- Cabeçalho Principal com Título e Ações Rápidas -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm3-3h.008v.008H15V12zm0 3h.008v.008H15V15zm-6-3h.008v.008H9V12zm0 3h.008v.008H9V15z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Mapa Mensal da Agenda</h2>
                <p class="text-xs text-slate-500 mt-0.5">Visão panorâmica para a gestão: ocupação diária, capacidade de vagas e lotação do CER</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('grade.semanal') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition cursor-pointer">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Grade Operacional</span>
            </a>

            @can('create', \App\Models\Agendamento::class)
                <a href="{{ route('grade.semanal', ['novo' => 1]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-teal-500 transition cursor-pointer">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span>Novo Agendamento</span>
                </a>
            @endcan
        </div>
    </div>

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
                <p class="text-[11px] text-slate-400 mt-1">Média de {{ number_format($metricasMes['mediaDiaria'], 1, ',', '.') }} agendamentos por dia ativo</p>
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
                <label for="filtroEspecialidade" class="sr-only">Especialidade</label>
                <select id="filtroEspecialidade"
                        wire:model.live="filtroEspecialidade"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    <option value="">Todas as Especialidades</option>
                    @foreach($especialidadesDisponiveis as $esp)
                        <option value="{{ $esp }}">{{ $esp }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Profissional -->
            <div class="w-full sm:w-auto min-w-[180px]">
                <label for="filtroProfissionalId" class="sr-only">Profissional</label>
                <select id="filtroProfissionalId"
                        wire:model.live="filtroProfissionalId"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    <option value="">Todos os Profissionais</option>
                    @foreach($profissionaisDisponiveis as $prof)
                        <option value="{{ $prof->id }}">{{ $prof->nome }} ({{ $prof->profissao_ou_especialidade }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Turno -->
            <div class="w-full sm:w-auto min-w-[140px]">
                <label for="filtroTurno" class="sr-only">Turno</label>
                <select id="filtroTurno"
                        wire:model.live="filtroTurno"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-2xs focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    <option value="">Todos os Turnos</option>
                    <option value="MANHA">Manhã (08h-12h)</option>
                    <option value="TARDE">Tarde (13h-17h)</option>
                </select>
            </div>

            @if($filtroEspecialidade !== '' || $filtroProfissionalId !== '' || $filtroTurno !== '')
                <button type="button"
                        wire:click="limparFiltros"
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
            @foreach($diasCalendario as $dia)
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
                            <!-- Distribuição por Situação -->
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

                    <!-- Rodapé do Dia: Botão Abrir Dia e Indicador Visual de Volume -->
                    <div class="pt-1 border-t border-slate-100 flex items-center justify-between gap-1">
                        @if($dia['ehMesAtual'] && ! $dia['ehFimDeSemana'])
                            <a href="{{ route('grade.semanal', ['data' => $dia['data']]) }}"
                               title="Abrir Grade Operacional de {{ \Carbon\CarbonImmutable::parse($dia['data'])->format('d/m/Y') }}"
                               class="text-[10px] sm:text-[11px] font-semibold text-teal-700 hover:text-teal-900 transition flex items-center gap-1 group">
                                <span>Abrir dia</span>
                                <span class="group-hover:translate-x-0.5 transition-transform">→</span>
                            </a>
                        @else
                            <span></span>
                        @endif

                        <!-- Barra Visual de Intensidade de Carga -->
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
        <!-- Legenda -->
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

        <!-- Privacidade / LGPD -->
        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 max-w-md">
            <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            <span>Mapa gerencial agregado: dados clínicos e nominais de pacientes são protegidos e visíveis apenas na Grade Operacional e no Prontuário.</span>
        </div>
    </div>
</div>
