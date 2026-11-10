<div class="space-y-6">
    <!-- CABEÇALHO DO MÓDULO -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-teal-700 uppercase tracking-wider mb-1">
                <span>Gestão Assistencial & SUS</span>
                <span>•</span>
                <span class="font-mono text-slate-500">{{ $filtros['descricao'] }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Relatórios e Indicadores Gerenciais
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                Acompanhamento analítico da operação, absenteísmo, perfil da população assistida e produção ambulatorial SUS.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 print:hidden">
            <!-- Botão Filtros Mobile -->
            <button type="button"
                    wire:click="toggleFiltrosMobile"
                    class="lg:hidden inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 active:scale-95 transition">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span>Filtros</span>
            </button>

            <!-- Botão Imprimir / Salvar em PDF -->
            <button type="button"
                    onclick="window.print()"
                    title="Imprimir visualização formatada em folha A4 ou salvar como PDF"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 active:scale-95 transition">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656h10.5z"/>
                </svg>
                <span class="hidden sm:inline">Imprimir / PDF</span>
            </button>

            <!-- Botão Exportar CSV -->
            <button type="button"
                    wire:click="exportarCsv"
                    title="Exportar dados da aba atual para planilha CSV compatível com Excel"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-teal-800 active:scale-95 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Exportar CSV</span>
            </button>
        </div>
    </div>

    <!-- PAINEL DE FILTROS GLOBAIS -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-4 print:hidden {{ $filtrosAbertosMobile ? 'block' : 'hidden lg:block' }}">
        <!-- Linha 1: Períodos predefinidos em formato de pílulas -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">Período:</span>
                @php
                    $periodos = [
                        'hoje' => 'Hoje',
                        'ultimos_7_dias' => 'Últimos 7 dias',
                        'mes_atual' => 'Mês Atual',
                        'mes_anterior' => 'Mês Anterior',
                        'ano_atual' => 'Ano Atual',
                    ];
                @endphp
                @foreach ($periodos as $chave => $rotulo)
                    <button type="button"
                            wire:click="aplicarPeriodoPredefinido('{{ $chave }}')"
                            class="rounded-lg px-2.5 py-1 text-xs font-medium transition cursor-pointer {{ $periodoPredefinido === $chave ? 'bg-teal-700 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $rotulo }}
                    </button>
                @endforeach
            </div>

            <!-- Botão Limpar Filtros -->
            <button type="button"
                    wire:click="limparFiltros"
                    class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition inline-flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Limpar filtros</span>
            </button>
        </div>

        <!-- Linha 2: Campos de seleção detalhada -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <!-- Data Início -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Data Início</label>
                <input type="date"
                       wire:model.live="dataInicio"
                       wire:change="atualizarPeriodoPersonalizado"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
            </div>

            <!-- Data Fim -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Data Fim</label>
                <input type="date"
                       wire:model.live="dataFim"
                       wire:change="atualizarPeriodoPersonalizado"
                       class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
            </div>

            <!-- Profissional -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Profissional</label>
                <select wire:model.live="profissionalId"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                    <option value="">Todos os profissionais</option>
                    @foreach ($listaProfissionais as $prof)
                        <option value="{{ $prof->id }}">{{ $prof->nome }} ({{ $prof->especialidade ?? 'Geral' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Especialidade -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Especialidade</label>
                <select wire:model.live="especialidade"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                    <option value="">Todas as especialidades</option>
                    @foreach ($listaEspecialidades as $esp)
                        <option value="{{ $esp }}">{{ $esp }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sala / Espaço -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Sala / Consultório</label>
                <select wire:model.live="salaId"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                    <option value="">Todas as salas</option>
                    @foreach ($listaSalas as $s)
                        <option value="{{ $s->id }}">{{ $s->nome }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($abaAtiva === 'producao-bpai')
            <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-3">
                <span class="text-xs font-bold text-slate-700">Competência BPA-i (SIA/SUS):</span>
                <select wire:model.live="competencia"
                        class="rounded-lg border border-teal-300 bg-teal-50/50 px-3 py-1.5 text-xs font-bold text-teal-900 focus:border-teal-600">
                    @foreach ($competenciasDisponiveis as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
                <span class="text-[11px] text-slate-500">Mês de processamento e faturamento ambulatorial</span>
            </div>
        @endif
    </div>

    <!-- NAVEGAÇÃO ENTRE ABAS -->
    <div class="border-b border-slate-200 print:hidden">
        <nav class="-mb-px flex space-x-2 sm:space-x-6 overflow-x-auto text-xs sm:text-sm font-medium">
            @php
                $todasAbas = [
                    'visao-geral' => ['nome' => 'Visão Geral', 'icone' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                    'atendimentos' => ['nome' => 'Atendimentos & Faltas', 'icone' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm3-3h.008v.008H15V12zm0 3h.008v.008H15V15zm-6-3h.008v.008H9V12zm0 3h.008v.008H9V15z'],
                    'pacientes' => ['nome' => 'Pacientes & Qualidade', 'icone' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
                    'profissionais' => ['nome' => 'Profissionais & Equipe', 'icone' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
                    'salas' => ['nome' => 'Salas & Ocupação', 'icone' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                    'producao-bpai' => ['nome' => 'Produção SUS / BPA-i', 'icone' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
                ];
            @endphp

            @foreach ($todasAbas as $chave => $item)
                @if (in_array($chave, $abasPermitidas, true))
                    <button type="button"
                            wire:click="selecionarAba('{{ $chave }}')"
                            class="inline-flex items-center gap-2 py-3 px-1 border-b-2 font-bold whitespace-nowrap transition cursor-pointer {{ $abaAtiva === $chave ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                        <svg class="h-4 w-4 shrink-0 {{ $abaAtiva === $chave ? 'text-teal-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icone'] }}"/>
                        </svg>
                        <span>{{ $item['nome'] }}</span>
                    </button>
                @endif
            @endforeach
        </nav>
    </div>

    <!-- ========================================================================= -->
    <!-- ABA 1: VISÃO GERAL GERENCIAL                                              -->
    <!-- ========================================================================= -->
    @if ($abaAtiva === 'visao-geral')
        <div class="space-y-6">
            <!-- CARDS DE INDICADORES PRINCIPAIS -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Total de Agendamentos -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Agendamentos Totais</span>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($visaoGeral['total_agendamentos'], 0, ',', '.') }}</span>
                        <span class="text-xs font-medium text-slate-500">horários</span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500">
                        {{ $visaoGeral['pacientes_distintos'] }} pacientes únicos atendidos
                    </div>
                </div>

                <!-- Atendimentos Concluídos -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
                    <span class="text-xs font-semibold text-teal-700 uppercase tracking-wider block">Atendidos (Concluídos)</span>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-teal-800">{{ number_format($visaoGeral['atendidos'], 0, ',', '.') }}</span>
                        <span class="text-xs font-semibold text-teal-600">finalizados</span>
                    </div>
                    <div class="mt-2 text-[11px] text-teal-700 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                        <span>{{ $visaoGeral['presencas'] }} presentes aguardando consulta</span>
                    </div>
                </div>

                <!-- Taxa de Comparecimento -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Taxa de Comparecimento</span>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-emerald-800">{{ $visaoGeral['taxa_comparecimento'] }}%</span>
                        <span class="text-xs text-slate-500">efetividade</span>
                    </div>
                    <!-- Barra de progresso visual -->
                    <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ min(100, $visaoGeral['taxa_comparecimento']) }}%"></div>
                    </div>
                </div>

                <!-- Absenteísmo (Faltas Totais) -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider block">Taxa de Absenteísmo</span>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-extrabold text-rose-800">{{ $visaoGeral['taxa_absenteismo'] }}%</span>
                        <span class="text-xs font-semibold text-rose-600">{{ $visaoGeral['total_faltas'] }} faltas</span>
                    </div>
                    <!-- Barra de progresso visual -->
                    <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-rose-500 h-2 rounded-full" style="width: {{ min(100, $visaoGeral['taxa_absenteismo']) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- CARDS COMPLEMENTARES: REGISTROS CLÍNICOS E SUS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-mono text-teal-300 uppercase">Prontuário Multiprofissional</span>
                        <div class="text-xl sm:text-2xl font-bold mt-1">{{ number_format($visaoGeral['evolucoes_assinadas'], 0, ',', '.') }}</div>
                        <span class="text-xs text-slate-400">Evoluções com assinatura eletrônica</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center font-bold text-sm">
                        PEP
                    </div>
                </div>

                <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-mono text-teal-300 uppercase">Faturamento BPA-i</span>
                        <div class="text-xl sm:text-2xl font-bold mt-1">{{ number_format($visaoGeral['producoes_registradas'], 0, ',', '.') }}</div>
                        <span class="text-xs text-slate-400">Procedimentos ambulatoriais gerados</span>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center font-bold text-sm">
                        SUS
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase">Modalidades de Atendimento</span>
                        <div class="text-sm font-semibold text-slate-800 mt-2 space-y-0.5">
                            <div>Individuais: <span class="font-bold text-teal-700">{{ $visaoGeral['individuais'] }}</span></div>
                            <div>Compartilhados: <span class="font-bold text-indigo-700">{{ $visaoGeral['compartilhados'] }}</span></div>
                            <div>Trabalho Interno: <span class="font-bold text-slate-600">{{ $visaoGeral['trabalho_interno'] }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DISTRIBUIÇÃO DOS STATUS E FALTAS POR DIA -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Status dos Horários -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Composição dos Horários Marcados
                    </h3>
                    <div class="space-y-3">
                        @php
                            $statusBarras = [
                                ['label' => 'Atendimentos Concluídos', 'valor' => $visaoGeral['atendidos'], 'cor' => 'bg-emerald-600', 'txt' => 'text-emerald-700'],
                                ['label' => 'Presenças Confirmadas (Aguardando)', 'valor' => $visaoGeral['presencas'], 'cor' => 'bg-amber-500', 'txt' => 'text-amber-700'],
                                ['label' => 'Agendados (Sem desfecho)', 'valor' => $visaoGeral['aguardando'], 'cor' => 'bg-slate-400', 'txt' => 'text-slate-700'],
                                ['label' => 'Faltas Injustificadas', 'valor' => $visaoGeral['faltas_injustificadas'], 'cor' => 'bg-rose-600', 'txt' => 'text-rose-700'],
                                ['label' => 'Faltas Justificadas', 'valor' => $visaoGeral['faltas_justificadas'], 'cor' => 'bg-orange-400', 'txt' => 'text-orange-700'],
                                ['label' => 'Cancelamentos Formalizados', 'valor' => $visaoGeral['cancelamentos'], 'cor' => 'bg-slate-300', 'txt' => 'text-slate-600'],
                            ];
                            $baseTotal = max(1, $visaoGeral['total_agendamentos']);
                        @endphp

                        @foreach ($statusBarras as $b)
                            @php $pct = round(($b['valor'] / $baseTotal) * 100, 1); @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-medium">
                                    <span class="text-slate-700">{{ $b['label'] }}</span>
                                    <span class="{{ $b['txt'] }} font-bold">{{ $b['valor'] }} ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="{{ $b['cor'] }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Faltas por Dia da Semana -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Faltas por Dia da Semana
                    </h3>
                    <div class="space-y-3">
                        @php
                            $maxFaltas = max(1, collect($visaoGeral['dados_faltas_dia'])->max('total'));
                        @endphp
                        @foreach ($visaoGeral['dados_faltas_dia'] as $fd)
                            @php
                                $largura = round(($fd['total'] / $maxFaltas) * 100);
                            @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="font-bold text-slate-800">{{ $fd['rotulo'] }}</span>
                                    <span class="text-slate-600 font-mono">
                                        Total: <strong class="text-rose-700">{{ $fd['total'] }}</strong>
                                        <span class="text-[11px] text-slate-400">({{ $fd['justificadas'] }} just. / {{ $fd['injustificadas'] }} injust.)</span>
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden flex">
                                    @if ($fd['total'] > 0)
                                        <div class="bg-orange-400 h-2.5" style="width: {{ round(($fd['justificadas'] / $maxFaltas) * 100) }}%"></div>
                                        <div class="bg-rose-600 h-2.5" style="width: {{ round(($fd['injustificadas'] / $maxFaltas) * 100) }}%"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="pt-2 text-[11px] text-slate-500 flex items-center gap-4">
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-orange-400"></span> Justificadas</span>
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-600"></span> Injustificadas</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- ABA 2: ATENDIMENTOS & ABSENTEÍSMO                                         -->
    <!-- ========================================================================= -->
    @if ($abaAtiva === 'atendimentos')
        <div class="space-y-6">
            <!-- ANÁLISE DE ABSENTEÍSMO: CARDS DE DESTAQUE -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase">Faltas no Turno Manhã</span>
                    <div class="text-2xl font-bold text-slate-800 mt-1">{{ $absenteismo['turno_manha'] }}</div>
                    <span class="text-[11px] text-slate-500">Horários antes de 12:00</span>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase">Faltas no Turno Tarde</span>
                    <div class="text-2xl font-bold text-slate-800 mt-1">{{ $absenteismo['turno_tarde'] }}</div>
                    <span class="text-[11px] text-slate-500">Horários a partir de 12:00</span>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-700 uppercase">Taxa Consolidada</span>
                    <div class="text-2xl font-bold text-rose-800 mt-1">{{ $visaoGeral['taxa_absenteismo'] }}%</div>
                    <span class="text-[11px] text-slate-500">{{ $visaoGeral['total_faltas'] }} faltas de {{ $visaoGeral['total_com_desfecho'] }} atendimentos previstos</span>
                </div>
            </div>

            <!-- PACIENTES COM FALTAS RECORRENTES (>= 2 FALTAS) -->
            @if(count($absenteismo['pacientes_recorrentes']) > 0)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-rose-600"></span>
                                <span>Pacientes com Faltas Recorrentes no Período (2 ou mais faltas)</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Identificação para busca ativa, contato pela recepção ou apoio da UBS de referência.</p>
                        </div>
                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 border border-rose-200">
                            {{ count($absenteismo['pacientes_recorrentes']) }} pacientes identificados
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">Paciente</th>
                                    <th class="py-2.5 px-3">Telefone</th>
                                    <th class="py-2.5 px-3">UBS de Referência</th>
                                    <th class="py-2.5 px-3 text-center">Faltas Justificadas</th>
                                    <th class="py-2.5 px-3 text-center">Faltas Injustificadas</th>
                                    <th class="py-2.5 px-3 text-right">Total Faltas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($absenteismo['pacientes_recorrentes'] as $pr)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-2 px-3 font-semibold text-slate-900">{{ $pr->nome }}</td>
                                        <td class="py-2 px-3 font-mono text-slate-600">{{ $pr->telefone_contato ?? 'Não informado' }}</td>
                                        <td class="py-2 px-3">{{ $pr->ubs_referencia ?? 'Não informada' }}</td>
                                        <td class="py-2 px-3 text-center text-orange-700 font-bold">{{ $pr->justificadas }}</td>
                                        <td class="py-2 px-3 text-center text-rose-700 font-bold">{{ $pr->injustificadas }}</td>
                                        <td class="py-2 px-3 text-right">
                                            <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-0.5 text-xs font-extrabold text-rose-800">
                                                {{ $pr->total_faltas }} faltas
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- TABELA DETALHADA DE AGENDAMENTOS (PAGINADA) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            Relação Operacional de Atendimentos
                        </h3>
                        <p class="text-xs text-slate-500">Listagem analítica com paginação rápida e filtros aplicados.</p>
                    </div>

                    <!-- Filtro rápido por status -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Filtrar status:</label>
                        <select wire:model.live="statusFiltro"
                                class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-700 focus:border-teal-600">
                            <option value="">Todos os status</option>
                            <option value="AGENDADO">Agendado</option>
                            <option value="PRESENTE">Presente</option>
                            <option value="ATENDIDO">Atendido</option>
                            <option value="FALTA_JUSTIFICADA">Falta Justificada</option>
                            <option value="FALTA_INJUSTIFICADA">Falta Injustificada</option>
                            <option value="CANCELADO">Cancelado</option>
                        </select>
                    </div>
                </div>

                <!-- Tabela Desktop -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Data / Horário</th>
                                <th class="py-2.5 px-3">Paciente</th>
                                <th class="py-2.5 px-3">Profissional / Especialidade</th>
                                <th class="py-2.5 px-3">Sala</th>
                                <th class="py-2.5 px-3">Modalidade</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($atendimentosPaginados as $agendamento)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-2.5 px-3 whitespace-nowrap">
                                        <div class="font-bold text-slate-900">{{ $agendamento->data_atendimento?->format('d/m/Y') }}</div>
                                        <div class="text-[11px] text-slate-500 font-mono">{{ substr((string) $agendamento->hora_inicio, 0, 5) }} - {{ substr((string) $agendamento->hora_fim, 0, 5) }}</div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-semibold text-slate-900">{{ $agendamento->paciente->nome ?? '-' }}</div>
                                        @if (!empty($agendamento->paciente->cns))
                                            <div class="text-[11px] text-slate-400 font-mono">CNS {{ substr($agendamento->paciente->cns, 0, 3) }}.***.***-{{ substr($agendamento->paciente->cns, -2) }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @forelse ($agendamento->profissionais as $prof)
                                            <div class="font-medium text-slate-800">{{ $prof->nome }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $prof->especialidade ?? 'Geral' }}</div>
                                        @empty
                                            <span class="text-slate-400 italic">Não atribuído</span>
                                        @endforelse
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-slate-700 font-medium">{{ $agendamento->sala->nome ?? '-' }}</span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-medium text-slate-600">{{ $agendamento->tipo_atendimento }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        @php
                                            $badgeClasses = match($agendamento->status) {
                                                'ATENDIDO' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'PRESENTE' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'AGENDADO' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                'FALTA_JUSTIFICADA' => 'bg-orange-100 text-orange-800 border-orange-200',
                                                'FALTA_INJUSTIFICADA' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                'CANCELADO' => 'bg-slate-200 text-slate-600 border-slate-300',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeClasses }}">
                                            {{ $agendamento->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        Nenhum atendimento encontrado para os filtros selecionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Tabela Resumida em Cartões para Mobile -->
                <div class="sm:hidden space-y-3">
                    @forelse ($atendimentosPaginados as $agendamento)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $agendamento->data_atendimento?->format('d/m/Y') }} às {{ substr((string) $agendamento->hora_inicio, 0, 5) }}</span>
                                <span class="rounded-full px-2 py-0.5 font-bold text-[10px] {{ $agendamento->status === 'ATENDIDO' ? 'bg-emerald-100 text-emerald-800' : ($agendamento->status === 'PRESENTE' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700') }}">
                                    {{ $agendamento->status }}
                                </span>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800">{{ $agendamento->paciente->nome ?? '-' }}</div>
                                <div class="text-[11px] text-slate-500">Sala: {{ $agendamento->sala->nome ?? '-' }} • {{ $agendamento->tipo_atendimento }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400 text-xs">Nenhum atendimento encontrado.</div>
                    @endforelse
                </div>

                <!-- Paginação -->
                <div class="pt-3 border-t border-slate-100">
                    {{ $atendimentosPaginados->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- ABA 3: PACIENTES & QUALIDADE CADASTRAL                                    -->
    <!-- ========================================================================= -->
    @if ($abaAtiva === 'pacientes')
        <div class="space-y-6">
            <!-- CARDS DE POPULAÇÃO -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase">População Ativa Cadastrada</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                        {{ number_format($dadosPacientes['total_ativos'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-slate-500">Pacientes cadastrados no CER</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <span class="text-xs font-semibold text-teal-700 uppercase">Novos Cadastros no Período</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-teal-800 mt-1">
                        {{ number_format($dadosPacientes['novos_periodo'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-slate-500">Inscritos entre as datas selecionadas</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <span class="text-xs font-semibold text-indigo-700 uppercase">Atendidos Efetivamente</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-indigo-800 mt-1">
                        {{ number_format($dadosPacientes['atendidos_periodo'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-slate-500">Pacientes com presença ou atendimento no período</span>
                </div>
            </div>

            <!-- AUDITORIA DA QUALIDADE CADASTRAL (COMPLETUDE) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            Indicadores de Qualidade Cadastral da População
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Monitoramento de dados essenciais para o faturamento BPA-i e integração com a Atenção Primária à Saúde (APS).
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- CNS -->
                    <div class="rounded-xl border border-slate-200 p-4 space-y-2 bg-slate-50/50">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-700">Cartão SUS (CNS)</span>
                            <span class="font-bold text-teal-700">{{ $dadosPacientes['perc_cns'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-600 h-2 rounded-full" style="width: {{ $dadosPacientes['perc_cns'] }}%"></div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            <strong>{{ number_format($dadosPacientes['sem_cns'], 0, ',', '.') }}</strong> pacientes sem Cartão SUS
                        </div>
                    </div>

                    <!-- Data Nascimento -->
                    <div class="rounded-xl border border-slate-200 p-4 space-y-2 bg-slate-50/50">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-700">Data de Nascimento</span>
                            <span class="font-bold text-teal-700">{{ $dadosPacientes['perc_nascimento'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-600 h-2 rounded-full" style="width: {{ $dadosPacientes['perc_nascimento'] }}%"></div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            <strong>{{ number_format($dadosPacientes['sem_nascimento'], 0, ',', '.') }}</strong> pacientes sem data
                        </div>
                    </div>

                    <!-- UBS Referência -->
                    <div class="rounded-xl border border-slate-200 p-4 space-y-2 bg-slate-50/50">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-700">UBS de Referência</span>
                            <span class="font-bold text-teal-700">{{ $dadosPacientes['perc_ubs'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-600 h-2 rounded-full" style="width: {{ $dadosPacientes['perc_ubs'] }}%"></div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            <strong>{{ number_format($dadosPacientes['sem_ubs'], 0, ',', '.') }}</strong> sem vínculo de UBS
                        </div>
                    </div>

                    <!-- ACS -->
                    <div class="rounded-xl border border-slate-200 p-4 space-y-2 bg-slate-50/50">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-700">Agente de Saúde (ACS)</span>
                            <span class="font-bold text-teal-700">{{ $dadosPacientes['perc_acs'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-teal-600 h-2 rounded-full" style="width: {{ $dadosPacientes['perc_acs'] }}%"></div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            <strong>{{ number_format($dadosPacientes['sem_acs'], 0, ',', '.') }}</strong> sem ACS identificado
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAIXAS ETÁRIAS E MODALIDADES DE REABILITAÇÃO -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Faixas Etárias -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Distribuição por Faixa Etária
                    </h3>
                    <div class="space-y-3">
                        @php
                            $faixas = [
                                ['label' => 'Crianças (0 a 12 anos)', 'qtd' => $dadosPacientes['faixas_etarias']['criancas']],
                                ['label' => 'Adolescentes (13 a 17 anos)', 'qtd' => $dadosPacientes['faixas_etarias']['adolescentes']],
                                ['label' => 'Adultos (18 a 59 anos)', 'qtd' => $dadosPacientes['faixas_etarias']['adultos']],
                                ['label' => 'Idosos (60 anos ou mais)', 'qtd' => $dadosPacientes['faixas_etarias']['idosos']],
                            ];
                            $baseIdade = max(1, $dadosPacientes['total_ativos']);
                        @endphp
                        @foreach ($faixas as $f)
                            @php $pct = round(($f['qtd'] / $baseIdade) * 100, 1); @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="font-medium text-slate-700">{{ $f['label'] }}</span>
                                    <span class="font-bold text-slate-900">{{ number_format($f['qtd'], 0, ',', '.') }} ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-teal-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Modalidades de Reabilitação -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Modalidades de Reabilitação Assistidas
                    </h3>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/50">
                            <span class="font-bold text-slate-500 uppercase">Reabilitação Física</span>
                            <div class="text-xl font-bold text-slate-900 mt-1">{{ $dadosPacientes['modalidades']['FISICA'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/50">
                            <span class="font-bold text-slate-500 uppercase">Intelectual / TEA</span>
                            <div class="text-xl font-bold text-slate-900 mt-1">{{ $dadosPacientes['modalidades']['INTELECTUAL'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/50">
                            <span class="font-bold text-slate-500 uppercase">Auditiva</span>
                            <div class="text-xl font-bold text-slate-900 mt-1">{{ $dadosPacientes['modalidades']['AUDITIVA'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/50">
                            <span class="font-bold text-slate-500 uppercase">Visual</span>
                            <div class="text-xl font-bold text-slate-900 mt-1">{{ $dadosPacientes['modalidades']['VISUAL'] }}</div>
                        </div>
                    </div>

                    <!-- Origem do Cadastro -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                        <span>Origem e-SUS / APS: <strong>{{ $dadosPacientes['origem_esus'] }}</strong></span>
                        <span>Cadastro Manual: <strong>{{ $dadosPacientes['origem_manual'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- ABA 4: PROFISSIONAIS & ESPECIALIDADES                                     -->
    <!-- ========================================================================= -->
    @if ($abaAtiva === 'profissionais')
        <div class="space-y-6">
            <!-- CARDS DE TOTAIS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase">Equipe Ativa</span>
                    <div class="text-2xl font-bold text-slate-900 mt-1">{{ $dadosProfissionais['total_profissionais_ativos'] }}</div>
                    <span class="text-xs text-slate-500">Profissionais em exercício</span>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-teal-700 uppercase">Evoluções Clínicas Assinadas</span>
                    <div class="text-2xl font-bold text-teal-800 mt-1">{{ number_format($dadosProfissionais['total_evolucoes_assinadas'], 0, ',', '.') }}</div>
                    <span class="text-xs text-slate-500">Registros no prontuário eletrônico</span>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-indigo-700 uppercase">Produções BPA-i Geradas</span>
                    <div class="text-2xl font-bold text-indigo-800 mt-1">{{ number_format($dadosProfissionais['total_producoes_bpai'], 0, ',', '.') }}</div>
                    <span class="text-xs text-slate-500">Procedimentos ambulatoriais vinculados</span>
                </div>
            </div>

            <!-- TABELA DE ATENDIMENTOS E EVOLUÇÕES POR PROFISSIONAL -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Atividade Assistencial por Profissional
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Em atendimentos compartilhados, a participação de cada profissional é computada individualmente para sua especialidade, preservando a contagem única do agendamento para a clínica.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Profissional</th>
                                <th class="py-2.5 px-3">Especialidade</th>
                                <th class="py-2.5 px-3">CBO</th>
                                <th class="py-2.5 px-3 text-center">Agendados</th>
                                <th class="py-2.5 px-3 text-center">Concluídos</th>
                                <th class="py-2.5 px-3 text-center">Faltas</th>
                                <th class="py-2.5 px-3 text-center">Compartilhados</th>
                                <th class="py-2.5 px-3 text-center">Evoluções Assinadas</th>
                                <th class="py-2.5 px-3 text-center">Produção BPA-i</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($dadosProfissionais['linhas'] as $p)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $p['nome'] }}</td>
                                    <td class="py-2.5 px-3">{{ $p['especialidade'] }}</td>
                                    <td class="py-2.5 px-3 font-mono text-slate-500">{{ $p['cbo'] }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ $p['total_agendamentos'] }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-emerald-700">{{ $p['concluidos'] }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-rose-700">{{ $p['faltas'] }}</td>
                                    <td class="py-2.5 px-3 text-center text-indigo-700 font-semibold">{{ $p['compartilhados'] }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-teal-800">{{ $p['evolucoes_assinadas'] }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ $p['producoes_bpai'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- ABA 5: SALAS & OCUPAÇÃO                                                   -->
    <!-- ========================================================================= -->
    @if ($abaAtiva === 'salas')
        <div class="space-y-6">
            <!-- TABELA DE SALAS -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Utilização das Salas e Espaços Terapêuticos
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Volume de horários reservados e distribuição por turno dentro do período analisado.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Sala / Espaço</th>
                                <th class="py-2.5 px-3">Tipo</th>
                                <th class="py-2.5 px-3 text-center">Horários Reservados</th>
                                <th class="py-2.5 px-3 text-center">Turno Manhã</th>
                                <th class="py-2.5 px-3 text-center">Turno Tarde</th>
                                <th class="py-2.5 px-3 text-center">Compartilhados</th>
                                <th class="py-2.5 px-3 text-right">Uso Relativo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($dadosSalas['salas'] as $sala)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $sala['nome'] }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $sala['tipo'] }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-slate-900">{{ $sala['total_agendamentos'] }}</td>
                                    <td class="py-2.5 px-3 text-center text-slate-600">{{ $sala['turno_manha'] }}</td>
                                    <td class="py-2.5 px-3 text-center text-slate-600">{{ $sala['turno_tarde'] }}</td>
                                    <td class="py-2.5 px-3 text-center text-indigo-700 font-semibold">{{ $sala['compartilhados'] }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-0.5 text-xs font-bold text-teal-800">
                                            {{ $sala['ocupacao_relativa_pct'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAIXAS HORÁRIAS MAIS UTILIZADAS -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Concentração por Faixa Horária
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach ($dadosSalas['faixas_horarias'] as $fh)
                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/50 text-center">
                            <span class="text-xs font-mono font-bold text-slate-500">{{ $fh['faixa'] }}</span>
                            <div class="text-lg font-bold text-slate-900 mt-1">{{ $fh['total'] }}</div>
                            <span class="text-[10px] text-slate-400">agendamentos</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- ABA 6: PRODUÇÃO SUS / BPA-i                                               -->
    <!-- ========================================================================= -->
    @if ($abaAtiva === 'producao-bpai')
        <div class="space-y-6">
            <!-- CARDS DE CONFORMIDADE DA COMPETÊNCIA -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500 uppercase">Produções Totais</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                        {{ number_format($dadosProducaoBpai['total'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-slate-500">Competência {{ $dadosProducaoBpai['competencia_formatada'] }}</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-teal-700 uppercase">Aptas para Faturamento</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-teal-800 mt-1">
                        {{ number_format($dadosProducaoBpai['aptos'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-teal-600 font-semibold">{{ $dadosProducaoBpai['taxa_conformidade'] }}% de conformidade</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-700 uppercase">Inconsistências Pendentes</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-rose-800 mt-1">
                        {{ number_format($dadosProducaoBpai['inconsistentes'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-rose-600 font-semibold">Exigem regularização cadastral</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs font-semibold text-indigo-700 uppercase">Exportadas em Lote</span>
                    <div class="text-2xl sm:text-3xl font-extrabold text-indigo-800 mt-1">
                        {{ number_format($dadosProducaoBpai['exportados'], 0, ',', '.') }}
                    </div>
                    <span class="text-xs text-slate-500">Já transmitidas para o DATASUS</span>
                </div>
            </div>

            <!-- PRODUÇÃO POR PROCEDIMENTO SIGTAP E POR CBO -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Por Procedimento SIGTAP -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Top Procedimentos SIGTAP Faturados
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3">Código</th>
                                    <th class="py-2 px-3">Procedimento</th>
                                    <th class="py-2 px-3 text-right">Qtd</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($dadosProducaoBpai['por_procedimento'] as $proc)
                                    <tr>
                                        <td class="py-2 px-3 font-mono font-bold text-teal-800">{{ $proc->codigo }}</td>
                                        <td class="py-2 px-3 truncate max-w-xs">{{ $proc->descricao }}</td>
                                        <td class="py-2 px-3 text-right font-bold text-slate-900">{{ $proc->quantidade }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-4 text-center text-slate-400">Nenhuma produção registrada nesta competência.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Por Profissional -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Produção por Profissional Executante
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3">Profissional</th>
                                    <th class="py-2 px-3">Especialidade</th>
                                    <th class="py-2 px-3 text-center">Aptos</th>
                                    <th class="py-2 px-3 text-center">Inconsistentes</th>
                                    <th class="py-2 px-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($dadosProducaoBpai['por_profissional'] as $prof)
                                    <tr>
                                        <td class="py-2 px-3 font-semibold text-slate-900">{{ $prof->nome }}</td>
                                        <td class="py-2 px-3 text-slate-500">{{ $prof->especialidade }}</td>
                                        <td class="py-2 px-3 text-center font-bold text-teal-700">{{ $prof->aptos }}</td>
                                        <td class="py-2 px-3 text-center font-bold text-rose-700">{{ $prof->inconsistentes }}</td>
                                        <td class="py-2 px-3 text-right font-bold text-slate-900">{{ $prof->total }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-slate-400">Sem registros para a competência.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABELA DE PENDÊNCIAS / INCONSISTÊNCIAS (CONSULTIVA) -->
            @if(count($dadosProducaoBpai['inconsistencias']) > 0)
                <div class="bg-white rounded-2xl border border-rose-200 p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-rose-900 uppercase tracking-wider flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-rose-600"></span>
                                <span>Amostra de Pendências e Inconsistências de Produção</span>
                            </h3>
                            <p class="text-xs text-rose-700 mt-0.5">
                                Esta tela é exclusivamente consultiva. Para correções e revalidação de lote, utilize o módulo Faturamento BPA-i.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-rose-50 text-rose-900 uppercase font-bold border-b border-rose-200">
                                <tr>
                                    <th class="py-2.5 px-3">Data</th>
                                    <th class="py-2.5 px-3">Paciente</th>
                                    <th class="py-2.5 px-3">Profissional</th>
                                    <th class="py-2.5 px-3">Procedimento</th>
                                    <th class="py-2.5 px-3">Motivo da Inconsistência</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-100 text-slate-700">
                                @foreach ($dadosProducaoBpai['inconsistencias'] as $inc)
                                    <tr>
                                        <td class="py-2 px-3 font-mono">{{ $inc->data_atendimento?->format('d/m/Y') }}</td>
                                        <td class="py-2 px-3 font-semibold text-slate-900">{{ $inc->paciente->nome ?? '-' }}</td>
                                        <td class="py-2 px-3">{{ $inc->profissional->nome ?? '-' }}</td>
                                        <td class="py-2 px-3 font-mono text-[11px]">{{ $inc->procedimento->codigo ?? '-' }}</td>
                                        <td class="py-2 px-3 text-rose-700 font-medium">{{ $inc->mensagem_inconsistencia ?? 'Inconsistência cadastral' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
