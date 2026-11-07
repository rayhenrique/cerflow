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
            'estabelecimento' => [
                'badge' => 'Identidade Institucional',
                'titulo' => 'Identificação do Estabelecimento',
                'subtitulo' => 'Configure a logo institucional, o código IBGE padrão e o CNES utilizado no faturamento SUS.',
            ],
            'esus' => [
                'badge' => 'Atenção Primária à Saúde',
                'titulo' => 'Integração e-SUS APS PEC',
                'subtitulo' => 'Conexão segura somente-leitura com o banco PostgreSQL do e-SUS PEC para descoberta de schema e sincronização de pacientes.',
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
    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-semibold mb-2">
                <span>Configurações &rarr; {{ $secaoAtual['badge'] }}</span>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900">{{ $secaoAtual['titulo'] }}</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">{{ $secaoAtual['subtitulo'] }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
            @if ($abaAtiva === 'pacientes' && in_array('pacientes', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovoPaciente"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Paciente
                </button>
            @elseif ($abaAtiva === 'salas' && in_array('salas', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovaSala"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nova Sala / Espaço
                </button>
            @elseif ($abaAtiva === 'profissionais' && in_array('profissionais', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovoProfissional"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Usuário / Profissional
                </button>
            @elseif ($abaAtiva === 'sigtap' && in_array('sigtap', $abasPermitidas, true))
                <button type="button" wire:click="abrirModalNovoSigtap"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Procedimento SIGTAP
                </button>
            @elseif ($abaAtiva === 'esus' && in_array('esus', $abasPermitidas, true))
                <button type="button" wire:click="testarConexaoEsus" wire:loading.attr="disabled"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition cursor-pointer disabled:opacity-60">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span wire:loading.remove wire:target="testarConexaoEsus">Testar Conexão</span>
                    <span wire:loading wire:target="testarConexaoEsus">Testando...</span>
                </button>
                <button type="button" wire:click="sincronizarEsusAgora" wire:loading.attr="disabled"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition cursor-pointer disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span wire:loading.remove wire:target="sincronizarEsusAgora">Sincronizar Agora</span>
                    <span wire:loading wire:target="sincronizarEsusAgora">Sincronizando...</span>
                </button>
            @endif
        </div>
    </div>

    {{-- ===================================================================== --}}
    {{-- IDENTIFICAÇÃO DO ESTABELECIMENTO (Coordenação)                        --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'estabelecimento' && in_array('estabelecimento', $abasPermitidas, true))
        @php($configuracaoEstabelecimento = \App\Models\ConfiguracaoEstabelecimento::atual())
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <section class="xl:col-span-1 bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Logo institucional</h2>
                    <p class="text-xs text-slate-500 mt-1">A imagem substituirá o monograma CD no menu lateral e na tela de acesso.</p>
                </div>
                <div class="min-h-36 rounded-xl border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center p-4">
                    @if ($logoEstabelecimento)
                        <img src="{{ $logoEstabelecimento->temporaryUrl() }}" alt="Pré-visualização da logo" class="max-h-28 max-w-full object-contain">
                    @elseif ($configuracaoEstabelecimento->logo_path && !$removerLogoEstabelecimento)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuracaoEstabelecimento->logo_path) }}" alt="Logo do estabelecimento" class="max-h-28 max-w-full object-contain">
                    @else
                        <div class="text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5l4.5-4.5 3 3L16.5 9 21 13.5M4.5 4.5h15A1.5 1.5 0 0121 6v12a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18V6a1.5 1.5 0 011.5-1.5z"/></svg>
                            <span class="text-xs">Nenhuma logo cadastrada</span>
                        </div>
                    @endif
                </div>
                <input type="file" wire:model="logoEstabelecimento" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-teal-700 hover:file:bg-teal-100">
                <p class="text-[11px] text-slate-500">PNG, JPG ou WebP. Tamanho máximo: 2 MB.</p>
                @error('logoEstabelecimento') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                @if ($configuracaoEstabelecimento->logo_path || $logoEstabelecimento)
                    <label class="inline-flex items-center gap-2 text-xs text-rose-700">
                        <input type="checkbox" wire:model="removerLogoEstabelecimento" class="rounded border-slate-300 text-rose-600">
                        Remover logo atual ao salvar
                    </label>
                @endif
            </section>
            <section class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                <h2 class="text-sm font-bold text-slate-900">Dados do estabelecimento</h2>
                <p class="text-xs text-slate-500 mt-1 mb-5">Esses valores serão usados como padrão nos cadastros novos e no arquivo magnético BPA-i.</p>
                <form wire:submit="salvarIdentificacaoEstabelecimento" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Código IBGE padrão do município *</label>
                        <input type="text" inputmode="numeric" maxlength="6" wire:model="estabelecimento_ibge" placeholder="6 dígitos" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-teal-600 focus:outline-none">
                        <p class="text-[11px] text-slate-500 mt-1">Código municipal de 6 dígitos, conforme o layout atualmente adotado pelo sistema.</p>
                        @error('estabelecimento_ibge') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">CNES do estabelecimento *</label>
                        <input type="text" inputmode="numeric" maxlength="7" wire:model="estabelecimento_cnes" placeholder="7 dígitos" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-teal-600 focus:outline-none">
                        <p class="text-[11px] text-slate-500 mt-1">CNES de 7 dígitos usado na identificação do prestador no BPA-i.</p>
                        @error('estabelecimento_cnes') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-teal-700 disabled:opacity-60 cursor-pointer">
                            <span wire:loading.remove wire:target="salvarIdentificacaoEstabelecimento">Salvar identificação</span>
                            <span wire:loading wire:target="salvarIdentificacaoEstabelecimento">Salvando...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- INTEGRAÇÃO e-SUS APS PEC (Coordenação)                                 --}}
    {{-- ===================================================================== --}}
    @if ($abaAtiva === 'esus' && in_array('esus', $abasPermitidas, true))
        <div class="space-y-6">
            <!-- Banner Informativo & Segurança -->
            <div class="rounded-xl border border-teal-200 bg-teal-50/60 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-teal-600 p-2 text-white shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Integração Segura Unidirecional (Somente Leitura)</h2>
                        <p class="text-xs text-slate-600 mt-0.5">
                            O CER Digital lê exclusivamente a base do e-SUS PEC via PostgreSQL, sem jamais executar comandos de escrita no servidor de origem.
                            A senha é criptografada com AES-256-CBC no banco local.
                        </p>
                    </div>
                </div>
                @if ($esus_endereco_web)
                    <a href="{{ $esus_endereco_web }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-teal-300 bg-white px-3 py-1.5 text-xs font-semibold text-teal-800 hover:bg-teal-50 transition shrink-0">
                        <span>Acessar Web PEC</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif
            </div>

            <!-- Cards Rápidos de Indicadores -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Status da Integração -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs text-slate-500 block">Status da Integração</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="w-2.5 h-2.5 rounded-full {{ $esus_ativo ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        <span class="font-bold text-sm text-slate-800">{{ $esus_ativo ? 'Ativa' : 'Inativa' }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Rotina agendada diariamente às 02:00</span>
                </div>

                <!-- Conexão PostgreSQL -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs text-slate-500 block">Conexão PostgreSQL</span>
                    <div class="flex items-center gap-2 mt-1">
                        @if ($esus_ultimo_status === 'SUCESSO')
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="font-bold text-sm text-emerald-700">Conectado</span>
                        @elseif ($esus_ultimo_status === 'ERRO')
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="font-bold text-sm text-rose-700">Falha de Conexão</span>
                        @else
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="font-bold text-sm text-slate-600">Não Testado</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block truncate">
                        {{ $esus_host }}:{{ $esus_porta }}/{{ $esus_banco }}
                    </span>
                </div>

                <!-- Diagnóstico de Schema -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs text-slate-500 block">Origem Detectada</span>
                    <div class="flex items-center gap-2 mt-1">
                        @if ($esus_tipo_banco === 'DW_PEC')
                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-xs font-bold font-mono">DW PEC Oficial</span>
                        @elseif ($esus_tipo_banco === 'PEC_OPERACIONAL')
                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-xs font-bold font-mono">PEC Operacional</span>
                        @else
                            <span class="text-xs font-semibold text-slate-500">Pendente de teste</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">
                        {{ $esus_tabela_origem ? "Tabela: {$esus_tabela_origem}" : 'Aguardando teste de conexão' }}
                    </span>
                </div>

                <!-- Última Sincronização -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <span class="text-xs text-slate-500 block">Última Sincronização</span>
                    <span class="font-bold text-sm text-slate-800 mt-1 block">
                        {{ $esus_ultima_sincronizacao ?: 'Nenhuma realizada' }}
                    </span>
                    @if ($esus_ultima_sincronizacao_status)
                        <span class="text-[11px] font-semibold {{ $esus_ultima_sincronizacao_status === 'SUCESSO' ? 'text-emerald-600' : ($esus_ultima_sincronizacao_status === 'INTERROMPIDO' ? 'text-amber-600' : 'text-rose-600') }} mt-1 block">
                            Status: {{ $esus_ultima_sincronizacao_status }}
                        </span>
                    @else
                        <span class="text-[11px] text-slate-400 mt-1 block">Execuções registradas abaixo</span>
                    @endif
                </div>
            </div>

            <!-- Grid: Formulário de Conexão + Diagnóstico Detalhado -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Coluna Esquerda: Parâmetros de Conexão (7 cols) -->
                <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Parâmetros de Conexão PostgreSQL</h3>
                            <p class="text-xs text-slate-500">Configuração de acesso read-only ao e-SUS APS PEC</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="esus_ativo" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-600"></div>
                            <span class="ml-2 text-xs font-semibold text-slate-700">Habilitar Integração</span>
                        </label>
                    </div>

                    <form wire:submit="salvarConfiguracaoEsus" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Host / Servidor *</label>
                                <input type="text" wire:model="esus_host" placeholder="localhost ou IP do servidor e-SUS"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                                <p class="text-[10px] text-slate-400 mt-1">'localhost' refere-se ao host onde o CER Digital é executado.</p>
                                @error('esus_host') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Porta *</label>
                                <input type="number" wire:model="esus_porta" placeholder="5433"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                                @error('esus_porta') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nome do Banco de Dados *</label>
                                <input type="text" wire:model="esus_banco" placeholder="esus"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                                @error('esus_banco') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Usuário PostgreSQL *</label>
                                <input type="text" wire:model="esus_usuario" placeholder="esus_leitura"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                                <p class="text-[10px] text-slate-400 mt-1">Usuário com privilégios exclusivamente de leitura (SELECT).</p>
                                @error('esus_usuario') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700">Senha de Acesso</label>
                                @if ($esus_tem_senha)
                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Senha criptografada salva
                                    </span>
                                @endif
                            </div>
                            <input type="password" wire:model="esus_senha" placeholder="{{ $esus_tem_senha ? '•••••••••••• (deixe em branco para manter a atual)' : 'Informe a senha de leitura' }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                            <p class="text-[11px] text-slate-400 mt-1">
                                Criptografada no banco de dados do CER com a APP_KEY. Nunca exibida descriptografada.
                            </p>
                            @error('esus_senha') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Endereço Web do PEC (Interface do Usuário)</label>
                            <input type="url" wire:model="esus_endereco_web" placeholder="http://189.89.61.50:8181"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                            <p class="text-[11px] text-slate-400 mt-1">Endereço web HTTP do PEC (não confundir com o endpoint PostgreSQL).</p>
                            @error('esus_endereco_web') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                            <button type="button" wire:click="testarConexaoEsus" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer disabled:opacity-60">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span wire:loading.remove wire:target="testarConexaoEsus">Testar Conexão</span>
                                <span wire:loading wire:target="testarConexaoEsus">Testando...</span>
                            </button>

                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer disabled:opacity-60">
                                <span wire:loading.remove wire:target="salvarConfiguracaoEsus">Salvar Configurações</span>
                                <span wire:loading wire:target="salvarConfiguracaoEsus">Salvando...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Coluna Direita: Diagnóstico & Descoberta do Schema (5 cols) -->
                <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Descoberta do Banco e-SUS</h3>
                            <p class="text-xs text-slate-500">Auditoria estrutural e diagnóstico de compatibilidade</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-mono text-slate-600">
                            {{ $esus_total_tabelas }} tabelas
                        </span>
                    </div>

                    @if ($esus_mensagem_status)
                        <div class="rounded-lg p-3 text-xs {{ $esus_ultimo_status === 'SUCESSO' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                            <div class="font-bold mb-0.5">{{ $esus_ultimo_status === 'SUCESSO' ? 'Conexão Estabelecida' : 'Falha no Teste de Conexão' }}</div>
                            <p>{{ $esus_mensagem_status }}</p>
                            @if ($esus_ultima_conexao)
                                <span class="text-[10px] opacity-75 mt-1 block">Testado em {{ $esus_ultima_conexao }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="text-slate-500">Classificação do Banco</span>
                            <span class="font-bold text-slate-800">
                                @if ($esus_tipo_banco === 'DW_PEC')
                                    DW e-SUS APS PEC (Oficial)
                                @elseif ($esus_tipo_banco === 'PEC_OPERACIONAL')
                                    PEC Operacional (Transacional)
                                @else
                                    Não identificado
                                @endif
                            </span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="text-slate-500">Tabela de Cidadãos Vinculados</span>
                            <span class="font-mono font-semibold {{ $esus_tabela_origem === 'tb_acomp_cidadaos_vinculados' ? 'text-emerald-700' : 'text-slate-600' }}">
                                {{ $esus_tabela_origem ?: 'tb_acomp_cidadaos_vinculados (Ausente)' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="text-slate-500">Compatibilidade para Importação</span>
                            @if ($esus_tipo_banco === 'DW_PEC')
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[11px]">Compatível (DW)</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[11px]">Requer DW Oficial</span>
                            @endif
                        </div>

                        @if ($esus_diagnostico && !empty($esus_diagnostico['colunas_origem']))
                            <div>
                                <span class="text-slate-500 block mb-1">Colunas Mapeadas na Tabela:</span>
                                <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto p-1.5 rounded-lg bg-slate-50 border border-slate-200">
                                    @foreach ($esus_diagnostico['colunas_origem'] as $col)
                                        <span class="px-1.5 py-0.5 rounded bg-white text-slate-700 font-mono text-[10px] border border-slate-200">{{ $col }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="rounded-lg bg-slate-50 p-3 text-[11px] text-slate-600 space-y-1">
                            <span class="font-bold text-slate-700 block">Referência Oficial:</span>
                            <p>Documentação de Integração Bridge / e-SUS APS:</p>
                            <a href="https://integracao.esusaps.bridge.ufsc.tech/" target="_blank" rel="noopener noreferrer" class="text-teal-700 hover:underline font-mono break-all block">
                                https://integracao.esusaps.bridge.ufsc.tech/
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela: Histórico de Sincronizações -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Histórico de Sincronizações e-SUS PEC</h3>
                        <p class="text-xs text-slate-500">Últimas execuções manuais e agendadas com estatísticas agregadas</p>
                    </div>
                    <button type="button" wire:click="sincronizarEsusAgora" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-teal-700 transition cursor-pointer disabled:opacity-60">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span wire:loading.remove wire:target="sincronizarEsusAgora">Sincronizar Agora</span>
                        <span wire:loading wire:target="sincronizarEsusAgora">Executando...</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Início / Duração</th>
                                <th class="px-5 py-3 text-center">Tipo</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Consultados</th>
                                <th class="px-5 py-3 text-center">Novos</th>
                                <th class="px-5 py-3 text-center">Atualizados</th>
                                <th class="px-5 py-3 text-center">Duplicidades</th>
                                <th class="px-5 py-3 text-left">Observação / Erro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse ($historicoEsus as $hist)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-5 py-3 text-slate-700">
                                        <div class="font-bold">{{ $hist->iniciado_em?->format('d/m/Y H:i:s') }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $hist->duracao_segundos }}s de execução</div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $hist->tipo_execucao === 'AGENDADA' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $hist->tipo_execucao }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold
                                            {{ $hist->status === 'SUCESSO' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                            {{ $hist->status === 'INTERROMPIDO' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                            {{ $hist->status === 'ERRO' || $hist->status === 'CONEXAO_FALHOU' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}
                                            {{ $hist->status === 'EM_ANDAMENTO' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}">
                                            {{ $hist->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center font-mono font-bold text-slate-800">{{ $hist->total_consultado }}</td>
                                    <td class="px-5 py-3 text-center font-mono text-emerald-700 font-bold">+{{ $hist->novos_pacientes }}</td>
                                    <td class="px-5 py-3 text-center font-mono text-teal-700 font-bold">{{ $hist->pacientes_atualizados }}</td>
                                    <td class="px-5 py-3 text-center font-mono text-amber-700 font-bold">{{ $hist->duplicidades_identificadas }}</td>
                                    <td class="px-5 py-3 text-slate-500 max-w-xs truncate" title="{{ $hist->mensagem_erro }}">
                                        {{ $hist->mensagem_erro ?: 'Sincronização executada com sucesso.' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-slate-400 text-xs">
                                        Nenhuma sincronização realizada até o momento. Utilize o botão "Sincronizar Agora" ou aguarde a rotina agendada (02:00).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

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
                            <th class="px-5 py-3 text-left">Paciente / Nascimento</th>
                            <th class="px-5 py-3 text-left">CNS / CPF</th>
                            @if (auth()->user()?->hasAnyRole(['coordenacao', 'terapeuta']))
                                <th class="px-5 py-3 text-center">CID-10</th>
                                <th class="px-5 py-3 text-left">Modalidades</th>
                            @else
                                <th class="px-5 py-3 text-left">Vínculo APS / UBS</th>
                                <th class="px-5 py-3 text-left">Contato / Telefone</th>
                            @endif
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
                                        Nasc: {{ $pac->data_nascimento?->format('d/m/Y') }} ({{ $pac->sexo }})
                                        @if ($pac->nome_mae)
                                            • Mãe: {{ $pac->nome_mae }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-700">
                                    <div>
                                        CNS:
                                        @if ($pac->cns)
                                            <span class="font-bold text-teal-700">{{ $pac->cns }}</span>
                                        @else
                                            <span class="text-slate-400 font-normal italic">Não informado</span>
                                        @endif
                                    </div>
                                    @if ($pac->cpf)
                                        <div class="text-slate-400">CPF: {{ $pac->cpf }}</div>
                                    @endif
                                </td>
                                @if (auth()->user()?->hasAnyRole(['coordenacao', 'terapeuta']))
                                    <td class="px-5 py-3.5 text-center">
                                        @if ($pac->cid_principal)
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-mono text-xs font-bold">{{ $pac->cid_principal }}</span>
                                            @if ($pac->cid_secundario)
                                                <span class="block text-[10px] text-slate-400 mt-0.5">+ {{ $pac->cid_secundario }}</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400 italic">Pendente</span>
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
                                @else
                                    <td class="px-5 py-3.5 text-xs text-slate-600">
                                        <div class="font-medium text-slate-800">{{ $pac->ubs_referencia ?: 'Não informada' }}</div>
                                        @if ($pac->nome_acs)
                                            <div class="text-[11px] text-slate-400">ACS: {{ $pac->nome_acs }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-slate-600">
                                        <div>{{ $pac->telefone_contato ?: 'Sem telefone' }}</div>
                                        @if ($pac->telefone_secundario)
                                            <div class="text-[11px] text-slate-400">Recado: {{ $pac->telefone_secundario }}</div>
                                        @endif
                                    </td>
                                @endif
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
    {{-- MODAL 1: CRIAR / EDITAR PACIENTE (REFORMULADO — PADRÃO SUS / CER)     --}}
    {{-- ===================================================================== --}}
    @if ($modalPacienteAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4 overflow-y-auto">
            <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-8">
                <!-- Cabeçalho do Modal no estilo do design de referência -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">
                            {{ $pacienteEditandoId ? 'Editar Cadastro de Paciente' : 'Cadastrar Novo Paciente' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Preencha os dados do paciente com validação de CPF e vinculação à UBS</p>
                    </div>
                    <button type="button" wire:click="fecharModalPaciente"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        ← VOLTAR À LISTA
                    </button>
                </div>

                <form wire:submit="salvarPaciente" class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                    {{-- SEÇÃO 1: IDENTIFICAÇÃO DO CIDADÃO --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                            <span class="w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Identificação do Cidadão</h4>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nome Completo *</label>
                            <input type="text" wire:model="pac_nome" placeholder="Ex: Maria José da Silva"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('pac_nome') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">CPF (11 Dígitos)</label>
                                <input type="text" wire:model="pac_cpf" maxlength="14" placeholder="000.000.000-00 (opcional)"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                                @error('pac_cpf') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Cartão Nacional de Saúde (CNS)</label>
                                <input type="text" wire:model="pac_cns" maxlength="15" placeholder="Ex: 700000000000000 (15 dígitos)"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                                @error('pac_cns') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Data de Nascimento *</label>
                                <input type="date" wire:model="pac_data_nascimento"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                @error('pac_data_nascimento') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Sexo *</label>
                                <select wire:model="pac_sexo"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                                @error('pac_sexo') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nome da Mãe</label>
                            <input type="text" wire:model="pac_nome_mae" placeholder="Nome completo da mãe (opcional)"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('pac_nome_mae') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- SEÇÃO 2: VÍNCULO COM A ATENÇÃO PRIMÁRIA --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                            <span class="w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Vínculo com a Atenção Primária</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">UBS de Referência</label>
                                <input list="ubs-list" wire:model="pac_ubs_referencia" placeholder="Selecione a UBS (opcional)"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                <datalist id="ubs-list">
                                    @foreach ($listaUbs as $ubs)
                                        <option value="{{ $ubs }}"></option>
                                    @endforeach
                                </datalist>
                                @error('pac_ubs_referencia') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Agente Comunitário de Saúde (ACS)</label>
                                <input type="text" wire:model="pac_nome_acs" placeholder="Ex: ACS Maria Pereira"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                @error('pac_nome_acs') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="sm:w-1/2">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Município IBGE (6 dígitos) *</label>
                            <input type="text" wire:model="pac_municipio_ibge" maxlength="6"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-teal-600 focus:outline-none">
                            @error('pac_municipio_ibge') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- SEÇÃO 3: CONTATOS E ENDEREÇO --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                            <span class="w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Contatos e Endereço</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Telefone Principal (WhatsApp / Celular)</label>
                                <input type="text" wire:model="pac_telefone_contato" placeholder="(00) 00000-0000"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                @error('pac_telefone_contato') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Telefone Secundário / Recado</label>
                                <input type="text" wire:model="pac_telefone_secundario" placeholder="(00) 00000-0000"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                                @error('pac_telefone_secundario') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Endereço Residencial Completo</label>
                            <textarea wire:model="pac_endereco" rows="2" placeholder="Rua, número, complemento e ponto de referência..."
                                class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-teal-600 focus:outline-none"></textarea>
                            @error('pac_endereco') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:w-1/2">
                            <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Bairro</label>
                            <input type="text" wire:model="pac_bairro" placeholder="Bairro"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:outline-none">
                            @error('pac_bairro') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="fecharModalPaciente"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 uppercase tracking-wider hover:bg-slate-50 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-6 py-2.5 text-xs font-bold text-white uppercase tracking-wider hover:bg-teal-700 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ $pacienteEditandoId ? 'Salvar Alterações' : 'Salvar Cadastro' }}
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

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
