<div class="space-y-6">
    @php
        $metaSecoes = [
            'pacientes' => [
                'badge' => 'Recepção & Regulação SUS',
                'titulo' => 'Cadastro de Pacientes (CER)',
                'subtitulo' => 'Gerencie o prontuário cadastral, validação Módulo 11 do CNS, CID-10 principal e modalidades de reabilitação.',
            ],
            'salas' => [
                'badge' => 'Infraestrutura Terapêutica',
                'titulo' => 'Salas, Consultórios & Ginásios',
                'subtitulo' => 'Cadastre, edite, ative/inative ou remova espaços físicos utilizados na Grade Semanal Multiprofissional.',
            ],
            'profissionais' => [
                'badge' => 'Gestão de Acessos & Corpo Clínico',
                'titulo' => 'Equipe Multiprofissional & Perfis (RBAC)',
                'subtitulo' => 'Gerencie contas de acesso, perfis Spatie Permission, CBOs, Conselhos de Classe e PIN de assinatura eletrônica.',
            ],
            'sigtap' => [
                'badge' => 'Faturamento Ambulatorial SUS',
                'titulo' => 'Tabela SIGTAP & Matriz CBO',
                'subtitulo' => 'Parametrize procedimentos ambulatoriais, faixas etárias e CBOs autorizados para exportação magnética BPA-I.',
            ],
            'auditoria' => [
                'badge' => 'Segurança da Informação & LGPD',
                'titulo' => 'Trilha de Auditoria Imutável',
                'subtitulo' => 'Rastreamento em tempo real de acessos ao prontuário (PEP), assinaturas clínicas com hash SHA-256 e exportações BPA-I.',
            ],
            'perfil' => [
                'badge' => 'Credenciais Pessoais',
                'titulo' => 'Meu Perfil & PIN de Assinatura Clínica',
                'subtitulo' => 'Atualize seus dados básicos de identificação, senha de login e PIN pessoal de assinatura eletrônica do PEP.',
            ],
        ];
        $secaoAtual = $metaSecoes[$abaAtiva] ?? $metaSecoes['perfil'];
    @endphp

    {{-- Cabeçalho Dinâmico do Submódulo --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-semibold mb-2">
                <span>Configurações &rarr; {{ $secaoAtual['badge'] }}</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900">{{ $secaoAtual['titulo'] }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $secaoAtual['subtitulo'] }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if ($abaAtiva === 'pacientes' && in_array('pacientes', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovoPaciente"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Paciente
                </button>
            @elseif ($abaAtiva === 'salas' && in_array('salas', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovaSala"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nova Sala / Espaço
                </button>
            @elseif ($abaAtiva === 'profissionais' && in_array('profissionais', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovoProfissional"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Usuário / Profissional
                </button>
            @elseif ($abaAtiva === 'sigtap' && in_array('sigtap', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovoSigtap"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Procedimento SIGTAP
                </button>
            @endif
        </div>
    </div>

    {{-- ===================================================================== --}}
    {{-- SUBMÓDULO 1: PACIENTES DO CER (CRUD COMPLETO COM MODAL)               --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'pacientes' && in_array('pacientes', $abasPermitidas, true))
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Listagem de Pacientes Cadastrados</h2>
                    <p class="text-xs text-slate-500">Exibindo até 50 registros filtrados em tempo real</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <input type="text" wire:model.live.debounce.250ms="buscaPaciente"
                        placeholder="Buscar por nome, CNS, CPF ou CID..."
                        class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-xs focus:border-teal-600 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Paciente / Mãe</th>
                            <th class="px-5 py-3 text-left">CNS / CPF</th>
                            <th class="px-5 py-3 text-center">CID-10</th>
                            <th class="px-5 py-3 text-left">Modalidades</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Ações CRUD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pacientes as $pac)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-800">{{ $pac->nome }}</div>
                                    <div class="text-xs text-slate-400">
                                        Nasc: {{ $pac->data_nascimento->format('d/m/Y') }} ({{ $pac->sexo }}) • Mãe: {{ $pac->nome_mae }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-700">
                                    <div>CNS: <span class="font-bold">{{ $pac->cns }}</span></div>
                                    @if ($pac->cpf)
                                        <div class="text-slate-400">CPF: {{ $pac->cpf }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-mono text-xs font-bold">{{ $pac->cid_principal }}</span>
                                    @if ($pac->cid_secundario)
                                        <span class="block text-[10px] text-slate-400 mt-0.5">+ {{ $pac->cid_secundario }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($pac->modalidades_reabilitacao ?? [] as $mod)
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">{{ $mod }}</span>
                                        @empty
                                            <span class="text-xs text-slate-400">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $pac->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $pac->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1">
                                    <button type="button" wire:click="editarPaciente({{ $pac->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="alternarStatusPaciente({{ $pac->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold {{ $pac->ativo ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} cursor-pointer">
                                        {{ $pac->ativo ? 'Inativar' : 'Ativar' }}
                                    </button>
                                    <button type="button" wire:click="confirmarExclusao('paciente', {{ $pac->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-rose-200 bg-rose-50/60 text-xs font-semibold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400 text-xs">
                                    Nenhum paciente encontrado para os critérios informados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- SUBMÓDULO 2: SALAS & ESPAÇOS TERAPÊUTICOS (CRUD COMPLETO COM MODAL)   --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'salas' && in_array('salas', $abasPermitidas, true))
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Salas, Consultórios e Ginásios Cadastrados ({{ $salas->count() }})</h2>
                    <p class="text-xs text-slate-500">Espaços disponíveis para alocação na Grade Semanal</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <input type="text" wire:model.live.debounce.250ms="buscaSala"
                        placeholder="Filtrar por nome ou tipo..."
                        class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-xs focus:border-teal-600 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Identificação da Sala / Espaço</th>
                            <th class="px-5 py-3 text-left">Classificação</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Ações CRUD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($salas as $sala)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $sala->nome }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                        {{ $sala->tipo }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $sala->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $sala->ativo ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1">
                                    <button type="button" wire:click="editarSala({{ $sala->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="alternarStatusSala({{ $sala->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold {{ $sala->ativo ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} cursor-pointer">
                                        {{ $sala->ativo ? 'Inativar' : 'Reativar' }}
                                    </button>
                                    <button type="button" wire:click="confirmarExclusao('sala', {{ $sala->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-rose-200 bg-rose-50/60 text-xs font-semibold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-slate-400 text-xs">
                                    Nenhuma sala encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- SUBMÓDULO 3: EQUIPE & PERFIS (CRUD COMPLETO COM MODAL)                --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'profissionais' && in_array('profissionais', $abasPermitidas, true))
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Corpo Clínico e Operadores do Sistema ({{ $usuarios->count() }})</h2>
                    <p class="text-xs text-slate-500">Contas de acesso, papéis RBAC e registro profissional CNS/CBO</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <input type="text" wire:model.live.debounce.250ms="buscaProfissional"
                        placeholder="Buscar por nome, e-mail, CNS ou CBO..."
                        class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-xs focus:border-teal-600 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Usuário / E-mail</th>
                            <th class="px-5 py-3 text-left">Perfil RBAC</th>
                            <th class="px-5 py-3 text-left">Vínculo Clínico (CBO / CNS / Conselho)</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Ações CRUD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($usuarios as $usr)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-800">{{ $usr->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $usr->email }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @foreach ($usr->roles as $role)
                                        <span class="px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-bold uppercase">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-600">
                                    @if ($usr->profissional)
                                        <div class="font-semibold text-slate-800">
                                            {{ $usr->profissional->especialidade }} (CBO {{ $usr->profissional->cbo_padrao }})
                                        </div>
                                        <div class="font-mono text-[11px] text-slate-400">
                                            CNS: {{ $usr->profissional->cns }} • {{ $usr->profissional->registro_conselho }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Operador Administrativo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $usr->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $usr->ativo ? 'Ativo' : 'Suspenso' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1">
                                    <button type="button" wire:click="editarUsuarioProfissional({{ $usr->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Editar
                                    </button>
                                    @if ($usr->id !== auth()->id())
                                        <button type="button" wire:click="alternarStatusUsuario({{ $usr->id }})"
                                            class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold {{ $usr->ativo ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} cursor-pointer">
                                            {{ $usr->ativo ? 'Suspender' : 'Reativar' }}
                                        </button>
                                        <button type="button" wire:click="confirmarExclusao('usuario', {{ $usr->id }})"
                                            class="inline-flex items-center px-2.5 py-1 rounded-md border border-rose-200 bg-rose-50/60 text-xs font-semibold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                            Excluir
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-400 text-xs">
                                    Nenhum profissional ou usuário localizado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- SUBMÓDULO 4: TABELA SIGTAP X CBO (CRUD COMPLETO COM MODAL)            --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'sigtap' && in_array('sigtap', $abasPermitidas, true))
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Catálogo SIGTAP Ativo no CER ({{ $procedimentosSigtap->count() }})</h2>
                    <p class="text-xs text-slate-500">Procedimentos ambulatoriais e validação cruzada de CBOs</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <input type="text" wire:model.live.debounce.250ms="buscaSigtap"
                        placeholder="Buscar por código (10 dígitos) ou descrição..."
                        class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-xs focus:border-teal-600 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"/>
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Código SIGTAP</th>
                            <th class="px-5 py-3 text-left">Descrição do Procedimento</th>
                            <th class="px-5 py-3 text-left">CBOs Autorizados</th>
                            <th class="px-5 py-3 text-center">Faixa Etária / Sexo</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Ações CRUD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($procedimentosSigtap as $proc)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-teal-700">{{ $proc->codigo }}</td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800 text-xs">{{ $proc->descricao }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($proc->cbos_permitidos ?? [] as $cbo)
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px] font-semibold">{{ $cbo }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center text-xs text-slate-600">
                                    {{ $proc->idade_minima }} a {{ $proc->idade_maxima }} anos • {{ $proc->sexo_permitido }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $proc->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $proc->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1">
                                    <button type="button" wire:click="editarProcedimentoSigtap({{ $proc->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="alternarStatusSigtap({{ $proc->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold {{ $proc->ativo ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} cursor-pointer">
                                        {{ $proc->ativo ? 'Inativar' : 'Ativar' }}
                                    </button>
                                    <button type="button" wire:click="confirmarExclusao('sigtap', {{ $proc->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-rose-200 bg-rose-50/60 text-xs font-semibold text-rose-700 hover:bg-rose-100 cursor-pointer">
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400 text-xs">
                                    Nenhum procedimento SIGTAP encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- SUBMÓDULO 5: TRILHA DE AUDITORIA LGPD (COM MODAL DE INSPEÇÃO JSON)    --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'auditoria' && in_array('auditoria', $abasPermitidas, true))
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Registros de Auditoria e Rastreabilidade (LGPD)</h2>
                    <p class="text-xs text-slate-500">Últimos 50 eventos auditados com IP de origem e metadados</p>
                </div>
                <div class="flex items-center gap-2">
                    <select wire:model.live="filtroAcaoAuditoria"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-teal-600 focus:outline-none">
                        <option value="">Todas as Ações</option>
                        <option value="ACESSO_PEP">ACESSO_PEP</option>
                        <option value="ASSINATURA_EVOLUCAO">ASSINATURA_EVOLUCAO</option>
                        <option value="EXPORTACAO_BPA">EXPORTACAO_BPA</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Data / Hora</th>
                            <th class="px-5 py-3 text-left">Operador</th>
                            <th class="px-5 py-3 text-left">Evento</th>
                            <th class="px-5 py-3 text-left">Entidade Alvo</th>
                            <th class="px-5 py-3 text-left">IP Origem</th>
                            <th class="px-5 py-3 text-right">Inspeção</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse ($logsAuditoria as $log)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3 font-mono text-slate-600">
                                    {{ $log->created_at?->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-800">
                                    {{ $log->user?->name ?? 'Sistema' }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded bg-slate-900 text-white font-mono text-[11px] font-bold">
                                        {{ $log->acao }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $log->tabela_alvo }} {{ $log->registro_id ? '#' . $log->registro_id : '' }}
                                </td>
                                <td class="px-5 py-3 font-mono text-slate-500">
                                    {{ $log->ip_origem }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" wire:click="inspecionarLogAuditoria({{ $log->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-semibold text-teal-700 hover:bg-teal-50 cursor-pointer">
                                        Ver JSON
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                    Nenhum evento de auditoria registrado até o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- SUBMÓDULO 6: MEU PERFIL & CREDENCIAIS (SENHA E PIN)                   --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'perfil')
        <div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
            <h2 class="text-base font-bold text-slate-900 mb-1">Minhas Credenciais de Acesso e Assinatura Eletrônica</h2>
            <p class="text-xs text-slate-500 mb-5">
                Atualize seus dados básicos, senha de login ou redefina o seu PIN pessoal utilizado para assinar evoluções clínicas no PEP.
            </p>

            <form wire:submit="atualizarCredenciaisProprias" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Seu Nome Completo *</label>
                        <input type="text" wire:model="perfil_nome"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        @error('perfil_nome') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">E-mail de Login *</label>
                        <input type="email" wire:model="perfil_email"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        @error('perfil_email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if (auth()->user()?->profissional)
                    <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-200 text-xs flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="text-slate-400 font-bold uppercase text-[10px] block">Vínculo Profissional SUS</span>
                            <span class="font-bold text-slate-800">
                                {{ auth()->user()->profissional->especialidade }} • CBO {{ auth()->user()->profissional->cbo_padrao }}
                            </span>
                        </div>
                        <div class="font-mono text-slate-600">
                            CNS: {{ auth()->user()->profissional->cns }} | {{ auth()->user()->profissional->registro_conselho }}
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nova Senha de Login (opcional)</label>
                        <input type="password" wire:model="nova_senha" placeholder="Deixe em branco para manter a atual"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        @error('nova_senha') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    @if (auth()->user()?->profissional)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Novo PIN de Assinatura Clínica (4 a 12 dígitos)</label>
                            <input type="password" wire:model="novo_pin" placeholder="Deixe em branco para manter o PIN"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('novo_pin') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition cursor-pointer">
                        Salvar Alterações do Perfil
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 1: CRIAR / EDITAR PACIENTE                                      --}}
    {{-- ===================================================================== --}}
    @if ($modalPacienteAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-8">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">
                            {{ $pacienteEditandoId ? 'Editar Cadastro de Paciente' : 'Novo Cadastro de Paciente (CER)' }}
                        </h3>
                        <p class="text-xs text-slate-400">Validação oficial do Cartão Nacional de Saúde (Módulo 11 DATASUS)</p>
                    </div>
                    <button type="button" wire:click="fecharModalPaciente" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarPaciente" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo do Paciente *</label>
                            <input type="text" wire:model="pac_nome" placeholder="Nome completo sem abreviações"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('pac_nome') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CNS (15 dígitos — Módulo 11) *</label>
                            <input type="text" wire:model="pac_cns" maxlength="15" placeholder="700000000000005"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                            @error('pac_cns') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CPF (11 dígitos — opcional)</label>
                            <input type="text" wire:model="pac_cpf" maxlength="14" placeholder="Apenas números"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                            @error('pac_cpf') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Data de Nascimento *</label>
                            <input type="date" wire:model="pac_data_nascimento"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('pac_data_nascimento') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Sexo *</label>
                            <select wire:model="pac_sexo"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                <option value="M">Masculino (M)</option>
                                <option value="F">Feminino (F)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nome da Mãe *</label>
                            <input type="text" wire:model="pac_nome_mae"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('pac_nome_mae') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CID-10 Principal *</label>
                            <input type="text" wire:model="pac_cid_principal" maxlength="6" placeholder="Ex.: F840, G800"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase font-mono focus:border-teal-600 focus:outline-none">
                            @error('pac_cid_principal') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CID-10 Secundário</label>
                            <input type="text" wire:model="pac_cid_secundario" maxlength="6" placeholder="Opcional"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase font-mono focus:border-teal-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Município IBGE (6 dígitos) *</label>
                            <input type="text" wire:model="pac_municipio_ibge" maxlength="6"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                            @error('pac_municipio_ibge') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Telefone / WhatsApp</label>
                            <input type="text" wire:model="pac_telefone_contato" placeholder="(82) 99999-0000"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Endereço</label>
                            <input type="text" wire:model="pac_endereco" placeholder="Rua, número, complemento"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Bairro</label>
                            <input type="text" wire:model="pac_bairro" placeholder="Bairro"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Modalidades de Reabilitação</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                            @foreach (['FISICA' => 'Física', 'INTELECTUAL' => 'Intelectual', 'AUDITIVA' => 'Auditiva', 'VISUAL' => 'Visual'] as $valor => $rotulo)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 cursor-pointer hover:bg-slate-50">
                                    <input type="checkbox" wire:model="pac_modalidades" value="{{ $valor }}" class="rounded text-teal-600">
                                    <span class="font-medium text-slate-700">{{ $rotulo }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="fecharModalPaciente"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-teal-600 px-5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer">
                            {{ $pacienteEditandoId ? 'Salvar Alterações' : 'Cadastrar Paciente' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 2: CRIAR / EDITAR SALA                                          --}}
    {{-- ===================================================================== --}}
    @if ($modalSalaAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">{{ $salaEditandoId ? 'Editar Sala / Espaço' : 'Nova Sala / Espaço Terapêutico' }}</h3>
                        <p class="text-xs text-slate-400">Infraestrutura física para alocação na Grade Semanal</p>
                    </div>
                    <button type="button" wire:click="fecharModalSala" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarSala" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nome da Sala / Consultório *</label>
                        <input type="text" wire:model="sala_nome" placeholder="Ex.: Consultório 06 - Fonoaudiologia"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                        @error('sala_nome') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipo de Espaço *</label>
                        <select wire:model="sala_tipo"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            <option value="CONSULTORIO">CONSULTÓRIO</option>
                            <option value="SENSORIAL">SALA SENSORIAL</option>
                            <option value="AVD">ATIVIDADES DE VIDA DIÁRIA (AVD)</option>
                            <option value="PEDIATRIA">GINÁSIO PEDIÁTRICO</option>
                            <option value="ANEXO">GINÁSIO / ANEXO</option>
                        </select>
                        @error('sala_tipo') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="fecharModalSala"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-teal-600 px-5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer">
                            {{ $salaEditandoId ? 'Atualizar Sala' : 'Salvar Sala' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 3: CRIAR / EDITAR USUÁRIO & PROFISSIONAL                        --}}
    {{-- ===================================================================== --}}
    @if ($modalProfissionalAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-8">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">
                            {{ $usuarioEditandoId ? 'Editar Usuário / Profissional' : 'Novo Usuário / Profissional de Saúde' }}
                        </h3>
                        <p class="text-xs text-slate-400">Controle de acesso RBAC e credenciais clínicas SUS</p>
                    </div>
                    <button type="button" wire:click="fecharModalProfissional" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarUsuarioProfissional" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo *</label>
                            <input type="text" wire:model="usr_name" placeholder="Ex.: Dra. Mariana Costa"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('usr_name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">E-mail de Acesso *</label>
                            <input type="email" wire:model="usr_email" placeholder="mariana@cerflow.gov.br"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('usr_email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                {{ $usuarioEditandoId ? 'Nova Senha (opcional)' : 'Senha Inicial *' }}
                            </label>
                            <input type="password" wire:model="usr_password"
                                placeholder="{{ $usuarioEditandoId ? 'Manter atual' : 'Mínimo 6 caracteres' }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('usr_password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Perfil de Acesso (RBAC) *</label>
                            <select wire:model="usr_role"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                <option value="terapeuta">Terapeuta / Profissional de Saúde</option>
                                <option value="recepcao">Recepção & Check-in</option>
                                <option value="faturamento">Faturamento Ambulatorial (BPA-I)</option>
                                <option value="coordenacao">Coordenação Geral (Acesso Total)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" wire:model.live="vincular_profissional" class="rounded text-teal-600">
                            <span>Possui vínculo clínico para Agenda/PEP (CNS, CBO e PIN)</span>
                        </label>
                    </div>

                    @if ($vincular_profissional)
                        <div class="space-y-3 rounded-xl bg-slate-50 p-4 border border-slate-200">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">CNS do Profissional (15 dígitos) *</label>
                                    <input type="text" wire:model="prof_cns" maxlength="15" placeholder="700000000000005"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-mono">
                                    @error('prof_cns') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">CPF (11 dígitos) *</label>
                                    <input type="text" wire:model="prof_cpf" maxlength="11"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-mono">
                                    @error('prof_cpf') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Conselho de Classe *</label>
                                    <input type="text" wire:model="prof_conselho" placeholder="CREFITO 12345-F"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                                    @error('prof_conselho') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">CBO Oficial *</label>
                                    <select wire:model="prof_cbo"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                                        <option value="223605">223605 — Fisioterapeuta</option>
                                        <option value="223810">223810 — Fonoaudiólogo</option>
                                        <option value="223905">223905 — Terapeuta Ocupacional</option>
                                        <option value="251510">251510 — Psicólogo Clínico</option>
                                        <option value="225112">225112 — Médico Neurologista</option>
                                        <option value="225125">225125 — Médico Clínico / Fisiatra</option>
                                        <option value="251605">251605 — Assistente Social</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Especialidade *</label>
                                    <input type="text" wire:model="prof_especialidade" placeholder="Ex.: Fisioterapia"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        {{ $usuarioEditandoId ? 'Novo PIN Assinatura (opcional)' : 'PIN de Assinatura (4-12 dígitos) *' }}
                                    </label>
                                    <input type="password" wire:model="prof_pin" placeholder="Ex.: 123456"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
                                    @error('prof_pin') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="fecharModalProfissional"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-teal-600 px-5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer">
                            {{ $usuarioEditandoId ? 'Salvar Alterações' : 'Criar Usuário & Profissional' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 4: CRIAR / EDITAR PROCEDIMENTO SIGTAP                           --}}
    {{-- ===================================================================== --}}
    @if ($modalSigtapAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">
                            {{ $sigtapEditandoId ? 'Editar Procedimento SIGTAP' : 'Novo Procedimento SIGTAP' }}
                        </h3>
                        <p class="text-xs text-slate-400">Parametrização de código ambulatorial e CBOs autorizados</p>
                    </div>
                    <button type="button" wire:click="fecharModalSigtap" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
                </div>

                <form wire:submit="salvarProcedimentoSigtap" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Código SIGTAP (10 dígitos) *</label>
                        <input type="text" wire:model="sig_codigo" maxlength="10" placeholder="0301070075"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                        @error('sig_codigo') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Descrição Oficial *</label>
                        <input type="text" wire:model="sig_descricao" placeholder="ATENDIMENTO FISIOTERAPÊUTICO..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-teal-600 focus:outline-none">
                        @error('sig_descricao') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">CBOs Permitidos (separados por vírgula) *</label>
                        <input type="text" wire:model="sig_cbos_csv" placeholder="223605, 223810, 223905"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                        @error('sig_cbos_csv') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Idade Mín.</label>
                            <input type="number" wire:model="sig_idade_minima"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Idade Máx.</label>
                            <input type="number" wire:model="sig_idade_maxima"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Sexo</label>
                            <select wire:model="sig_sexo_permitido"
                                class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                <option value="AMBOS">AMBOS</option>
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="fecharModalSigtap"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-teal-600 px-5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer">
                            {{ $sigtapEditandoId ? 'Salvar Alterações' : 'Cadastrar Procedimento' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 5: DETALHES JSON DO LOG DE AUDITORIA                            --}}
    {{-- ===================================================================== --}}
    @if ($logInspecionado)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Inspeção de Evento de Auditoria #{{ $logInspecionado->id }}</h3>
                        <p class="text-xs text-slate-400">{{ $logInspecionado->acao }} • {{ $logInspecionado->created_at?->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <button type="button" wire:click="fecharModalLogAuditoria" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
                </div>
                <div class="p-6 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <div>
                            <span class="text-slate-400 block">Operador:</span>
                            <span class="font-bold text-slate-800">{{ $logInspecionado->user?->name ?? 'Sistema' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">IP de Origem:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $logInspecionado->ip_origem }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="font-bold text-slate-700 block mb-1">Payload JSON Registrado:</span>
                        <pre class="rounded-lg bg-slate-950 text-teal-300 p-4 font-mono text-[11px] overflow-x-auto">{{ json_encode($logInspecionado->detalhes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="button" wire:click="fecharModalLogAuditoria"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 cursor-pointer">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 6: CONFIRMAÇÃO PADRONIZADA DE EXCLUSÃO                          --}}
    {{-- ===================================================================== --}}
    @if ($modalExclusaoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-rose-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Confirmar Exclusão de Registro</h3>
                            <p class="text-xs text-slate-600 mt-1">
                                Você solicitou a exclusão do seguinte item:
                            </p>
                            <div class="mt-2.5 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs font-bold text-slate-800">
                                {{ $exclusaoDescricao }}
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2">
                                Caso o registro possua vínculos clínicos ou faturamento BPA-I, o sistema bloqueará a remoção física automaticamente para preservar a integridade histórica.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="cancelarExclusao"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="button" wire:click="excluirRegistro"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer">
                            Confirmar Exclusão
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
