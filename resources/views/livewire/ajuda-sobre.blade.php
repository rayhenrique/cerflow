<div class="space-y-6">
    <!-- Cabeçalho do Módulo Ajuda & Sobre -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-lg font-bold text-slate-900">Central de Ajuda & Sobre o CER Digital</h2>
                <span class="rounded-md bg-teal-50 px-2.5 py-0.5 text-xs font-mono font-bold text-teal-700 border border-teal-200">
                    v{{ $versaoAtual }}
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Manual completo e didático para todos os colaboradores, orientações por setor e histórico de evolução do sistema
            </p>
        </div>

        <div class="grid grid-cols-2 sm:inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-xs w-full sm:w-auto">
            <button type="button"
                    wire:click="selecionarAba('evolucao')"
                    class="rounded-md px-3 py-1.5 font-semibold text-center transition cursor-pointer {{ $abaAtiva === 'evolucao' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Novidades de Cada Versão
            </button>
            <button type="button"
                    wire:click="selecionarAba('ajuda')"
                    class="rounded-md px-3 py-1.5 font-semibold text-center transition cursor-pointer {{ $abaAtiva === 'ajuda' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Manual do Usuário
            </button>
        </div>
    </div>

    <!-- Resumo Rápido da Unidade -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Versão Atual do Sistema</span>
            <span class="mt-1 font-mono text-sm font-bold text-teal-700 block">v{{ $versaoAtual }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Código CNES da Unidade</span>
            <span class="mt-1 font-mono text-sm font-semibold text-slate-800 block">{{ $infoAmbiente['cnes'] }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Horário do Sistema</span>
            <span class="mt-1 text-sm font-semibold text-slate-800 block">Horário de Maceió / Brasília</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Desenvolvido por</span>
            <span class="mt-1 text-sm font-semibold text-slate-800 block">KL Tecnologia</span>
        </div>
    </div>

    @if($abaAtiva === 'evolucao')
        <!-- Linha do Tempo de Evolução das Versões -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
            <h3 class="text-sm font-bold text-slate-900 mb-6">O que mudou em cada atualização do sistema</h3>

            <div class="relative border-l-2 border-teal-500 ml-3 space-y-8 pl-6">
                @foreach($historicoVersoes as $indice => $item)
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 flex h-4 w-4 items-center justify-center rounded-full {{ $indice === 0 ? 'bg-teal-600 ring-4 ring-teal-100' : 'bg-slate-400' }}"></span>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="rounded-md {{ $indice === 0 ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-800' }} px-2.5 py-0.5 font-mono text-xs font-bold">
                                v{{ $item['versao'] }}
                            </span>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                {{ $item['tipo'] }}
                            </span>
                            <span class="text-xs text-slate-400">
                                Atualizado em {{ $item['data'] }}
                            </span>
                            @if($indice === 0)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                                    Versão que você está usando agora
                                </span>
                            @endif
                        </div>

                        <h4 class="mt-2 text-sm font-bold text-slate-900">{{ $item['titulo'] }}</h4>

                        <ul class="mt-3 space-y-2 text-xs text-slate-600">
                            @foreach($item['destaques'] as $destaque)
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600 font-bold">•</span>
                                    <span>{{ $destaque }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <!-- MANUAL COMPLETO E DIDÁTICO DO USUÁRIO -->
        <div class="space-y-6">
            <!-- Barra Superior do Manual: Ações e Filtro de Módulos -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📖 Manual Oficial do Usuário — CER Digital</span>
                            <span class="text-xs font-normal text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">Guia Didático</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Selecione o setor abaixo para consultar os procedimentos passo a passo, normas do SUS e dicas práticas.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                wire:click="baixarManual"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition shadow-xs cursor-pointer"
                                title="Baixar arquivo do manual em formato Markdown (.md)">
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Baixar Manual (.md)</span>
                        </button>

                        <button type="button"
                                onclick="window.print()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-700 transition shadow-xs cursor-pointer"
                                title="Imprimir este manual ou salvar em PDF">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Imprimir / Salvar PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Chips / Filtro de Capítulos do Manual -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button"
                            wire:click="selecionarSecaoManual('todos')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'todos' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        🌟 Todos os Capítulos
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('acesso')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'acesso' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        🔑 Acesso & PIN
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('recepcao')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'recepcao' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        🏢 Recepção & Agendamento
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('terapeutas')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'terapeutas' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        🩺 Terapeutas & Prontuário (PEP)
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('faturamento')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'faturamento' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        💰 Faturamento SUS (BPA-i)
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('coordenacao')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'coordenacao' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        ⚙️ Coordenação & Gestão
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('checklists')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'checklists' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        📋 Checklists Diários
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('faq')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'faq' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        ❓ Dúvidas Frequentes (FAQ)
                    </button>
                    <button type="button"
                            wire:click="selecionarSecaoManual('glossario')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer {{ $secaoManual === 'glossario' ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        📖 Glossário SUS
                    </button>
                </div>
            </div>

            <!-- CAPÍTULO 1: ACESSO, LOGIN & PIN DE ASSINATURA -->
            @if(in_array($secaoManual, ['todos', 'acesso']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">1</span>
                            <h3 class="text-sm font-bold text-slate-900">Primeiros Passos, Login e PIN de Assinatura</h3>
                        </div>
                        <span class="rounded bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700">Todos os Colaboradores</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600">
                        <div class="space-y-3 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                                <span>🔑 Login e Troca de Senha</span>
                            </h4>
                            <p class="leading-relaxed">
                                Acesse o sistema pelo navegador (Chrome, Edge ou Firefox). Digite seu e-mail institucional e a senha fornecida.
                            </p>
                            <ul class="space-y-1.5 list-disc list-inside text-slate-700">
                                <li><strong>Para alterar sua senha:</strong> Vá em <em>Configurações → Minha Conta & PIN</em>.</li>
                                <li>Informe a senha atual, digite a nova com no mínimo 8 caracteres e confirme.</li>
                                <li>Se esquecer sua senha, solicite a redefinição à Coordenação da unidade.</li>
                            </ul>
                        </div>

                        <div class="space-y-3 bg-teal-50/50 p-4 rounded-lg border border-teal-100">
                            <h4 class="font-bold text-teal-900 flex items-center gap-1.5">
                                <span>🔒 O que é o PIN Eletrônico de Assinatura?</span>
                            </h4>
                            <p class="leading-relaxed text-teal-800">
                                É uma senha numérica de 4 a 6 dígitos exclusiva para assinar suas evoluções clínicas no Prontuário Eletrônico (PEP).
                            </p>
                            <ul class="space-y-1.5 list-disc list-inside text-teal-900">
                                <li><strong>Substitui a assinatura física e o carimbo:</strong> Gera um Hash criptográfico SHA-256 e tem validade jurídica perante o SUS e Conselhos.</li>
                                <li><strong>Uso Pessoal e Intransferível:</strong> Nunca passe seu PIN para outra pessoa.</li>
                                <li><strong>Cadastrar/Alterar:</strong> Em <em>Configurações → Minha Conta & PIN</em>.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 2: RECEPÇÃO E AGENDAMENTO -->
            @if(in_array($secaoManual, ['todos', 'recepcao']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">2</span>
                            <h3 class="text-sm font-bold text-slate-900">Recepção, Grade Semanal e Agendamentos</h3>
                        </div>
                        <span class="rounded bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700">Recepção e Coordenação</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">📅 Grade Semanal em Blocos</h4>
                            <p class="text-slate-600 leading-relaxed">
                                A grade é estruturada em blocos de 30 minutos (08h às 12h e 13h às 17h) por sala e terapeuta.
                            </p>
                            <p class="text-slate-700 font-medium">
                                <strong>Anti-choque de horário:</strong> O sistema impede marcar o mesmo paciente ou a mesma sala em duplicidade no mesmo horário.
                            </p>
                        </div>

                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">🔍 Novo Agendamento Ágil</h4>
                            <p class="text-slate-600 leading-relaxed">
                                O modal abre instantaneamente sem carregar pacientes antecipadamente.
                            </p>
                            <p class="text-slate-700">
                                <strong>Como buscar:</strong> Digite pelo menos 3 letras do Nome, ou CPF (com ou sem pontuação), ou os 15 dígitos do Cartão SUS. O sistema traz até 20 resultados em tempo real.
                            </p>
                        </div>

                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">👥 Atendimento Compartilhado</h4>
                            <p class="text-slate-600 leading-relaxed">
                                Quando dois terapeutas atendem a mesma criança juntos (ex: Fisioterapeuta e TO na sala sensorial).
                            </p>
                            <p class="text-slate-700">
                                Marque o segundo profissional no agendamento. O sistema divide a produção BPA-i automaticamente sem conflito!
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-amber-50 p-4 border border-amber-200 text-xs text-amber-900 space-y-1.5">
                        <div class="font-bold flex items-center gap-1.5">
                            <span>⚠️ Controle de Faltas e Alerta de Absenteísmo</span>
                        </div>
                        <p class="leading-relaxed">
                            Ao registrar a chegada do paciente, use o botão <strong>Presente</strong>. Caso o paciente falte, registre se a falta foi <strong>Justificada</strong> ou <strong>Injustificada</strong>.
                            Se o paciente acumular <strong>3 faltas seguidas ou 5 no mês</strong>, o sistema emite uma tarja vermelha de alerta para contato social imediato antes de perder a vaga.
                        </p>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 3: TERAPEUTAS E PRONTUÁRIO (PEP) -->
            @if(in_array($secaoManual, ['todos', 'terapeutas']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">3</span>
                            <h3 class="text-sm font-bold text-slate-900">Terapeutas e Prontuário Multiprofissional (PEP)</h3>
                        </div>
                        <span class="rounded bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700">Equipe Multiprofissional & Coordenação</span>
                    </div>

                    <div class="space-y-3 text-xs text-slate-600">
                        <p class="leading-relaxed">
                            O Prontuário Eletrônico do CER Digital unifica todas as especialidades (Fisioterapia, Fonoaudiologia, Terapia Ocupacional, Psicologia, etc.) em uma linha do tempo única, garantindo visão holística e proteção de sigilo.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 space-y-2">
                                <h4 class="font-bold text-slate-900">📝 Registro da Evolução Clínica</h4>
                                <ul class="space-y-1.5 text-slate-700">
                                    <li><strong>1. Localize o Paciente:</strong> Digite 3 letras do nome, CPF ou Cartão SUS no campo de pesquisa rápida.</li>
                                    <li><strong>2. Nova Evolução:</strong> Selecione seu CBO e o Procedimento SIGTAP executado.</li>
                                    <li><strong>3. Relato Estruturado:</strong> Preencha Queixa/Estado Atual, Conduta Realizada e Intercorrências.</li>
                                    <li><strong>4. Assinatura:</strong> Digite seu PIN de 4 a 6 dígitos para finalizar e gerar a produção BPA-i do SUS.</li>
                                </ul>
                            </div>

                            <div class="bg-teal-50/60 p-4 rounded-lg border border-teal-100 space-y-2 text-teal-900">
                                <h4 class="font-bold flex items-center gap-1.5">
                                    <span>⏱️ A Regra das 24 Horas & Termos de Adendo</span>
                                </h4>
                                <p class="leading-relaxed">
                                    <strong>Edição em até 24 horas:</strong> Você pode corrigir erros de digitação na sua evolução durante as primeiras 24 horas após a assinatura.
                                </p>
                                <p class="leading-relaxed font-semibold">
                                    <strong>Imutabilidade após 24 horas:</strong> Passadas 24h, o texto é travado definitivamente por lei. Para acrescentar dados, clique em <em>Adicionar Termo de Adendo (Observação Retificadora)</em> e assine com seu PIN.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 4: FATURAMENTO SUS E BPA-I -->
            @if(in_array($secaoManual, ['todos', 'faturamento']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">4</span>
                            <h3 class="text-sm font-bold text-slate-900">Faturamento SUS & Lote Magnético BPA-i (SIA/SUS)</h3>
                        </div>
                        <span class="rounded bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">Setor de Faturamento e Coordenação</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">⚙️ Geração Automática</h4>
                            <p class="text-slate-600 leading-relaxed">
                                Cada evolução assinada com PIN no PEP alimenta diretamente a tabela de produção ambulatorial do mês, associando CBO, Procedimento SIGTAP, CID-10 e Cartão SUS.
                            </p>
                        </div>

                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">🛡️ Validação Preventiva SIGTAP</h4>
                            <p class="text-slate-600 leading-relaxed">
                                O sistema confere se o CBO é autorizado, se a idade do paciente é compatível com o procedimento, se o Cartão SUS tem 15 dígitos válidos e se o CID é permitido pelo Ministério da Saúde.
                            </p>
                        </div>

                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">💾 Arquivo Datasus Pronto (.txt)</h4>
                            <p class="text-slate-600 leading-relaxed">
                                No fechamento da competência, clique em <strong>Exportar Arquivo Magnético BPA-i</strong> para baixar o arquivo texto oficial formatado com o Header #BPA# e linhas 02 para importação direta no SIA/SUS.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 5: COORDENAÇÃO, CADASTROS E E-SUS PEC -->
            @if(in_array($secaoManual, ['todos', 'coordenacao']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">5</span>
                            <h3 class="text-sm font-bold text-slate-900">Coordenação, Gestão de Cadastros e Integração e-SUS PEC</h3>
                        </div>
                        <span class="rounded bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700">Coordenação Geral & TI</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600">
                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">👥 Pacientes e Paginação de Alta Velocidade</h4>
                            <p class="leading-relaxed">
                                Em <em>Configurações → Pacientes</em>, visualize milhares de cadastros com paginação configurável (15, 25, 50 ou 100 pacientes por página) e filtros por Modalidade (Física, Intelectual, Visual e Auditiva).
                            </p>
                            <p class="leading-relaxed">
                                É possível cadastrar novos pacientes, atualizar contatos, CID-10 e dados de residência (Código IBGE).
                            </p>
                        </div>

                        <div class="space-y-2 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-900">🌐 Sincronização com o e-SUS APS PEC</h4>
                            <p class="leading-relaxed">
                                O sistema conecta-se ao banco de dados PostgreSQL do e-SUS PEC municipal para importar cidadãos cadastrados na Atenção Primária e vincular automaticamente a UBS de referência e o Agente Comunitário de Saúde (ACS).
                            </p>
                            <p class="leading-relaxed text-slate-700 font-medium">
                                Pode ser executado em <em>Configurações → Integração e-SUS PEC</em> ou agendado no servidor via comando <code>php artisan esus:sincronizar-pec</code>.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 6: CHECKLISTS DIÁRIOS -->
            @if(in_array($secaoManual, ['todos', 'checklists']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">6</span>
                            <h3 class="text-sm font-bold text-slate-900">Checklists Diários Operacionais por Função</h3>
                        </div>
                        <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800">Rotina da Unidade</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">🏢 Recepção</h4>
                            <ul class="space-y-1 text-slate-600">
                                <li>✓ Conferir a grade do dia às 07:45.</li>
                                <li>✓ Marcar <strong>Presente</strong> assim que o paciente chegar.</li>
                                <li>✓ Cadastrar pacientes conferindo 15 dígitos do Cartão SUS.</li>
                                <li>✓ Lançar faltas justificadas/injustificadas.</li>
                                <li>✓ Avisar Serviço Social sobre alertas de 3 faltas.</li>
                            </ul>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">🩺 Terapeutas</h4>
                            <ul class="space-y-1 text-slate-600">
                                <li>✓ Consultar cronograma na Grade Semanal.</li>
                                <li>✓ Ler anotações multiprofissionais no PEP.</li>
                                <li>✓ Registrar a evolução clínica após a sessão.</li>
                                <li>✓ Assinar com o PIN eletrônico de 4 a 6 dígitos.</li>
                                <li>✓ Usar Termo de Adendo caso precise corrigir algo após 24h.</li>
                            </ul>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">💰 Faturamento</h4>
                            <ul class="space-y-1 text-slate-600">
                                <li>✓ Acompanhar volume de produção semanalmente.</li>
                                <li>✓ Sanar críticas de CBO, idade e Cartão SUS.</li>
                                <li>✓ Confirmar 100% dos registros prontos no fechamento.</li>
                                <li>✓ Baixar arquivo magnético .txt BPA-i.</li>
                                <li>✓ Importar no BPA Magnético Datasus e arquivar recibo.</li>
                            </ul>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">⚙️ Coordenação</h4>
                            <ul class="space-y-1 text-slate-600">
                                <li>✓ Monitorar taxa de ocupação no Dashboard.</li>
                                <li>✓ Gerenciar salas, espaços e terapeutas.</li>
                                <li>✓ Rodar sincronização e-SUS PEC periodicamente.</li>
                                <li>✓ Verificar trilha de auditoria LGPD.</li>
                                <li>✓ Zelar pela segurança e sigilo de dados.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 7: PERGUNTAS FREQUENTES (FAQ) -->
            @if(in_array($secaoManual, ['todos', 'faq']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">7</span>
                            <h3 class="text-sm font-bold text-slate-900">Perguntas Frequentes (FAQ) & Resolução Rápida</h3>
                        </div>
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Dúvidas Comuns</span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                            <h4 class="font-bold text-slate-900">1. Esqueci meu PIN de assinatura. Como proceder?</h4>
                            <p class="text-slate-600 leading-relaxed">
                                Vá em <strong>Configurações → Minha Conta & PIN</strong>. No bloco do PIN, digite um novo PIN numérico, informe sua senha de login atual e salve. Ele passa a valer imediatamente.
                            </p>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                            <h4 class="font-bold text-slate-900">2. Posso corrigir uma evolução depois de 24 horas?</h4>
                            <p class="text-slate-600 leading-relaxed">
                                Não diretamente, pois a lei proíbe apagar ou sobrescrever prontuários já assinados. Você deve clicar no botão <strong>Adicionar Termo de Adendo</strong> na evolução original, registrar a nota retificadora e assinar com seu PIN.
                            </p>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                            <h4 class="font-bold text-slate-900">3. Como funciona a busca de pacientes sem lentidão?</h4>
                            <p class="text-slate-600 leading-relaxed">
                                O sistema não baixa a tabela de 38 mil pacientes de uma vez. Basta você digitar <strong>pelo menos 3 caracteres</strong> (do Nome, do CPF com ou sem pontuação ou do Cartão SUS) para o sistema consultar diretamente o banco e trazer até 20 registros instantaneamente.
                            </p>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                            <h4 class="font-bold text-slate-900">4. Dois terapeutas podem atender na mesma sala ao mesmo tempo?</h4>
                            <p class="text-slate-600 leading-relaxed">
                                Sim! Ao agendar, basta adicionar o segundo terapeuta como <strong>Co-atendimento</strong>. O sistema não acusa conflito de agenda e desmembra a produção BPA-i para ambos os profissionais.
                            </p>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                            <h4 class="font-bold text-slate-900">5. Como imprimir o prontuário para uma junta médica ou determinação judicial?</h4>
                            <p class="text-slate-600 leading-relaxed">
                                Abra o prontuário do paciente no PEP e use a função de impressão do navegador (ou <em>Ctrl + P</em>). O layout adapta-se automaticamente para impressão em folha A4 limpa com cabeçalho oficial e carimbos de validação.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CAPÍTULO 8: GLOSSÁRIO SUS -->
            @if(in_array($secaoManual, ['todos', 'glossario']))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 font-bold text-xs">8</span>
                            <h3 class="text-sm font-bold text-slate-900">Glossário de Termos do SUS e do CER Digital</h3>
                        </div>
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Dicionário Prático</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-900 block">BPA-i (Boletim de Produção Ambulatorial Individualizado)</span>
                            <span class="text-slate-600 text-[11px] mt-0.5 block">Instrumento magnético oficial do SUS onde cada atendimento em reabilitação é registrado com CNS do paciente e do terapeuta.</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-900 block">CBO (Classificação Brasileira de Ocupações)</span>
                            <span class="text-slate-600 text-[11px] mt-0.5 block">Código numérico do Ministério do Trabalho e Saúde que identifica a profissão (ex: Fisioterapeuta, Fonoaudiólogo, Psicólogo).</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-900 block">SIGTAP (Tabela de Procedimentos do SUS)</span>
                            <span class="text-slate-600 text-[11px] mt-0.5 block">Catálogo oficial de procedimentos, valores e regras de compatibilidade do Ministério da Saúde.</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-900 block">CNS (Cartão Nacional de Saúde)</span>
                            <span class="text-slate-600 text-[11px] mt-0.5 block">Número único de 15 dígitos com validação matemática do Ministério da Saúde, obrigatório para faturamento ambulatorial.</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-900 block">Hash SHA-256</span>
                            <span class="text-slate-600 text-[11px] mt-0.5 block">Assinatura matemática criptográfica inviolável gravada na evolução para comprovar que o prontuário não foi alterado.</span>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-900 block">Horário Cativo</span>
                            <span class="text-slate-600 text-[11px] mt-0.5 block">Vaga semanal fixa reservada na grade para pacientes que realizam tratamento contínuo de reabilitação.</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
