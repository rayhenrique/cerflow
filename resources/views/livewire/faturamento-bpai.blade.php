<div class="space-y-4 sm:space-y-6">
    <!-- Barra de Competência e Exportação Magnética -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900">Faturamento & Pré-Auditoria BPA-i (SIA/SUS)</h2>
            <p class="text-xs text-slate-500">Validação preventiva de CNS, CBO x SIGTAP e exportação magnética posicional (.txt)</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 text-xs">
            <div class="flex items-center justify-between sm:justify-start gap-2">
                <label class="font-semibold text-slate-700">Competência (AAAAMM):</label>
                <input type="text" maxlength="6" wire:model.live="competencia"
                       class="w-28 rounded-lg border border-slate-300 px-3 py-2 font-mono font-bold text-center">
            </div>

            <button type="button"
                    wire:click="exportarArquivoTxt"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white shadow-xs hover:bg-teal-500 cursor-pointer">
                ⬇ Exportar Remessa .TXT (#BPA#)
            </button>
        </div>
    </div>

    <!-- Cards de Resumo da Pré-Auditoria -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-xs text-slate-500">Total de Produções na Competência</span>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $auditoria['total'] }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-xs">
            <span class="text-xs font-medium text-emerald-800">Registros Válidos (Prontos para BPA-i)</span>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $auditoria['validados']->count() }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50/60 p-4 shadow-xs">
            <span class="text-xs font-medium text-red-800">Registros Inconsistentes (Retidos na Pré-Auditoria)</span>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ $auditoria['inconsistentes']->count() }}</p>
        </div>
    </div>

    <!-- Painel de Inconsistências (se houver) -->
    @if($auditoria['inconsistentes']->isNotEmpty())
        <div class="rounded-xl border border-red-200 bg-white overflow-hidden shadow-xs">
            <div class="bg-red-50 px-4 py-3 border-b border-red-200">
                <h3 class="text-xs font-bold text-red-900">Inconsistências Detectadas pela Pré-Auditoria (Bloqueados da Remessa)</h3>
            </div>
            
            <!-- Mobile: Cartões de Inconsistência (< md) -->
            <div class="block md:hidden divide-y divide-red-100">
                @foreach($auditoria['inconsistentes'] as $inc)
                    <div class="p-3.5 space-y-2 bg-red-50/20">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-slate-800">{{ $inc->data_atendimento?->format('d/m/Y') }}</span>
                            <span class="font-mono text-[11px] bg-red-100 text-red-800 px-2 py-0.5 rounded font-bold">
                                {{ $inc->procedimento?->codigo }}
                            </span>
                        </div>
                        <div class="text-xs">
                            <span class="font-bold text-slate-900 block">{{ $inc->paciente?->nome }}</span>
                            <span class="font-mono text-[11px] text-slate-500">CNS: {{ $inc->paciente?->cns ?? 'Ausente' }}</span>
                        </div>
                        <div class="text-xs text-slate-600">
                            <span>{{ $inc->profissional?->nome }} (CBO {{ $inc->cbo_executante }})</span>
                        </div>
                        <div class="text-xs font-medium text-red-700 bg-red-50 p-2 rounded-lg border border-red-200">
                            ⚠️ {{ $inc->mensagem_inconsistencia }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop: Tabela de Inconsistências (>= md) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-2.5 whitespace-nowrap">Data</th>
                            <th class="px-4 py-2.5">Paciente (CNS)</th>
                            <th class="px-4 py-2.5">Profissional / CBO</th>
                            <th class="px-4 py-2.5 whitespace-nowrap">Procedimento SIGTAP</th>
                            <th class="px-4 py-2.5">Motivo da Rejeição Preventiva</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($auditoria['inconsistentes'] as $inc)
                            <tr>
                                <td class="px-4 py-2.5 font-mono whitespace-nowrap">{{ $inc->data_atendimento?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 min-w-[180px]">{{ $inc->paciente?->nome }} ({{ $inc->paciente?->cns }})</td>
                                <td class="px-4 py-2.5 min-w-[160px]">{{ $inc->profissional?->nome }} (CBO {{ $inc->cbo_executante }})</td>
                                <td class="px-4 py-2.5 font-mono whitespace-nowrap">{{ $inc->procedimento?->codigo }}</td>
                                <td class="px-4 py-2.5 text-red-700 font-medium min-w-[200px]">{{ $inc->mensagem_inconsistencia }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Tabela de Produções Validadas -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-xs">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200">
            <h3 class="text-xs font-bold text-slate-900">Produções Validadas para Exportação Tipo 03 (BPA-i)</h3>
        </div>

        <!-- Mobile: Cartões de Produção Validada (< md) -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($auditoria['validados'] as $val)
                <div class="p-3.5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-slate-800">{{ $val->data_atendimento?->format('d/m/Y') }}</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                            {{ $val->status_exportacao }}
                        </span>
                    </div>
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 block">{{ $val->paciente?->nome }}</span>
                        <span class="font-mono text-[11px] text-slate-500">CNS: {{ $val->paciente?->cns }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-600 pt-1 border-t border-slate-100">
                        <span>{{ $val->profissional?->nome }} (CBO {{ $val->cbo_executante }})</span>
                        <span class="font-mono text-teal-700 font-bold">{{ $val->procedimento?->codigo }}</span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-xs text-slate-400">
                    Nenhuma produção validada na competência {{ $competencia }}.
                </div>
            @endforelse
        </div>

        <!-- Desktop: Tabela de Produções Validadas (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold text-slate-700">
                    <tr>
                        <th class="px-4 py-2.5 whitespace-nowrap">Data</th>
                        <th class="px-4 py-2.5">Paciente / CNS</th>
                        <th class="px-4 py-2.5">Profissional / CBO</th>
                        <th class="px-4 py-2.5 whitespace-nowrap">SIGTAP / CID-10</th>
                        <th class="px-4 py-2.5 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($auditoria['validados'] as $val)
                        <tr>
                            <td class="px-4 py-2.5 font-mono whitespace-nowrap">{{ $val->data_atendimento?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 min-w-[180px]">
                                <span class="font-semibold text-slate-900 block">{{ $val->paciente?->nome }}</span>
                                <span class="font-mono text-[11px] text-slate-500">{{ $val->paciente?->cns }}</span>
                            </td>
                            <td class="px-4 py-2.5 min-w-[160px]">
                                <span class="font-medium text-slate-800 block">{{ $val->profissional?->nome }}</span>
                                <span class="font-mono text-[11px] text-slate-500">CBO {{ $val->cbo_executante }}</span>
                            </td>
                            <td class="px-4 py-2.5 font-mono whitespace-nowrap">
                                {{ $val->procedimento?->codigo }} • CID {{ $val->cid_atendimento }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">
                                    {{ $val->status_exportacao }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                Nenhuma produção validada na competência {{ $competencia }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
