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
                Guia prático para o dia a dia da unidade e histórico de todas as melhorias feitas no sistema
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
                Como Usar o Sistema
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
        <!-- Guia Prático por Setor em Linguagem Simples -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">1. Recepção e Agenda da Semana</h3>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Recepção e Coordenação</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Organiza os horários de atendimento de 30 em 30 minutos para cada sala e permite confirmar rapidamente quando o paciente chega na unidade.
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>Sem choque de horário:</strong> O sistema avisa se a sala ou o paciente já estiverem ocupados naquele horário (a menos que seja um atendimento em conjunto com dois profissionais).</li>
                    <li><strong>Cartão SUS conferido na hora:</strong> Ao cadastrar um paciente, o sistema confere se os 15 números do Cartão SUS são válidos.</li>
                    <li><strong>Aviso de Faltas:</strong> Se o paciente faltar 3 vezes seguidas ou 5 vezes no mesmo mês, aparece um alerta vermelho para a recepção avisar a coordenação.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">2. Prontuário do Paciente</h3>
                    <span class="rounded bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700">Terapeutas e Coordenação</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Reúne em um só lugar o histórico de evoluções de Fisioterapia, Fonoaudiologia, Terapia Ocupacional, Psicologia e demais áreas, com sigilo protegido.
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>Assinatura com PIN:</strong> Para finalizar uma evolução, o profissional digita sua senha curta (PIN), garantindo que só ele pôde assinar aquele atendimento.</li>
                    <li><strong>Proteção após 24 horas:</strong> Depois de 24 horas, o texto assinado não pode mais ser apagado. Se precisar corrigir ou acrescentar algo, basta clicar em <strong>Adicionar Adendo (Observação Complementar)</strong>.</li>
                    <li><strong>Atendimento em Conjunto:</strong> Quando dois profissionais atendem juntos, cada um assina sua parte normalmente.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">3. Faturamento Mensal do SUS (BPA-i)</h3>
                    <span class="rounded bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">Faturamento e Coordenação</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Confere todos os atendimentos realizados no mês antes de gerar o arquivo final para prestação de contas ao SUS.
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>Conferência antes de exportar:</strong> O sistema separa automaticamente o que está pronto para enviar e avisa se algum atendimento tiver dados incompletos.</li>
                    <li><strong>Arquivo pronto para o SUS:</strong> Com um clique, baixa o arquivo de texto do mês no formato exigido pelo Ministério da Saúde.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">4. Configurações e Meu Perfil</h3>
                    <span class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800">Todos os Colaboradores</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    No menu lateral em <strong>Configurações</strong>, você encontra os cadastros da unidade de acordo com a sua função.
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>Meu Perfil & PIN:</strong> Qualquer colaborador pode trocar sua senha de acesso ou escolher um novo PIN de assinatura.</li>
                    <li><strong>Cadastros da Unidade:</strong> A Recepção e a Coordenação podem cadastrar e atualizar Pacientes, Salas, Profissionais e Procedimentos.</li>
                </ul>
            </div>
        </div>
    @endif
</div>
