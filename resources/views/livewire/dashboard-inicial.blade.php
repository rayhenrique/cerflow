<div class="space-y-4 sm:space-y-6">
    <!-- Cabeçalho do Dashboard e Filtro de Competência -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-semibold mb-1.5">
                <span>Visão Geral da Unidade • Competência {{ $competenciaFormatada }}</span>
            </div>
            <h2 class="text-base sm:text-xl font-bold text-slate-900">Dashboard de Indicadores do CER</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Acompanhamento em tempo real de pacientes ativos, assiduidade, atendimentos multiprofissionais e produção SUS (BPA-i)
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 text-xs">
            <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 w-full sm:w-auto justify-between sm:justify-start">
                <label class="font-semibold text-slate-600">Mês / Competência (AAAAMM):</label>
                <input type="text" maxlength="6" wire:model.live.debounce.300ms="competencia"
                       class="w-24 rounded-md border border-slate-300 bg-white px-2.5 py-1 font-mono font-bold text-center text-slate-800 focus:border-teal-600 focus:outline-none">
            </div>
        </div>
    </div>

    <!-- 4 Cards Principais (KPIs) -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Pacientes Ativos -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Pacientes Ativos (CER)</span>
                <span class="rounded-lg bg-teal-50 px-2 py-1 text-[11px] font-bold text-teal-700 border border-teal-200">
                    +{{ $novosPacientesMes }} no mês
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $totalPacientesAtivos }}</span>
                <span class="text-[11px] text-slate-400">{{ $totalProfissionaisAtivos }} profissionais • {{ $totalSalasAtivas }} salas</span>
            </div>
        </div>

        <!-- Card 2: Atendimentos de Hoje -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Agenda de Hoje ({{ now()->format('d/m') }})</span>
                <span class="rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-700 border border-emerald-200">
                    {{ $hojePresentesAtendidos }} presentes
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $hojeTotal }}</span>
                <span class="text-[11px] text-slate-500">
                    {{ $hojeAguardando }} aguardando • <span class="{{ $hojeFaltas > 0 ? 'text-rose-600 font-semibold' : '' }}">{{ $hojeFaltas }} faltas</span>
                </span>
            </div>
        </div>

        <!-- Card 3: Evoluções e Assiduidade no Mês -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Evoluções Assinadas no Mês</span>
                <span class="rounded-lg bg-indigo-50 px-2 py-1 text-[11px] font-bold text-indigo-700 border border-indigo-200">
                    {{ $taxaComparecimento }}% presença
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $evolucoesMes }}</span>
                <span class="text-[11px] text-slate-500">
                    {{ $mesCompartilhados }} atendimentos em conjunto
                </span>
            </div>
        </div>

        <!-- Card 4: Produção SUS (BPA-i) -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Produção BPA-i ({{ $competenciaFormatada }})</span>
                @if($bpaiInconsistentes > 0)
                    <span class="rounded-lg bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-700 border border-rose-200">
                        {{ $bpaiInconsistentes }} c/ pendência
                    </span>
                @else
                    <span class="rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-700 border border-emerald-200">
                        100% íntegro
                    </span>
                @endif
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-bold text-teal-700">{{ $bpaiTotal }}</span>
                <span class="text-[11px] text-slate-500">
                    {{ $bpaiAptos }} aptos • {{ $bpaiExportados }} exportados
                </span>
            </div>
        </div>
    </div>

    <!-- Atalhos Rápidos Operacionais -->
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
        <a href="{{ route('grade.semanal') }}"
           class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-800 hover:border-teal-500 hover:bg-teal-50/40 transition shadow-xs">
            <span>📅 Abrir Grade Semanal</span>
            <span class="text-teal-600">&rarr;</span>
        </a>
        <a href="{{ route('recepcao.checkin') }}"
           class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-800 hover:border-teal-500 hover:bg-teal-50/40 transition shadow-xs">
            <span>✅ Recepção & Check-in</span>
            <span class="text-teal-600">&rarr;</span>
        </a>
        @if(auth()->user()?->hasAnyRole(['terapeuta', 'coordenacao']))
            <a href="{{ route('prontuario.index') }}"
               class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-800 hover:border-teal-500 hover:bg-teal-50/40 transition shadow-xs">
                <span>🩺 Prontuário (PEP)</span>
                <span class="text-teal-600">&rarr;</span>
            </a>
        @endif
        @if(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']))
            <a href="{{ route('faturamento.bpai') }}"
               class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-800 hover:border-teal-500 hover:bg-teal-50/40 transition shadow-xs">
                <span>📄 Faturamento BPA-i</span>
                <span class="text-teal-600">&rarr;</span>
            </a>
        @elseif(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']))
            <a href="{{ route('configuracoes.secao', ['secao' => 'pacientes']) }}"
               class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-800 hover:border-teal-500 hover:bg-teal-50/40 transition shadow-xs">
                <span>👤 Cadastrar Paciente</span>
                <span class="text-teal-600">&rarr;</span>
            </a>
        @endif
    </div>

    <!-- Linha Central de Gráficos / Estatísticas -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Painel 1: Pacientes por Modalidade de Reabilitação -->
        <div class="lg:col-span-4 rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Pacientes por Modalidade de Reabilitação</h3>
                <p class="text-xs text-slate-500">Distribuição entre as 4 linhas de cuidado do CER</p>
            </div>

            @php
                $rotulosModalidades = [
                    'FISICA' => ['nome' => 'Reabilitação Física', 'cor' => 'bg-teal-600'],
                    'INTELECTUAL' => ['nome' => 'Reabilitação Intelectual / TEA', 'cor' => 'bg-indigo-600'],
                    'AUDITIVA' => ['nome' => 'Reabilitação Auditiva', 'cor' => 'bg-amber-500'],
                    'VISUAL' => ['nome' => 'Reabilitação Visual', 'cor' => 'bg-sky-600'],
                ];
                $baseModalidades = max($totalPacientesAtivos, 1);
            @endphp

            <div class="space-y-3.5 text-xs">
                @foreach($rotulosModalidades as $chave => $meta)
                    @php
                        $qtd = $contagemModalidades[$chave] ?? 0;
                        $perc = min(100, (int) round(($qtd / $baseModalidades) * 100));
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-slate-700">{{ $meta['nome'] }}</span>
                            <span class="font-mono font-bold text-slate-900">{{ $qtd }} ({{ $perc }}%)</span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $meta['cor'] }}" style="width: {{ $perc }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Procedimentos SIGTAP ativos:</span>
                <span class="font-mono font-bold text-slate-800">{{ $totalProcedimentosAtivos }}</span>
            </div>
        </div>

        <!-- Painel 2: Balanço de Assiduidade e Faltas no Mês -->
        <div class="lg:col-span-4 rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Assiduidade & Absenteísmo no Mês</h3>
                <p class="text-xs text-slate-500">Total de {{ $mesTotalAgendamentos }} agendamentos na competência {{ $competenciaFormatada }}</p>
            </div>

            <div class="grid grid-cols-3 gap-2.5 text-center">
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                    <span class="text-[11px] font-semibold text-emerald-800 block">Realizados</span>
                    <span class="text-xl font-bold text-emerald-700 mt-0.5 block">{{ $mesRealizados }}</span>
                </div>
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                    <span class="text-[11px] font-semibold text-amber-800 block">Faltas Just.</span>
                    <span class="text-xl font-bold text-amber-700 mt-0.5 block">{{ $mesFaltasJustificadas }}</span>
                </div>
                <div class="rounded-lg bg-rose-50 border border-rose-200 p-3">
                    <span class="text-[11px] font-semibold text-rose-800 block">Faltas Injust.</span>
                    <span class="text-xl font-bold text-rose-700 mt-0.5 block">{{ $mesFaltasInjustificadas }}</span>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-slate-700">Taxa de Comparecimento Efetivo</span>
                    <span class="font-mono font-bold text-teal-700">{{ $taxaComparecimento }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                    <div class="h-full rounded-full bg-teal-600" style="width: {{ min(100, $taxaComparecimento) }}%"></div>
                </div>
                <p class="text-[11px] text-slate-500">
                    Pacientes com 3 faltas seguidas ou 5 no mês recebem alerta automático na tela de Recepção.
                </p>
            </div>
        </div>

        <!-- Painel 3: Ocupação por Sala Terapêutica -->
        <div class="lg:col-span-4 rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Atendimentos por Sala / Espaço</h3>
                <p class="text-xs text-slate-500">Espaços mais utilizados na competência {{ $competenciaFormatada }}</p>
            </div>

            <div class="space-y-2.5 text-xs">
                @forelse($salasOcupacao as $sala)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                        <div class="min-w-0 pr-2">
                            <span class="font-semibold text-slate-800 block truncate">{{ $sala->nome }}</span>
                            <span class="text-[10px] font-mono text-slate-400">{{ $sala->tipo }}</span>
                        </div>
                        <span class="rounded-md bg-white px-2.5 py-1 font-mono text-xs font-bold text-teal-700 border border-slate-200 shrink-0">
                            {{ $sala->atendimentos_mes_count }} atend.
                        </span>
                    </div>
                @empty
                    <p class="text-slate-400 text-center py-6">Nenhuma sala ativa cadastrada.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Agenda do Dia (Próximos Atendimentos de Hoje) -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Atendimentos Programados para Hoje ({{ now()->format('d/m/Y') }})</h3>
                <p class="text-xs text-slate-500">Resumo rápido dos horários do dia</p>
            </div>
            <a href="{{ route('recepcao.checkin') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-600 underline underline-offset-4">
                Ir para Check-in Completo &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold text-slate-600">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Horário</th>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3">Sala / Espaço</th>
                        <th class="px-4 py-3">Profissionais</th>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agendamentosHoje as $ag)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-mono font-semibold text-slate-800 whitespace-nowrap">
                                {{ substr((string) $ag->hora_inicio, 0, 5) }} - {{ substr((string) $ag->hora_fim, 0, 5) }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 min-w-[160px]">
                                {{ $ag->tipo_atendimento === 'TRABALHO_INTERNO' ? 'TRABALHO INTERNO / EVOLUÇÃO' : $ag->paciente?->nome }}
                            </td>
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                {{ $ag->sala?->nome }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 min-w-[160px]">
                                {{ $ag->profissionais->pluck('nome')->join(' + ') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                                    {{ $ag->status === 'PRESENTE' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $ag->status === 'ATENDIDO' ? 'bg-teal-100 text-teal-800' : '' }}
                                    {{ $ag->status === 'AGENDADO' ? 'bg-slate-100 text-slate-700' : '' }}
                                    {{ str_starts_with((string) $ag->status, 'FALTA') ? 'bg-rose-100 text-rose-800' : '' }}">
                                    {{ $ag->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                Nenhum atendimento agendado para a data de hoje.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
