<div class="space-y-4 sm:space-y-6">
    <!-- Cabeçalho do PEP e Seletor de Paciente -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900">Prontuário Eletrônico Multiprofissional (PEP)</h2>
            <p class="text-xs text-slate-500">Linha do tempo integrada, assinatura eletrônica SHA-256 e trava legal de 24h</p>
        </div>

        <div class="w-full sm:w-80">
            <select wire:model.live="pacienteId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">
                <option value="">Selecione um paciente...</option>
                @foreach($pacientes as $pac)
                    <option value="{{ $pac->id }}">{{ $pac->nome }} (CNS: {{ $pac->cns }})</option>
                @endforeach
            </select>
        </div>
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
                                <span class="text-slate-500">• {{ $ev->profissional?->especialidade }} (CBO {{ $ev->profissional?->cbo_padrao }})</span>
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
