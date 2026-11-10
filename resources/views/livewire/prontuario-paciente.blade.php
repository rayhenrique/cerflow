<div class="space-y-4 sm:space-y-6">
    @if($profissionalAutenticado)
        <!-- Fila de Atendimento do Profissional Autenticado -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-linear-to-r from-slate-900 via-slate-800 to-teal-950 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-500/20 text-teal-300 border border-teal-500/30">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-bold tracking-tight text-white">Meus Pacientes / Atendimentos de Hoje</h2>
                            <span class="rounded-full bg-teal-500/30 px-2 py-0.5 text-[11px] font-bold text-teal-200 border border-teal-400/30">
                                {{ $filaAtendimentosHoje->count() }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300">
                            Fila de espera de <strong class="text-white">{{ $profissionalAutenticado->nome }}</strong> • {{ $profissionalAutenticado->profissao_ou_especialidade }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-800/80 text-slate-300 border border-slate-700">
                        <span class="h-2 w-2 rounded-full bg-teal-400 animate-pulse"></span>
                        Presença confirmada na recepção
                    </span>
                </div>
            </div>

            @if($filaAtendimentosHoje->isEmpty())
                <div class="p-6 text-center text-xs text-slate-500 bg-slate-50/50">
                    <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-medium text-slate-700">Nenhum paciente aguardando ou atendido para você hoje até o momento.</p>
                    <p class="text-slate-400 mt-0.5">Assim que a recepção confirmar a chegada do paciente, ele aparecerá automaticamente aqui. Você também pode pesquisar qualquer prontuário no seletor abaixo.</p>
                </div>
            @else
                <!-- Visão Mobile: Fila em Cartões (< md) -->
                <div class="block md:hidden divide-y divide-slate-100">
                    @foreach($filaAtendimentosHoje as $item)
                        @php
                            $ag = $item['agendamento'];
                            $selecionado = $agendamento_id === $ag->id;
                        @endphp
                        <div class="p-4 space-y-3 {{ $selecionado ? 'bg-teal-50/70 border-l-4 border-l-teal-600' : 'bg-white' }}" wire:key="fila-pep-mob-{{ $ag->id }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">
                                    <span>{{ substr((string) $ag->hora_inicio, 0, 5) }} - {{ substr((string) $ag->hora_fim, 0, 5) }}</span>
                                    @if($ag->chegada_em)
                                        <span class="text-teal-700">({{ $ag->chegada_em->format('H:i') }})</span>
                                    @endif
                                </div>

                                <div>
                                    @if($item['estado_fila'] === 'EVOLUCAO_REGISTRADA')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            ✓ Evolução Feita
                                        </span>
                                    @elseif($item['estado_fila'] === 'CHAMADO')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800">
                                            📺 Chamado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Aguardando
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <span class="font-bold text-slate-900 text-sm block">{{ $ag->paciente->nome }}</span>
                                <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-2">
                                    <span>Nasc: {{ $ag->paciente->data_nascimento?->format('d/m/Y') ?? '—' }}</span>
                                    @if($ag->paciente->cid_principal)
                                        <span>• <strong class="font-mono text-slate-600">CID {{ $ag->paciente->cid_principal }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-600 pt-1 border-t border-slate-100">
                                <span class="font-medium text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                                    {{ $ag->sala->nome }}
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold {{ $ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $ag->tipo_atendimento }}
                                </span>
                            </div>

                            <!-- Botões de Ação com Alvos de Toque Confortáveis -->
                            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                                <button type="button"
                                        wire:click="chamarPaciente({{ $ag->id }})"
                                        class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-300 bg-white py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-teal-700 hover:border-teal-300 transition touch-target">
                                    <svg class="h-3.5 w-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    <span>Chamar TV</span>
                                </button>

                                <button type="button"
                                        wire:click="abrirProntuarioAtendimento({{ $ag->id }})"
                                        class="inline-flex items-center justify-center gap-1 rounded-xl py-2.5 text-xs font-bold shadow-xs transition touch-target {{ $selecionado ? 'bg-teal-700 text-white hover:bg-teal-600' : 'bg-slate-900 text-white hover:bg-teal-700' }}">
                                    <span>{{ $selecionado ? '✓ Prontuário Aberto' : 'Abrir Prontuário →' }}</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Visão Desktop: Tabela Completa (>= md) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Horário / Chegada</th>
                                <th class="px-4 py-3">Paciente</th>
                                <th class="px-4 py-3">Sala</th>
                                <th class="px-4 py-3">Atendimento & Equipe</th>
                                <th class="px-4 py-3">Estado Operacional</th>
                                <th class="px-4 py-3 text-right">Ações Rápidas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-sans">
                            @foreach($filaAtendimentosHoje as $item)
                                @php
                                    $ag = $item['agendamento'];
                                    $selecionado = $agendamento_id === $ag->id;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition-colors {{ $selecionado ? 'bg-teal-50/60 font-semibold text-slate-900' : '' }}" wire:key="fila-pep-{{ $ag->id }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-slate-800">
                                            {{ substr((string) $ag->hora_inicio, 0, 5) }} - {{ substr((string) $ag->hora_fim, 0, 5) }}
                                        </div>
                                        @if($ag->chegada_em)
                                            <div class="text-[11px] text-teal-600 font-medium flex items-center gap-1 mt-0.5">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
                                                Chegada: {{ $ag->chegada_em->format('H:i') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900 text-xs">{{ $ag->paciente->nome }}</div>
                                        <div class="text-[11px] text-slate-500">
                                            Nasc: {{ $ag->paciente->data_nascimento?->format('d/m/Y') ?? '—' }}
                                            @if($ag->paciente->cid_principal)
                                                • <span class="font-mono text-slate-600">CID {{ $ag->paciente->cid_principal }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                            {{ $ag->sala->nome }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $ag->tipo_atendimento === 'COMPARTILHADO' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $ag->tipo_atendimento }}
                                        </span>
                                        <div class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5" title="{{ $ag->profissionais->pluck('nome')->implode(', ') }}">
                                            {{ $ag->profissionais->pluck('nome')->implode(', ') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($item['estado_fila'] === 'EVOLUCAO_REGISTRADA')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                                Evolução registrada
                                            </span>
                                        @elseif($item['estado_fila'] === 'CHAMADO')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-sky-600 animate-pulse"></span>
                                                Chamado na TV
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                                Aguardando
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                wire:click="chamarPaciente({{ $ag->id }})"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-teal-700 hover:border-teal-300 transition-colors cursor-pointer"
                                                title="Chamar paciente na TV da recepção"
                                            >
                                                <svg class="h-3.5 w-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                <span>Chamar</span>
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="abrirProntuarioAtendimento({{ $ag->id }})"
                                                class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors cursor-pointer {{ $selecionado ? 'bg-teal-600 text-white hover:bg-teal-700' : 'bg-slate-900 text-white hover:bg-teal-600' }}"
                                            >
                                                <span>Abrir Prontuário →</span>
                                            </button>
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

    <!-- Cabeçalho do PEP e Seletor de Paciente -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900">Prontuário Eletrônico Multiprofissional (PEP)</h2>
            <p class="text-xs text-slate-500">Linha do tempo integrada, assinatura eletrônica SHA-256 e trava legal de 24h</p>
        </div>

        @if($pacienteSelecionado)
            <!-- Seletor de Paciente Sob Demanda (Estado Selecionado) -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50/70 px-3 py-2 text-xs max-w-full sm:max-w-md">
                    <span class="inline-flex items-center justify-center rounded-full bg-teal-600 text-white w-4 h-4 text-[10px] font-bold shrink-0">✓</span>
                    <span class="font-bold text-slate-900 truncate">{{ $pacienteSelecionado->nome }}</span>
                    @if($pacienteSelecionado->cns)
                        <span class="font-mono text-slate-500 hidden md:inline shrink-0">CNS: {{ $pacienteSelecionado->cns }}</span>
                    @elseif($pacienteSelecionado->cpf)
                        <span class="font-mono text-slate-500 hidden md:inline shrink-0">CPF: {{ $pacienteSelecionado->cpf }}</span>
                    @endif
                </div>

                <button
                    type="button"
                    wire:click="limparPacienteSelecionado"
                    class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-rose-600 hover:border-rose-300 transition-colors cursor-pointer"
                    title="Pesquisar e selecionar outro paciente"
                >
                    <span>✕</span>
                    <span>Trocar</span>
                </button>
            </div>
        @else
            <!-- Busca Dinâmica Sob Demanda com Auto-complete Dropdown -->
            <div class="relative w-full sm:w-96">
                <div class="relative flex items-center">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="pesquisaPaciente"
                        placeholder="Digite o nome, CPF ou Cartão SUS do paciente..."
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

                <!-- Feedback ou Resultados em Dropdown Suspenso -->
                @if(mb_strlen(trim($pesquisaPaciente)) >= 3)
                    <div class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl divide-y divide-slate-100">
                        @forelse($pacientesResultados as $pac)
                            <button
                                type="button"
                                wire:click="selecionarPaciente({{ $pac['id'] }})"
                                wire:key="pep-paciente-{{ $pac['id'] }}"
                                class="w-full text-left p-2.5 hover:bg-teal-50/70 focus:bg-teal-50 transition-colors flex items-center justify-between gap-3 group cursor-pointer"
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
                                    Abrir PEP →
                                </span>
                            </button>
                        @empty
                            <div class="p-3 text-center text-xs text-slate-500" wire:loading.remove wire:target="pesquisaPaciente">
                                Nenhum paciente encontrado para <strong class="text-slate-700">"{{ $pesquisaPaciente }}"</strong>.
                            </div>
                        @endforelse
                    </div>
                @elseif(mb_strlen(trim($pesquisaPaciente)) > 0)
                    <div class="absolute z-50 left-0 right-0 mt-1 p-2.5 rounded-lg border border-slate-200 bg-white shadow-md text-xs text-slate-500">
                        Digite pelo menos 3 caracteres para iniciar a pesquisa.
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($pacienteSelecionado)
        <!-- Ficha Resumo do Usuário -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 bg-slate-900 text-white p-4 rounded-xl text-xs">
            <div>
                <span class="text-slate-400 block">Usuário do CER</span>
                <span class="font-bold text-sm">{{ $pacienteSelecionado->nome }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">CNS (15 dígitos)</span>
                @if ($pacienteSelecionado->cns)
                    <span class="font-mono font-semibold text-teal-400">{{ $pacienteSelecionado->cns }}</span>
                @else
                    <span class="text-slate-400 italic">Não informado</span>
                @endif
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 block">Nascimento / CID-10</span>
                </div>
                <span class="font-semibold">
                    {{ $pacienteSelecionado->data_nascimento?->format('d/m/Y') }} • 
                    @if ($pacienteSelecionado->cid_principal)
                        CID {{ $pacienteSelecionado->cid_principal }}
                    @else
                        <span class="text-amber-400 font-normal italic">CID Pendente</span>
                    @endif
                </span>
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 block">Modalidades</span>
                    <button type="button" wire:click="abrirModalDiagnostico" class="text-[11px] text-teal-400 hover:text-teal-300 font-semibold underline cursor-pointer">
                        Editar Diagnóstico
                    </button>
                </div>
                <div class="mt-1 flex flex-wrap gap-1">
                    @forelse($pacienteSelecionado->modalidades_reabilitacao ?? [] as $mod)
                        <span class="rounded bg-slate-800 px-2 py-0.5 text-[10px] font-mono text-teal-300 border border-slate-700">{{ $mod }}</span>
                    @empty
                        <span class="text-slate-400 italic">Nenhuma atribuída</span>
                    @endforelse
                </div>
            </div>
        </div>

        @php
            $agHojePresente = $agendamentosPaciente->first(fn ($ag) => $ag->data_atendimento->isToday() && $ag->status === 'PRESENTE');
        @endphp
        @if($agHojePresente)
            @php
                $qtdChamadas = $agHojePresente->chamadas->count();
                $ultimaChamada = $agHojePresente->chamadas->sortByDesc('chamado_em')->first();
            @endphp
            <div class="rounded-xl border border-teal-200 bg-teal-50/80 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <span class="font-bold text-teal-900 block">Presença confirmada na recepção hoje ({{ substr((string)$agHojePresente->hora_inicio, 0, 5) }} — {{ $agHojePresente->sala?->nome }})</span>
                        <span class="text-teal-700 text-[11px]">
                            @if($qtdChamadas === 0)
                                Paciente aguardando chamada para o consultório.
                            @else
                                Chamado {{ $qtdChamadas }} vez(es). Última chamada às {{ $ultimaChamada?->chamado_em?->format('H:i') }}.
                            @endif
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if($qtdChamadas === 0)
                        <button type="button" wire:click="chamarPaciente({{ $agHojePresente->id }})"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            Chamar Paciente na TV
                        </button>
                    @else
                        <button type="button" wire:click="repetirChamada({{ $agHojePresente->id }})"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-600 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Repetir Chamada ({{ $qtdChamadas }}x)
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Coluna Esquerda: Formulário de Evolução Rápida -->
            <div class="lg:col-span-5 bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-4 text-xs h-fit">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">
                    Nova Evolução Clínica + Produção BPA-i
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="block font-semibold text-slate-700">Agendamento Vinculado</label>
                        <select wire:model="agendamento_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Selecione o atendimento...</option>
                            @foreach($agendamentosPaciente as $ag)
                                <option value="{{ $ag->id }}">
                                    {{ $ag->data_atendimento->format('d/m/Y') }} ({{ substr((string) $ag->hora_inicio, 0, 5) }}) — {{ $ag->sala?->nome }} [{{ $ag->tipo_atendimento }}]
                                </option>
                            @endforeach
                        </select>
                        @error('agendamento_id') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">Procedimento SIGTAP Executado (10 dígitos)</label>
                        <select wire:model="procedimento_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Selecione o procedimento SIGTAP...</option>
                            @foreach($procedimentos as $proc)
                                <option value="{{ $proc->id }}">{{ $proc->codigo }} — {{ $proc->descricao }}</option>
                            @endforeach
                        </select>
                        @error('procedimento_id') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">Queixa / Estado Funcional Atual</label>
                        <textarea wire:model="queixa_estado_atual" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></textarea>
                        @error('queixa_estado_atual') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">Conduta Terapêutica Realizada</label>
                        <textarea wire:model="conduta_terapeutica" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></textarea>
                        @error('conduta_terapeutica') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">Intercorrências (Opcional)</label>
                        <textarea wire:model="intercorrencias" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></textarea>
                    </div>

                    <button type="button"
                            wire:click="solicitarPinAssinatura"
                            class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white shadow-xs hover:bg-teal-500 cursor-pointer">
                        Assinar Eletronicamente com PIN
                    </button>
                </div>
            </div>

            <!-- Coluna Direita: Linha do Tempo Multiprofissional -->
            <div class="lg:col-span-7 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">Linha do Tempo Clínica Multiprofissional</h3>

                @forelse($evolucoes as $ev)
                    @php
                        $bloqueada = $ev->estaBloqueada();
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs space-y-3 text-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                            <div>
                                <span class="font-bold text-slate-900">{{ $ev->profissional?->nome }}</span>
                                <span class="text-slate-500">• {{ $ev->profissional?->profissao_ou_especialidade }} (CBO {{ $ev->profissional?->cbo_padrao }})</span>
                                <span class="block text-[11px] text-slate-400">
                                    Assinado em {{ $ev->assinado_em?->format('d/m/Y H:i') }} • Hash SHA-256: <code class="font-mono">{{ substr($ev->assinatura_hash, 0, 16) }}...</code>
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if($bloqueada)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-700 border border-slate-300">
                                        🔒 Imutável (+24h)
                                    </span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                                        Editável (&lt;24h)
                                    </span>
                                @endif

                                @can('createAdendo', $ev)
                                    <button type="button"
                                            wire:click="abrirModalAdendo({{ $ev->id }})"
                                            class="rounded-md bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-slate-700 cursor-pointer">
                                        + Adendo Retificador
                                    </button>
                                @endcan
                            </div>
                        </div>

                        <div class="space-y-2 text-slate-700">
                            <p><strong class="text-slate-900">Queixa / Estado Atual:</strong> {{ $ev->queixa_estado_atual }}</p>
                            <p><strong class="text-slate-900">Conduta Terapêutica:</strong> {{ $ev->conduta_terapeutica }}</p>
                            @if($ev->intercorrencias)
                                <p><strong class="text-slate-900">Intercorrências:</strong> {{ $ev->intercorrencias }}</p>
                            @endif
                        </div>

                        @if($ev->producaoBpai)
                            <div class="rounded-lg bg-slate-50 px-3 py-2 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-[11px]">
                                <span>
                                    <strong>SIGTAP:</strong> {{ $ev->producaoBpai->procedimento?->codigo }} — {{ $ev->producaoBpai->procedimento?->descricao }}
                                </span>
                                <span class="font-mono font-semibold text-teal-700 shrink-0">
                                    Comp. {{ $ev->producaoBpai->competencia }} ({{ $ev->producaoBpai->status_exportacao }})
                                </span>
                            </div>
                        @endif

                        <!-- Adendos Retificadores Vinculados -->
                        @if($ev->adendos->isNotEmpty())
                            <div class="mt-3 space-y-2 border-l-2 border-amber-400 pl-3">
                                @foreach($ev->adendos as $adendo)
                                    <div class="rounded-lg bg-amber-50/70 p-2.5 border border-amber-200 text-[11px]">
                                        <span class="font-bold text-amber-900 block">
                                            Termo de Adendo — {{ $adendo->assinado_em?->format('d/m/Y H:i') }} ({{ $adendo->profissional?->nome }})
                                        </span>
                                        <p class="mt-1"><strong>Justificativa:</strong> {{ $adendo->justificativa }}</p>
                                        <p class="mt-0.5"><strong>Nota Retificadora:</strong> {{ $adendo->texto_adendo }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-xs text-slate-400">
                        Nenhuma evolução clínica registrada para este paciente até o momento.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Estado Inicial: Nenhum Paciente Selecionado -->
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-8 sm:p-14 text-center shadow-xs">
            <div class="mx-auto w-14 h-14 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Nenhum Paciente Selecionado</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-md mx-auto leading-relaxed">
                Digite o nome, CPF ou Cartão SUS do paciente no campo de busca acima para visualizar a linha do tempo clínica, registrar novas evoluções e gerar produção BPA-i.
            </p>
        </div>
    @endif

    <!-- Modal de Confirmação de PIN para Assinatura Eletrônica -->
    @if($modalPinAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-3 sm:p-4 overflow-y-auto">
            <div class="w-full max-w-sm rounded-xl bg-white p-5 sm:p-6 shadow-xl border border-slate-200 text-xs space-y-4 my-auto">
                <h3 class="text-sm font-bold text-slate-900">Assinatura Eletrônica do Prontuário</h3>
                <p class="text-slate-500">Confirme seu PIN individual para gerar o hash SHA-256 e lançar a produção no BPA-i.</p>

                <div>
                    <label class="block font-semibold text-slate-700">PIN de Assinatura</label>
                    <input type="password" wire:model="pin_assinatura" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-center font-mono text-base tracking-widest">
                    @error('pin_assinatura') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('modalPinAberto', false)" class="w-full sm:w-auto rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarAssinaturaEvolucao" class="w-full sm:w-auto rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white hover:bg-teal-500 cursor-pointer">
                        Confirmar e Assinar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Inserção de Termo de Adendo (Evoluções Bloqueadas >24h) -->
    @if($modalAdendoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-3 sm:p-4 overflow-y-auto">
            <div class="w-full max-w-md rounded-xl bg-white p-5 sm:p-6 shadow-xl border border-slate-200 text-xs space-y-4 my-auto max-h-[92vh] overflow-y-auto">
                <h3 class="text-sm font-bold text-slate-900">Inserir Termo de Adendo Retificador</h3>
                <p class="text-slate-500">A evolução original permanece imutável. O adendo será anexado com carimbo de tempo e assinatura.</p>

                <div>
                    <label class="block font-semibold text-slate-700">Justificativa da Retificação</label>
                    <input type="text" wire:model="justificativa_adendo" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('justificativa_adendo') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">Texto do Adendo</label>
                    <textarea wire:model="texto_adendo" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></textarea>
                    @error('texto_adendo') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">PIN do Profissional</label>
                    <input type="password" wire:model="pin_adendo" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
                    @error('pin_adendo') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('modalAdendoAberto', false)" class="w-full sm:w-auto rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" wire:click="salvarAdendo" class="w-full sm:w-auto rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-800 cursor-pointer">
                        Assinar Adendo
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE DIAGNÓSTICO CLÍNICO E MODALIDADES DE REABILITAÇÃO --}}
    @if ($modalDiagnosticoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-8">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Diagnóstico Clínico & Reabilitação</h3>
                        <p class="text-xs text-slate-400">Atribuição clínica pelo profissional de saúde autorizado</p>
                    </div>
                    <button type="button" wire:click="fecharModalDiagnostico" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarDiagnostico" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CID-10 Principal *</label>
                            <input type="text" wire:model="diag_cid_principal" maxlength="10" placeholder="Ex.: F840, G800"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase font-mono focus:border-teal-600 focus:outline-none">
                            @error('diag_cid_principal') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CID-10 Secundário</label>
                            <input type="text" wire:model="diag_cid_secundario" maxlength="10" placeholder="Opcional"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase font-mono focus:border-teal-600 focus:outline-none">
                            @error('diag_cid_secundario') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Modalidades de Reabilitação do CER</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            @foreach (['FISICA' => 'Física', 'INTELECTUAL' => 'Intelectual', 'AUDITIVA' => 'Auditiva', 'VISUAL' => 'Visual'] as $valor => $rotulo)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-2.5 cursor-pointer hover:bg-slate-50">
                                    <input type="checkbox" wire:model="diag_modalidades" value="{{ $valor }}" class="rounded text-teal-600">
                                    <span class="font-medium text-slate-800">{{ $rotulo }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('diag_modalidades') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="fecharModalDiagnostico"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-teal-600 px-5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer">
                            Salvar Avaliação Clínica
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
