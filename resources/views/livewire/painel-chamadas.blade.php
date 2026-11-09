<div wire:poll.4s="atualizarPainel"
     x-data="{
         audioLiberado: false,
         audioCtx: null,
         reproduzirChime(volume = 0.8) {
             try {
                 const ctx = this.getAudioContext();
                 if (!ctx) return;
                 const agora = ctx.currentTime;

                 // Tom 1: 587.33 Hz (Ré 5)
                 const osc1 = ctx.createOscillator();
                 const gain1 = ctx.createGain();
                 osc1.type = 'sine';
                 osc1.frequency.setValueAtTime(587.33, agora);
                 gain1.gain.setValueAtTime(0.001, agora);
                 gain1.gain.exponentialRampToValueAtTime(Math.max(0.01, volume * 0.7), agora + 0.05);
                 gain1.gain.exponentialRampToValueAtTime(0.0001, agora + 0.45);
                 osc1.connect(gain1);
                 gain1.connect(ctx.destination);
                 osc1.start(agora);
                 osc1.stop(agora + 0.45);

                 // Tom 2: 880.00 Hz (Lá 5)
                 const osc2 = ctx.createOscillator();
                 const gain2 = ctx.createGain();
                 osc2.type = 'sine';
                 osc2.frequency.setValueAtTime(880.00, agora + 0.25);
                 gain2.gain.setValueAtTime(0.001, agora + 0.25);
                 gain2.gain.exponentialRampToValueAtTime(Math.max(0.01, volume * 0.85), agora + 0.30);
                 gain2.gain.exponentialRampToValueAtTime(0.0001, agora + 0.95);
                 osc2.connect(gain2);
                 gain2.connect(ctx.destination);
                 osc2.start(agora + 0.25);
                 osc2.stop(agora + 0.95);
             } catch (e) {
                 console.warn('Erro ao reproduzir aviso sonoro:', e);
             }
         },
         falarTexto(texto, volume = 0.8) {
             if (!('speechSynthesis' in window)) return;
             try {
                 window.speechSynthesis.cancel();
                 const utter = new SpeechSynthesisUtterance(texto);
                 utter.lang = 'pt-BR';
                 utter.rate = 0.92;
                 utter.pitch = 1.0;
                 utter.volume = Math.max(0.1, Math.min(1.0, volume));

                 // Tenta selecionar voz em português
                 const vozes = window.speechSynthesis.getVoices();
                 const vozPt = vozes.find(v => v.lang.startsWith('pt-BR')) || vozes.find(v => v.lang.startsWith('pt'));
                 if (vozPt) {
                     utter.voice = vozPt;
                 }

                 window.speechSynthesis.speak(utter);
             } catch (e) {
                 console.warn('Erro na síntese de voz:', e);
             }
         },
         getAudioContext() {
             if (!this.audioCtx) {
                 const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                 if (AudioContextClass) {
                     this.audioCtx = new AudioContextClass();
                 }
             }
             if (this.audioCtx && this.audioCtx.state === 'suspended') {
                 this.audioCtx.resume();
             }
             return this.audioCtx;
         },
         ativarAudio() {
             this.audioLiberado = true;
             const ctx = this.getAudioContext();
             this.reproduzirChime(0.2);
         },
         alternarFullscreen() {
             if (!document.fullscreenElement) {
                 document.documentElement.requestFullscreen().catch(() => {});
             } else {
                 if (document.exitFullscreen) {
                     document.exitFullscreen();
                 }
             }
         },
         horaAtual: '',
         dataAtual: '',
         atualizarRelogio() {
             const agora = new Date();
             this.horaAtual = agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
             const opcoesData = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
             const dataStr = agora.toLocaleDateString('pt-BR', opcoesData);
             this.dataAtual = dataStr.charAt(0).toUpperCase() + dataStr.slice(1);
         }
     }"
     x-init="
         atualizarRelogio();
         setInterval(() => atualizarRelogio(), 1000);

         // Tenta desbloquear áudio se o navegador já tiver permissão
         document.addEventListener('click', () => { if (!audioLiberado) ativarAudio(); }, { once: true });

         // Ouve eventos de nova chamada emitidos pelo Livewire
         $wire.on('nova-chamada-painel', (evento) => {
             const dados = Array.isArray(evento) ? evento[0] : evento;
             if (!dados) return;

             // Reproduz o chime imediatamente
             reproduzirChime(dados.volume ?? 0.8);

             // Se a voz estiver habilitada nas configurações, fala após o término do som (850ms)
             if (dados.vozAtiva && dados.textoVoz) {
                 setTimeout(() => {
                     falarTexto(dados.textoVoz, dados.volume ?? 0.8);
                 }, 850);
             }
         });
     "
     class="min-h-screen flex flex-col justify-between p-4 sm:p-6 md:p-8 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100 select-none">

    <!-- Topo: Identificação da Unidade e Relógio Digital -->
    <header class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center gap-3.5">
            @if ($configuracao->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuracao->logo_path) }}"
                     alt="Logo do estabelecimento"
                     class="h-12 w-12 sm:h-14 sm:w-14 rounded-xl bg-white object-contain p-1 shadow-md">
            @else
                <span class="flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-xl bg-teal-500 text-slate-950 font-black text-xl shadow-lg">
                    CD
                </span>
            @endif

            <div>
                <h1 class="text-lg sm:text-2xl font-black tracking-tight text-white flex items-center gap-2">
                    <span>CER Digital</span>
                    <span class="hidden sm:inline-block text-xs font-mono font-medium px-2 py-0.5 rounded bg-teal-950/80 text-teal-300 border border-teal-800">
                        CNES {{ $configuracao->cnes ?? '2000001' }}
                    </span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400">
                    Centro Especializado em Reabilitação • Painel de Atendimento
                </p>
            </div>
        </div>

        <div class="text-right">
            <div class="font-mono text-2xl sm:text-4xl font-black text-teal-400 tracking-wider" x-text="horaAtual">
                --:--:--
            </div>
            <div class="text-xs sm:text-sm text-slate-400 font-medium" x-text="dataAtual">
                Carregando data...
            </div>
        </div>
    </header>

    @if ($autorizado)
        <!-- ============================================================= -->
        <!-- ESTADO 1: SMART TV AUTORIZADA — PAINEL DE CHAMADAS EM TEMPO REAL -->
        <!-- ============================================================= -->

        <!-- Botão de Desbloqueio de Áudio do Navegador (Aparece até o operador clicar) -->
        <div x-show="!audioLiberado"
             x-transition
             class="my-3 flex items-center justify-between bg-amber-500/20 border-2 border-amber-500/60 rounded-xl px-5 py-3 text-amber-200">
            <div class="flex items-center gap-3 text-sm sm:text-base font-semibold">
                <span class="text-2xl">🔊</span>
                <span>Atenção: Para ouvir o aviso sonoro e a chamada por voz nesta TV, clique no botão ao lado.</span>
            </div>
            <button type="button"
                    @click="ativarAudio()"
                    class="rounded-lg bg-amber-500 px-4 py-2 font-bold text-slate-950 hover:bg-amber-400 transition shadow-md cursor-pointer text-sm">
                Ativar Áudio da TV
            </button>
        </div>

        <!-- Área Principal de Conteúdo (Grid 16:9 Adaptado) -->
        <main class="my-auto py-4 sm:py-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            <!-- Card de Destaque da Chamada Atual (8 Colunas em telas grandes) -->
            <section class="lg:col-span-8 flex flex-col justify-center">
                @if($chamadaAtual)
                    <div class="relative overflow-hidden rounded-3xl border-2 border-teal-500/60 bg-gradient-to-b from-slate-900 to-slate-900/90 p-6 sm:p-10 shadow-2xl shadow-teal-950/50">
                        <!-- Faixa Superior com Badge de Tipo e Horário -->
                        <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs sm:text-sm font-bold tracking-wide uppercase shadow-xs
                                    {{ $chamadaAtual->tipo === 'REPETICAO' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-teal-500/20 text-teal-300 border border-teal-500/40 animate-pulse' }}">
                                    <span class="h-2 w-2 rounded-full {{ $chamadaAtual->tipo === 'REPETICAO' ? 'bg-amber-400' : 'bg-teal-400' }}"></span>
                                    {{ $chamadaAtual->tipo === 'REPETICAO' ? 'Chamada Repetida' : 'Chamada Atual' }}
                                </span>
                            </div>

                            <span class="font-mono text-sm sm:text-base text-slate-400 font-semibold">
                                Horário: {{ $chamadaAtual->chamado_em->format('H:i') }}
                            </span>
                        </div>

                        <!-- Nome do Paciente (Super Destaque Tipográfico) -->
                        <div class="mb-8">
                            <span class="text-xs sm:text-sm font-bold tracking-widest text-slate-400 uppercase block mb-1">
                                Paciente
                            </span>
                            <h2 class="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-white tracking-tight leading-tight drop-shadow-sm">
                                {{ $pacienteNomeAtual }}
                            </h2>
                        </div>

                        <!-- Consultório / Sala e Profissional -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 pt-4 border-t border-slate-800/80">
                            <!-- Sala de Destino -->
                            <div class="rounded-2xl bg-teal-950/40 border border-teal-600/40 p-4 sm:p-5">
                                <span class="text-xs font-bold tracking-wider text-teal-400 uppercase block mb-1">
                                    Dirigir-se ao Local
                                </span>
                                <span class="text-xl sm:text-3xl md:text-4xl font-extrabold text-teal-200 block">
                                    {{ $chamadaAtual->sala_nome }}
                                </span>
                            </div>

                            <!-- Profissional(is) -->
                            <div class="rounded-2xl bg-slate-800/40 border border-slate-700/50 p-4 sm:p-5">
                                <span class="text-xs font-bold tracking-wider text-slate-400 uppercase block mb-1">
                                    Profissional Responsável
                                </span>
                                <span class="text-lg sm:text-2xl font-bold text-slate-200 block truncate">
                                    {{ $chamadaAtual->profissional_nome ?? 'Equipe Multiprofissional' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Estado de Espera (Quando não há chamadas ativas) -->
                    <div class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-800 bg-slate-900/40 p-12 text-center h-full min-h-[380px]">
                        <div class="h-20 w-20 rounded-full bg-slate-800/70 flex items-center justify-center text-4xl mb-4 text-teal-400 shadow-inner">
                            🔔
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-200 mb-2">
                            Aguardando Próxima Chamada
                        </h2>
                        <p class="text-sm sm:text-base text-slate-400 max-w-md">
                            Os pacientes chamados pelos profissionais e pela recepção serão exibidos nesta tela em tempo real com anúncio sonoro.
                        </p>
                    </div>
                @endif
            </section>

            <!-- Histórico das Últimas Chamadas (4 Colunas) -->
            <section class="lg:col-span-4 flex flex-col justify-start">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6 h-full flex flex-col">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                        <h3 class="text-sm sm:text-base font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                            <span>📋 Últimas Chamadas</span>
                        </h3>
                        <span class="text-xs font-mono text-slate-500">Hoje</span>
                    </div>

                    <div class="space-y-3 overflow-hidden flex-1">
                        @forelse($historico as $item)
                            <div class="rounded-xl border border-slate-800/80 bg-slate-950/60 p-3.5 hover:border-slate-700 transition">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="font-bold text-sm sm:text-base text-slate-200 leading-tight">
                                        {{ $item['paciente_nome'] }}
                                    </span>
                                    <span class="font-mono text-xs text-teal-400 bg-teal-950/80 px-2 py-0.5 rounded border border-teal-800/80 shrink-0">
                                        {{ $item['hora'] }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                                    <span class="font-semibold text-teal-300">
                                        📍 {{ $item['sala_nome'] }}
                                    </span>
                                    @if($item['tipo'] === 'REPETICAO')
                                        <span class="text-[10px] text-amber-400 font-bold uppercase">Repetição</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 text-center text-slate-500 text-xs">
                                <span>Nenhuma chamada anterior registrada hoje.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>

        <!-- Rodapé: Status do Sistema e Botão Tela Cheia -->
        <footer class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800 pt-4 text-xs text-slate-500">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Dispositivo Autorizado: <strong>{{ $dispositivoNome }}</strong></span>
                </span>
                <span class="hidden md:inline text-slate-600">•</span>
                <span class="hidden md:inline">Atualização instantânea via live stream</span>
                <span class="hidden md:inline text-slate-600">•</span>
                <span x-text="audioLiberado ? '🔊 Áudio Habilitado' : '🔇 Áudio Silenciado'"></span>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        @click="alternarFullscreen()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/60 px-3 py-1.5 text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <span>Tela Cheia (F11)</span>
                </button>
            </div>
        </footer>

    @elseif ($revogado)
        <!-- ============================================================= -->
        <!-- ESTADO 2: SMART TV REVOGADA PELA COORDENAÇÃO                   -->
        <!-- ============================================================= -->
        <main class="my-auto py-12 flex flex-col items-center justify-center text-center max-w-2xl mx-auto">
            <div class="h-24 w-24 rounded-3xl bg-rose-500/10 border-2 border-rose-500/40 text-rose-400 flex items-center justify-center text-5xl mb-6 shadow-xl shadow-rose-950/40">
                🔒
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-950/80 text-rose-300 border border-rose-800 uppercase tracking-wider mb-3">
                Acesso Revogado
            </span>

            <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-4">
                Dispositivo Não Autorizado
            </h2>

            <p class="text-base sm:text-lg text-slate-300 mb-8 leading-relaxed">
                O acesso deste televisor (<span class="text-white font-bold">{{ $dispositivoNome ?? 'Smart TV' }}</span>) foi revogado pela Coordenação do CER Digital. Nenhum dado clínico ou chamada de paciente é exibido neste estado.
            </p>

            <button type="button"
                    wire:click="gerarNovoCodigo"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-3.5 text-base font-bold text-white shadow-lg hover:bg-teal-500 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Conectar Novamente com Novo Código</span>
            </button>
        </main>

        <footer class="flex items-center justify-between border-t border-slate-800 pt-4 text-xs text-slate-500">
            <span>CER Digital • Proteção e Privacidade LGPD</span>
            <button type="button" @click="alternarFullscreen()" class="hover:text-slate-300 transition">
                Tela Cheia (F11)
            </button>
        </footer>

    @else
        <!-- ============================================================= -->
        <!-- ESTADO 3: TELA DE PAREAMENTO / ATIVAÇÃO POR CÓDIGO (16:9)       -->
        <!-- ============================================================= -->
        <main class="my-auto py-8 sm:py-12 flex flex-col items-center justify-center text-center max-w-4xl mx-auto w-full">
            <!-- Badge de Ativação -->
            <div class="inline-flex items-center gap-2 rounded-full bg-teal-950/80 border border-teal-700/60 px-4 py-1.5 text-xs sm:text-sm font-semibold text-teal-300 mb-4 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-teal-400 animate-pulse"></span>
                <span>Pareamento Seguro de Dispositivo • CER Digital</span>
            </div>

            <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-2">
                Conectar este Televisor
            </h2>
            <p class="text-sm sm:text-base text-slate-400 max-w-xl mb-8">
                Para exibir o painel de chamadas nesta Smart TV sem necessidade de login ou senha, informe o código de ativação abaixo no computador da Coordenação.
            </p>

            <!-- Card Central com o Código de Ativação em Super Destaque -->
            <div class="w-full max-w-xl rounded-3xl border-2 border-teal-500/50 bg-gradient-to-b from-slate-900 to-slate-900/90 p-8 sm:p-10 shadow-2xl shadow-teal-950/60 mb-8">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400 block mb-2">
                    Código de Ativação do Dispositivo
                </span>

                @if ($codigoExpirado)
                    <div class="py-4">
                        <span class="text-3xl sm:text-4xl font-mono font-black text-rose-400 tracking-wider block mb-3">
                            EXPIRADO
                        </span>
                        <p class="text-sm text-slate-400 mb-5">
                            O código anterior expirou após 10 minutos de inatividade por motivos de segurança.
                        </p>
                        <button type="button"
                                wire:click="gerarNovoCodigo"
                                class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-3 text-sm font-bold text-white shadow-md hover:bg-teal-500 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Gerar Novo Código</span>
                        </button>
                    </div>
                @else
                    <div class="py-2">
                        <!-- Código em super destaque tipográfico de alta legibilidade -->
                        <div class="inline-block bg-slate-950/80 rounded-2xl border-2 border-teal-400/40 px-8 py-5 shadow-inner my-2">
                            <span class="font-mono text-5xl sm:text-7xl font-black text-teal-300 tracking-widest select-all">
                                {{ $codigoAtivacao }}
                            </span>
                        </div>

                        <div class="flex items-center justify-center gap-3 text-xs sm:text-sm text-slate-400 mt-4">
                            <span class="flex items-center gap-1.5 text-teal-400 font-medium">
                                <span class="h-2 w-2 rounded-full bg-teal-400 animate-ping"></span>
                                <span>Aguardando aprovação no sistema...</span>
                            </span>
                            <span>•</span>
                            <span>Válido até {{ $expiraEmFormatado }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Instruções Claras Passo a Passo -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left max-w-3xl w-full">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-900/60 text-teal-300 font-bold text-xs mb-2">1</span>
                    <h3 class="text-sm font-bold text-white mb-1">Acesse as Configurações</h3>
                    <p class="text-xs text-slate-400">Entre no sistema pelo computador e vá em <strong>Configurações &gt; Painel de Chamadas</strong>.</p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-900/60 text-teal-300 font-bold text-xs mb-2">2</span>
                    <h3 class="text-sm font-bold text-white mb-1">Autorizar Nova TV</h3>
                    <p class="text-xs text-slate-400">Na seção de <strong>Dispositivos Autorizados</strong>, clique no botão para adicionar uma nova TV.</p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-900/60 text-teal-300 font-bold text-xs mb-2">3</span>
                    <h3 class="text-sm font-bold text-white mb-1">Digite o Código</h3>
                    <p class="text-xs text-slate-400">Digite o código exibido nesta tela. A TV será liberada automaticamente na hora!</p>
                </div>
            </div>
        </main>

        <!-- Rodapé do Pareamento -->
        <footer class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800 pt-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span>🔒 Modo Seguro: Nenhum dado de paciente é transmitido antes da autorização.</span>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        wire:click="gerarNovoCodigo"
                        class="text-teal-400 hover:text-teal-300 transition text-xs font-semibold cursor-pointer">
                    Atualizar Código
                </button>
                <span>•</span>
                <button type="button"
                        @click="alternarFullscreen()"
                        class="inline-flex items-center gap-1.5 text-slate-400 hover:text-white transition cursor-pointer">
                    <span>Tela Cheia (F11)</span>
                </button>
            </div>
        </footer>
    @endif
</div>
