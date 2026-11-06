<div class="space-y-6">
    <!-- Barra de Competência e Exportação Magnética -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Faturamento & Pré-Auditoria BPA-i (SIA/SUS)</h2>
            <p class="text-xs text-slate-500">Validação preventiva de CNS, CBO x SIGTAP e exportação magnética posicional (.txt)</p>
        </div>

        <div class="flex items-center gap-3 text-xs">
            <div class="flex items-center gap-2">
                <label class="font-semibold text-slate-700">Competência (AAAAMM):</label>
                <input type="text" maxlength="6" wire:model.live="competencia"
                       class="w-28 rounded-lg border border-slate-300 px-3 py-2 font-mono font-bold text-center">
            </div>

            <button type="button"
                    wire:click="exportarArquivoTxt"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white shadow-xs hover:bg-teal-500">
                ⬇ Exportar Remessa .TXT (#BPA#)
            </button>
        </div>
    </div>

    <!-- Cards de Resumo da Pré-Auditoria -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold text-slate-700">
                    <tr>
                        <th class="px-4 py-2.5">Data</th>
                        <th class="px-4 py-2.5">Paciente (CNS)</th>
                        <th class="px-4 py-2.5">Profissional / CBO</th>
                        <th class="px-4 py-2.5">Procedimento SIGTAP</th>
                        <th class="px-4 py-2.5">Motivo da Rejeição Preventiva</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($auditoria['inconsistentes'] as $inc)
                        <tr>
                            <td class="px-4 py-2.5 font-mono">{{ $inc->data_atendimento?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5">{{ $inc->paciente?->nome }} ({{ $inc->paciente?->cns }})</td>
                            <td class="px-4 py-2.5">{{ $inc->profissional?->nome }} (CBO {{ $inc->cbo_executante }})</td>
                            <td class="px-4 py-2.5 font-mono">{{ $inc->procedimento?->codigo }}</td>
                            <td class="px-4 py-2.5 text-red-700 font-medium">{{ $inc->mensagem_inconsistencia }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Tabela de Produções Validadas -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-xs">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200">
            <h3 class="text-xs font-bold text-slate-900">Produções Validadas para Exportação Tipo 03 (BPA-i)</h3>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead class="bg-slate-50 font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-2.5">Data</th>
                    <th class="px-4 py-2.5">Paciente / CNS</th>
                    <th class="px-4 py-2.5">Profissional / CBO</th>
                    <th class="px-4 py-2.5">SIGTAP / CID-10</th>
                    <th class="px-4 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($auditoria['validados'] as $val)
                    <tr>
                        <td class="px-4 py-2.5 font-mono">{{ $val->data_atendimento?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5">
                            <span class="font-semibold text-slate-900 block">{{ $val->paciente?->nome }}</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $val->paciente?->cns }}</span>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="font-medium text-slate-800 block">{{ $val->profissional?->nome }}</span>
                            <span class="font-mono text-[11px] text-slate-500">CBO {{ $val->cbo_executante }}</span>
                        </td>
                        <td class="px-4 py-2.5 font-mono">
                            {{ $val->procedimento?->codigo }} • CID {{ $val->cid_atendimento }}
                        </td>
                        <td class="px-4 py-2.5">
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

