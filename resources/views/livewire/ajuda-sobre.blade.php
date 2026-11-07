<div class="space-y-6">
    <!-- Cabeçalho do Módulo Ajuda & Sobre -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-lg font-bold text-slate-900">Central de Ajuda & Sobre o CER Flow</h2>
                <span class="rounded-md bg-teal-50 px-2.5 py-0.5 text-xs font-mono font-bold text-teal-700 border border-teal-200">
                    v{{ $versaoAtual }}
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Documentação operacional SUS, regras de negócio do CER e linha do tempo de evolução das versões
            </p>
        </div>

        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-xs">
            <button type="button"
                    wire:click="selecionarAba('evolucao')"
                    class="rounded-md px-3.5 py-1.5 font-semibold transition {{ $abaAtiva === 'evolucao' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Evolução das Versões (Changelog)
            </button>
            <button type="button"
                    wire:click="selecionarAba('ajuda')"
                    class="rounded-md px-3.5 py-1.5 font-semibold transition {{ $abaAtiva === 'ajuda' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                Manual Operacional & Regras SUS
            </button>
        </div>
    </div>

    <!-- Ficha Técnica do Ambiente -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Versão Ativa</span>
            <span class="mt-1 font-mono text-sm font-bold text-teal-700 block">v{{ $versaoAtual }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Framework</span>
            <span class="mt-1 font-mono text-sm font-semibold text-slate-800 block">Laravel {{ $infoAmbiente['laravel'] }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Runtime PHP</span>
            <span class="mt-1 font-mono text-sm font-semibold text-slate-800 block">PHP {{ $infoAmbiente['php'] }}</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">Fuso Horário / Locale</span>
            <span class="mt-1 font-mono text-sm font-semibold text-slate-800 block">{{ $infoAmbiente['timezone'] }} ({{ $infoAmbiente['locale'] }})</span>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[11px] text-slate-400 block">CNES Unidade</span>
            <span class="mt-1 font-mono text-sm font-semibold text-slate-800 block">{{ $infoAmbiente['cnes'] }}</span>
        </div>
    </div>

    @if($abaAtiva === 'evolucao')
        <!-- Linha do Tempo de Evolução das Versões -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
            <h3 class="text-sm font-bold text-slate-900 mb-6">Histórico de Lançamentos e Evolução de Versões (SemVer)</h3>

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
                            <span class="text-xs text-slate-400 font-mono">
                                Lançado em {{ $item['data'] }}
                            </span>
                            @if($indice === 0)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                                    Versão Atual em Execução
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
        <!-- Manual Operacional por Perfil e Regras de Domínio SUS -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">1. Recepção & Grade Semanal</h3>
                    <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[10px] text-slate-600">recepcao / coordenacao</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Gerencia a alocação de usuários em slots de 30 minutos por sala e realiza o check-in diário em 1 clique (<code class="font-mono">PRESENTE</code>, <code class="font-mono">FALTA_INJUSTIFICADA</code>, <code class="font-mono">FALTA_JUSTIFICADA</code>).
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>RN01 (Anti-Conflito):</strong> Bloqueia agendamentos duplicados de sala ou paciente, exceto quando marcado como <code class="font-mono">COMPARTILHADO</code>.</li>
                    <li><strong>RN02 (Validação Cadastral):</strong> Exige Cartão Nacional de Saúde (CNS) válido de 15 dígitos (Módulo 11) e data de nascimento.</li>
                    <li><strong>RF2.5 (Absenteísmo):</strong> Sinaliza automaticamente pacientes com 3 faltas consecutivas ou 5 alternadas no mês vigente.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">2. Prontuário Eletrônico (PEP Multi)</h3>
                    <span class="rounded bg-teal-50 px-2 py-0.5 font-mono text-[10px] text-teal-700">terapeuta / coordenacao</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Linha do tempo clínica integrada entre Fisioterapia, Fonoaudiologia, Terapia Ocupacional e Psicologia, com isolamento estrito de sigilo (LGPD / CFM).
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>RF3.4 (Assinatura SHA-256):</strong> Cada evolução exige confirmação do PIN individual do profissional, gravando hash criptográfico de 64 caracteres.</li>
                    <li><strong>RN03 (Trava Legal de 24h):</strong> Passadas 24 horas da assinatura, a evolução torna-se imutável, admitindo apenas <strong>Termos de Adendo</strong> retificadores.</li>
                    <li><strong>RN04 (Co-Atendimento):</strong> Atendimentos compartilhados desmembram a produção individualmente por profissional, CBO e procedimento SIGTAP.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">3. Faturamento & Exportação BPA-i (SIA/SUS)</h3>
                    <span class="rounded bg-indigo-50 px-2 py-0.5 font-mono text-[10px] text-indigo-700">faturamento / coordenacao</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Audita preventivamente os atendimentos assinados na competência (<code class="font-mono">AAAAMM</code>) antes de gerar a remessa magnética oficial.
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>RF4.2 (Pré-Auditoria em Memória):</strong> Retém registros com CNS inválido, CBO incompatível com o procedimento SIGTAP de 10 dígitos ou idade fora da faixa.</li>
                    <li><strong>RF4.3 (Layout Posicional DATASUS):</strong> Gera o arquivo <code class="font-mono">BPA_I_AAAAMM.txt</code> com Header <code class="font-mono">01#BPA#</code> (130 colunas) e linhas Tipo <code class="font-mono">03</code> (350 colunas).</li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900">4. Segurança, LGPD e Trilha de Auditoria</h3>
                    <span class="rounded bg-amber-50 px-2 py-0.5 font-mono text-[10px] text-amber-800">coordenacao</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Todas as operações sensíveis são rastreadas de forma imutável na tabela <code class="font-mono">logs_auditoria</code>.
                </p>
                <ul class="space-y-1.5 text-slate-700">
                    <li><strong>RNF1 (Isolamento de Perfis):</strong> Recepção e Faturamento são bloqueados por Policy de visualizar anotações clínicas do PEP.</li>
                    <li><strong>RNF2 (Rastreabilidade):</strong> Registra automaticamente eventos <code class="font-mono">ACESSO_PEP</code>, <code class="font-mono">ASSINATURA_EVOLUCAO</code> e <code class="font-mono">EXPORTACAO_BPA</code> com IP e usuário.</li>
                </ul>
            </div>
        </div>
    @endif
</div>

