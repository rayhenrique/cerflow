<div class="space-y-6">
    <!-- Cabeçalho do Módulo de Configurações e Cadastros Base -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Configurações, Parâmetros SUS & Cadastros Base</h2>
            <p class="text-xs text-slate-500">
                Gerenciamento de salas, equipe multiprofissional, pacientes, tabela SIGTAP/CBO e trilha de auditoria
            </p>
        </div>

        @php
            $rotulosAbas = [
                'salas' => 'Salas & Espaços',
                'profissionais' => 'Usuários & Profissionais',
                'pacientes' => 'Pacientes (CNS)',
                'sigtap' => 'Tabela SIGTAP x CBO',
                'auditoria' => 'Auditoria LGPD',
                'perfil' => 'Minha Conta & PIN',
            ];
        @endphp

        <div class="flex flex-wrap gap-1.5 rounded-lg border border-slate-200 bg-slate-100 p-1 text-xs">
            @foreach($abasPermitidas as $aba)
                <button type="button"
                        wire:click="selecionarAba('{{ $aba }}')"
                        class="rounded-md px-3 py-1.5 font-semibold transition {{ $abaAtiva === $aba ? 'bg-white text-teal-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    {{ $rotulosAbas[$aba] ?? $aba }}
                </button>
            @endforeach
        </div>
    </div>

    @if (session()->has('sucesso_config'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('sucesso_config') }}
        </div>
    @endif

    <!-- ABA 1: SALAS & ESPAÇOS FÍSICOS -->
    @if($abaAtiva === 'salas')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 text-xs">
            <form wire:submit="salvarSala" class="lg:col-span-4 bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4 h-fit">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Cadastrar Sala / Espaço</h3>

                <div>
                    <label class="block font-semibold text-slate-700">Nome de Identificação</label>
                    <input type="text" wire:model="sala_nome" placeholder="Ex: Consultório 11 - Fonoaudiologia" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('sala_nome') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">Tipo de Espaço</label>
                    <select wire:model="sala_tipo" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="CONSULTORIO">CONSULTORIO</option>
                        <option value="SENSORIAL">SENSORIAL</option>
                        <option value="AVD">AVD</option>
                        <option value="PEDIATRIA">PEDIATRIA</option>
                        <option value="ANEXO">ANEXO</option>
                    </select>
                </div>

                <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">
                    Salvar Sala na Grade
                </button>
            </form>

            <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Espaço Físico</th>
                            <th class="px-4 py-3">Classificação</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($salas as $sala)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $sala->nome }}</td>
                                <td class="px-4 py-3 font-mono text-slate-600">{{ $sala->tipo }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $sala->ativo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $sala->ativo ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="alternarStatusSala({{ $sala->id }})" class="rounded border border-slate-300 px-2.5 py-1 font-medium text-slate-700 hover:bg-slate-50">
                                        {{ $sala->ativo ? 'Inativar' : 'Ativar' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ABA 2: USUÁRIOS, PERFIS RBAC & PROFISSIONAIS DE SAÚDE -->
    @if($abaAtiva === 'profissionais')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 text-xs">
            <form wire:submit="salvarUsuarioProfissional" class="lg:col-span-5 bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3 h-fit">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Novo Usuário & Profissional</h3>

                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700">Nome Completo</label>
                        <input type="text" wire:model="usr_name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @error('usr_name') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">E-mail de Acesso</label>
                        <input type="email" wire:model="usr_email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @error('usr_email') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700">Senha Inicial</label>
                        <input type="password" wire:model="usr_password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @error('usr_password') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700">Perfil de Acesso (Role Spatie)</label>
                        <select wire:model="usr_role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="terapeuta">Terapeuta / Especialista (terapeuta)</option>
                            <option value="recepcao">Recepção & Grade (recepcao)</option>
                            <option value="faturamento">Faturamento & Auditoria BPA-i (faturamento)</option>
                            <option value="coordenacao">Coordenação Geral (coordenacao)</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 pt-2 font-semibold text-teal-800">
                    <input type="checkbox" wire:model.live="vincular_profissional" class="rounded border-slate-300 text-teal-600">
                    Vincular credencial de Profissional de Saúde (CNS / CBO / PIN)
                </label>

                @if($vincular_profissional)
                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 border border-slate-200">
                        <div>
                            <label class="block font-semibold text-slate-700">CPF (11 dígitos)</label>
                            <input type="text" maxlength="14" wire:model="prof_cpf" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono">
                            @error('prof_cpf') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">CNS (15 dígitos)</label>
                            <input type="text" maxlength="15" wire:model="prof_cns" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono">
                            @error('prof_cns') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">Registro no Conselho</label>
                            <input type="text" wire:model="prof_conselho" placeholder="Ex: CREFITO-1 12345" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
                            @error('prof_conselho') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">CBO Principal</label>
                            <select wire:model="prof_cbo" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono">
                                <option value="223605">223605 - Fisioterapeuta</option>
                                <option value="223810">223810 - Fonoaudiólogo</option>
                                <option value="223905">223905 - Terapeuta Ocupacional</option>
                                <option value="251510">251510 - Psicólogo Clínico</option>
                                <option value="251605">251605 - Assistente Social</option>
                                <option value="225112">225112 - Médico Neurologista</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">Especialidade</label>
                            <input type="text" wire:model="prof_especialidade" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700">PIN Assinatura (4-12 díg)</label>
                            <input type="password" wire:model="prof_pin" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 font-mono">
                            @error('prof_pin') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">
                    Criar Usuário / Profissional
                </button>
            </form>

            <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Nome / E-mail</th>
                            <th class="px-4 py-3">Perfil RBAC</th>
                            <th class="px-4 py-3">Vínculo Clínico (CBO / CNS)</th>
                            <th class="px-4 py-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($usuarios as $u)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 block">{{ $u->name }}</span>
                                    <span class="text-[11px] text-slate-500">{{ $u->email }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono uppercase text-[11px] text-teal-700 font-bold">
                                    {{ $u->roles->pluck('name')->join(', ') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($u->profissional)
                                        <span class="font-medium text-slate-800 block">{{ $u->profissional->especialidade }} (CBO {{ $u->profissional->cbo_padrao }})</span>
                                        <span class="font-mono text-[11px] text-slate-500">CNS {{ $u->profissional->cns }} • {{ $u->profissional->registro_conselho }}</span>
                                    @else
                                        <span class="text-slate-400">Administrativo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="alternarStatusUsuario({{ $u->id }})" class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $u->ativo ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $u->ativo ? 'Ativo' : 'Inativo' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ABA 3: CADASTRO DE PACIENTES / USUÁRIOS DO CER -->
    @if($abaAtiva === 'pacientes')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 text-xs">
            <form wire:submit="salvarPaciente" class="lg:col-span-5 bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3 h-fit">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Cadastrar Paciente SUS (RN02)</h3>

                <div>
                    <label class="block font-semibold text-slate-700">Nome Completo do Paciente</label>
                    <input type="text" wire:model="pac_nome" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('pac_nome') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700">CNS (15 dígitos - Luhn/SUS)</label>
                        <input type="text" maxlength="18" wire:model="pac_cns" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
                        @error('pac_cns') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">CPF (11 dígitos - Opcional)</label>
                        <input type="text" maxlength="14" wire:model="pac_cpf" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
                        @error('pac_cpf') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700">Nascimento</label>
                        <input type="date" wire:model="pac_data_nascimento" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2">
                        @error('pac_data_nascimento') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">Sexo</label>
                        <select wire:model="pac_sexo" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2">
                            <option value="M">Masculino (M)</option>
                            <option value="F">Feminino (F)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">IBGE (6 díg)</label>
                        <input type="text" maxlength="6" wire:model="pac_municipio_ibge" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2 font-mono">
                        @error('pac_municipio_ibge') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">Nome da Mãe</label>
                    <input type="text" wire:model="pac_nome_mae" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('pac_nome_mae') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700">CID-10 Principal</label>
                        <input type="text" placeholder="Ex: G800" wire:model="pac_cid_principal" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono uppercase">
                        @error('pac_cid_principal') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">CID Secundário</label>
                        <input type="text" wire:model="pac_cid_secundario" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">WhatsApp / Tel</label>
                        <input type="text" wire:model="pac_telefone_contato" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">Modalidades de Reabilitação</label>
                    <div class="mt-1 grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-2.5 border border-slate-200">
                        @foreach(['FISICA' => 'Física', 'INTELECTUAL' => 'Intelectual / TEA', 'AUDITIVA' => 'Auditiva', 'VISUAL' => 'Visual'] as $chaveMod => $labelMod)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="pac_modalidades" value="{{ $chaveMod }}" class="rounded border-slate-300 text-teal-600">
                                <span>{{ $labelMod }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">
                    Salvar Paciente
                </button>
            </form>

            <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Paciente / Mãe</th>
                            <th class="px-4 py-3">CNS (15 dígitos)</th>
                            <th class="px-4 py-3">Nascimento / CID</th>
                            <th class="px-4 py-3">Modalidades</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pacientes as $p)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-900 block">{{ $p->nome }}</span>
                                    <span class="text-[11px] text-slate-500">Mãe: {{ $p->nome_mae }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold text-teal-700">{{ $p->cns }}</td>
                                <td class="px-4 py-3">
                                    {{ $p->data_nascimento?->format('d/m/Y') }} ({{ $p->sexo }}) • <span class="font-mono font-bold">{{ $p->cid_principal }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($p->modalidades_reabilitacao ?? [] as $m)
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-700">{{ $m }}</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400">Nenhum paciente cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ABA 4: TABELA SIGTAP X CBO -->
    @if($abaAtiva === 'sigtap')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 text-xs">
            <form wire:submit="salvarProcedimentoSigtap" class="lg:col-span-4 bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3 h-fit">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Parametrizar Procedimento SIGTAP</h3>

                <div>
                    <label class="block font-semibold text-slate-700">Código SIGTAP (10 dígitos)</label>
                    <input type="text" maxlength="10" wire:model="sig_codigo" placeholder="0301070075" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
                    @error('sig_codigo') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">Descrição Oficial</label>
                    <input type="text" wire:model="sig_descricao" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('sig_descricao') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700">CBOs Autorizados (separados por vírgula)</label>
                    <input type="text" wire:model="sig_cbos_csv" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
                    @error('sig_cbos_csv') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700">Idade Mín.</label>
                        <input type="number" wire:model="sig_idade_minima" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">Idade Máx.</label>
                        <input type="number" wire:model="sig_idade_maxima" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700">Sexo</label>
                        <select wire:model="sig_sexo_permitido" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5">
                            <option value="AMBOS">AMBOS</option>
                            <option value="M">M</option>
                            <option value="F">F</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">
                    Salvar Parâmetro SIGTAP
                </button>
            </form>

            <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Procedimento SIGTAP</th>
                            <th class="px-4 py-3">CBOs Permitidos</th>
                            <th class="px-4 py-3">Faixa Etária</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($procedimentosSigtap as $proc)
                            <tr>
                                <td class="px-4 py-3 font-mono font-bold text-teal-700">{{ $proc->codigo }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $proc->descricao }}</td>
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-600">
                                    {{ implode(', ', $proc->cbos_permitidos ?? []) }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    {{ $proc->idade_minima }} a {{ $proc->idade_maxima }} anos ({{ $proc->sexo_permitido }})
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ABA 5: TRILHA DE AUDITORIA LGPD / CFM -->
    @if($abaAtiva === 'auditoria')
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden text-xs">
            <div class="bg-slate-50 px-4 py-3 border-b border-slate-200">
                <h3 class="font-bold text-slate-900">Trilha Imutável de Auditoria (LGPD / CFM — Últimos 40 Eventos)</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 font-semibold text-slate-700">
                    <tr>
                        <th class="px-4 py-3">Data/Hora</th>
                        <th class="px-4 py-3">Usuário</th>
                        <th class="px-4 py-3">Ação Auditada</th>
                        <th class="px-4 py-3">Tabela / Registro</th>
                        <th class="px-4 py-3">Endereço IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logsAuditoria as $log)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-slate-600">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-2.5 font-semibold text-slate-900">{{ $log->user?->name ?? 'Sistema' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-teal-800">
                                    {{ $log->acao }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-slate-600">
                                {{ $log->tabela_afetada }} {{ $log->registro_id ? '#'.$log->registro_id : '' }}
                            </td>
                            <td class="px-4 py-2.5 font-mono text-slate-500">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">Nenhum evento de auditoria registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- ABA 6: MINHA CONTA & PIN DE ASSINATURA -->
    @if($abaAtiva === 'perfil')
        <div class="max-w-md bg-white p-6 rounded-xl border border-slate-200 shadow-xs text-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Atualizar Senha de Acesso e PIN Eletrônico</h3>

            <form wire:submit="atualizarCredenciaisProprias" class="space-y-4">
                <div>
                    <label class="block font-semibold text-slate-700">Nova Senha de Login (deixe em branco para manter)</label>
                    <input type="password" wire:model="nova_senha" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('nova_senha') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if(auth()->user()?->profissional)
                    <div>
                        <label class="block font-semibold text-slate-700">Novo PIN de Assinatura Clínica SHA-256 (4 a 12 dígitos)</label>
                        <input type="password" wire:model="novo_pin" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono">
                        @error('novo_pin') <span class="text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-800">
                    Salvar Credenciais
                </button>
            </form>
        </div>
    @endif
</div>

