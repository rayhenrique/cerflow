<div wire:poll.4s="atualizarPainel"
     x-data="{
         // --- ESTADOS DE CONTROLE DE ÁUDIO ---
         audioLiberado: false,
         audioCtx: null,
         chimeSuportado: false,
         sinalSonoroFuncionando: false,

         // --- SÍNTESE DE VOZ ---
         vozSuportada: false,
         vozesCarregadas: false,
         totalVozes: 0,
         vozPtDisponivel: false,
         vozSelecionadaNome: '',
         vozSelecionadaLang: '',
         estadoVoz: 'inicializando', // 'inicializando' | 'aguardando_ativacao' | 'pronto' | 'falando' | 'erro' | 'nao_suportado'
         sinteseFuncionando: false,
         ultimoErroVoz: null,
         ultimoErroCodigo: '',
         ultimoEventoVoz: 'Aguardando inicialização',
         testeStatus: '', // '' | 'testando' | 'sucesso' | 'erro' | 'sem_suporte' | 'aguardando'
         testeMensagem: '',
         toastTimeoutId: null,

         // --- STATUS E SUPORTE TÉCNICO SMART TV ---
         modalDiagnosticoAberto: false,
         deviceInfo: {
             fabricante: 'Detectando...',
             modelo: '',
             navegador: 'Detectando...',
             versao: '',
             userAgent: '',
             resolucao: '',
             isTv: false,
             isGoogleTv: false,
             isPhilips: false,
         },

         // Referência global ativa para contornar Garbage Collection bug no Chromium/Android TV
         activeUtterance: null,
         speechTimeoutId: null,

         detectarDispositivo() {
             const ua = navigator.userAgent || '';
             this.deviceInfo.userAgent = ua;
             this.deviceInfo.resolucao = `${window.innerWidth}x${window.innerHeight} (Tela: ${window.screen.width}x${window.screen.height})`;

             let nav = 'Navegador Web';
             let ver = '';
             if (ua.includes('Edg/')) {
                 nav = 'Microsoft Edge';
                 ver = (ua.match(/Edg\/([\d.]+)/) || [])[1] || '';
             } else if (ua.includes('Chrome/')) {
                 nav = ua.includes('Android') && !ua.includes('Version/') ? 'Google Chrome (Android)' : 'Chromium / WebView';
                 ver = (ua.match(/Chrome\/([\d.]+)/) || [])[1] || '';
             } else if (ua.includes('Firefox/')) {
                 nav = 'Mozilla Firefox';
                 ver = (ua.match(/Firefox\/([\d.]+)/) || [])[1] || '';
             } else if (ua.includes('Safari/') && !ua.includes('Chrome/')) {
                 nav = 'Apple Safari';
                 ver = (ua.match(/Version\/([\d.]+)/) || [])[1] || '';
             }
             this.deviceInfo.navegador = nav;
             this.deviceInfo.versao = ver;

             const isAndroid = ua.includes('Android');
             const isGoogleTv = ua.includes('GoogleTV') || ua.includes('Android TV') || (isAndroid && (ua.includes('Large Screen') || ua.includes('SmartTV') || ua.includes('TV')));
             const isPhilips = ua.toUpperCase().includes('PHILIPS') || ua.toUpperCase().includes('TPM') || ua.toUpperCase().includes('TPV');
             const isWebOs = ua.includes('Web0S') || ua.includes('webOS');
             const isTizen = ua.includes('Tizen');

             this.deviceInfo.isTv = isGoogleTv || isPhilips || isWebOs || isTizen || ua.includes('SmartTV') || ua.includes('TV');
             this.deviceInfo.isGoogleTv = isGoogleTv;
             this.deviceInfo.isPhilips = isPhilips;

             if (isPhilips && isGoogleTv) {
                 this.deviceInfo.fabricante = 'Philips Smart TV (Google TV / Android TV)';
             } else if (isPhilips) {
                 this.deviceInfo.fabricante = 'Philips Smart TV';
             } else if (isGoogleTv) {
                 this.deviceInfo.fabricante = 'Smart TV com Google TV / Android TV';
             } else if (isWebOs) {
                 this.deviceInfo.fabricante = 'LG Smart TV (webOS)';
             } else if (isTizen) {
                 this.deviceInfo.fabricante = 'Samsung Smart TV (Tizen)';
             } else if (isAndroid) {
                 this.deviceInfo.fabricante = 'Dispositivo Android TV';
             } else {
                 this.deviceInfo.fabricante = 'Computador Desktop / Notebook';
             }
         },

         carregarVozes() {
             if (!this.vozSuportada) {
                 this.estadoVoz = 'nao_suportado';
                 return [];
             }
             try {
                 const vozes = window.speechSynthesis.getVoices() || [];
                 this.totalVozes = vozes.length;

                 if (vozes.length > 0) {
                     this.vozesCarregadas = true;

                     // Procura prioritariamente voz pt-BR
                     const vozPt = vozes.find(v => v.lang && (v.lang.toLowerCase() === 'pt-br' || v.lang.toLowerCase().startsWith('pt-br') || v.lang.replace('_', '-').toLowerCase() === 'pt-br'))
                                || vozes.find(v => v.lang && (v.lang.toLowerCase() === 'pt' || v.lang.toLowerCase().startsWith('pt') || v.lang.toLowerCase().startsWith('por')));

                     if (vozPt) {
                         this.vozPtDisponivel = true;
                         this.vozSelecionadaNome = vozPt.name;
                         this.vozSelecionadaLang = vozPt.lang;
                     } else {
                         const vozDefault = vozes.find(v => v.default) || vozes[0];
                         this.vozPtDisponivel = false;
                         this.vozSelecionadaNome = vozDefault ? `${vozDefault.name} (Padrão)` : 'Padrão do Sistema';
                         this.vozSelecionadaLang = vozDefault ? vozDefault.lang : 'pt-BR';
                     }

                     if (this.estadoVoz === 'inicializando' || this.estadoVoz === 'aguardando_ativacao') {
                         this.estadoVoz = this.audioLiberado ? 'pronto' : 'aguardando_ativacao';
                     }
                 } else {
                     this.vozesCarregadas = false;
                     this.vozPtDisponivel = false;
                     if (this.estadoVoz === 'inicializando') {
                         this.estadoVoz = 'aguardando_ativacao';
                     }
                 }
                 return vozes;
             } catch (e) {
                 console.warn('[CER Digital TV Audio] Erro ao carregar vozes:', e);
                 this.ultimoErroVoz = 'Erro ao consultar vozes: ' + (e.message || e);
                 return [];
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

         reproduzirChime(volume = 0.8) {
             try {
                 const ctx = this.getAudioContext();
                 if (!ctx) return Promise.resolve(false);

                 if (ctx.state === 'suspended') {
                     ctx.resume();
                 }

                 const agora = ctx.currentTime;
                 const osc1 = ctx.createOscillator();
                 const gain1 = ctx.createGain();
                 osc1.type = 'sine';
                 osc1.frequency.setValueAtTime(587.33, agora); // Ré 5
                 gain1.gain.setValueAtTime(0.001, agora);
                 gain1.gain.exponentialRampToValueAtTime(Math.max(0.01, volume * 0.7), agora + 0.05);
                 gain1.gain.exponentialRampToValueAtTime(0.0001, agora + 0.45);
                 osc1.connect(gain1);
                 gain1.connect(ctx.destination);
                 osc1.start(agora);
                 osc1.stop(agora + 0.45);

                 const osc2 = ctx.createOscillator();
                 const gain2 = ctx.createGain();
                 osc2.type = 'sine';
                 osc2.frequency.setValueAtTime(880.00, agora + 0.25); // Lá 5
                 gain2.gain.setValueAtTime(0.001, agora + 0.25);
                 gain2.gain.exponentialRampToValueAtTime(Math.max(0.01, volume * 0.85), agora + 0.30);
                 gain2.gain.exponentialRampToValueAtTime(0.0001, agora + 0.95);
                 osc2.connect(gain2);
                 gain2.connect(ctx.destination);
                 osc2.start(agora + 0.25);
                 osc2.stop(agora + 0.95);

                 this.sinalSonoroFuncionando = true;
                 return new Promise(resolve => setTimeout(() => resolve(true), 950));
             } catch (e) {
                 console.warn('[CER Digital TV Audio] Erro ao reproduzir aviso sonoro:', e);
                 this.sinalSonoroFuncionando = false;
                 return Promise.resolve(false);
             }
         },

         ativarAudio() {
             this.audioLiberado = true;
             console.log('[CER Digital TV Audio] Gesto do usuário: destravando áudio e síntese de voz...');

             // 1. Destrava AudioContext
             const ctx = this.getAudioContext();
             this.reproduzirChime(0.2);

             // 2. Destrava SpeechSynthesis diretamente durante o gesto do usuário (fundamental no Google TV / Android)
             if (this.vozSuportada) {
                 try {
                     window.speechSynthesis.cancel();
                     if (window.speechSynthesis.paused) {
                         window.speechSynthesis.resume();
                     }

                     this.carregarVozes();

                     // Utterance de warmup para registrar ativação do usuário no Chromium
                     const warmup = new SpeechSynthesisUtterance(' ');
                     warmup.volume = 0.001;
                     warmup.rate = 1.0;
                     warmup.lang = 'pt-BR';
                     this.activeUtterance = warmup;
                     window._cerActiveUtterance = warmup;

                     warmup.onend = () => {
                         this.sinteseFuncionando = true;
                         this.estadoVoz = 'pronto';
                         this.activeUtterance = null;
                         console.log('[CER Digital TV Audio] Síntese de voz desbloqueada com sucesso via gesto do usuário.');
                     };

                     warmup.onerror = (err) => {
                         console.warn('[CER Digital TV Audio] Warmup de voz retornou erro:', err.error);
                         this.ultimoErroVoz = err.error || 'Erro no warmup';
                         this.ultimoErroCodigo = err.error || '';
                     };

                     window.speechSynthesis.speak(warmup);
                 } catch (e) {
                     console.warn('[CER Digital TV Audio] Falha ao inicializar síntese no gesto do usuário:', e);
                     this.ultimoErroVoz = e.message || 'Falha ao inicializar voz';
                     this.estadoVoz = 'erro';
                 }
             } else {
                 this.estadoVoz = 'nao_suportado';
             }
         },

         falarTexto(texto, volume = 0.8) {
             if (!texto || typeof texto !== 'string') return Promise.resolve(false);

             if (!this.vozSuportada) {
                 console.warn('[CER Digital TV Audio] Síntese de voz não suportada pelo navegador.');
                 this.estadoVoz = 'nao_suportado';
                 return Promise.resolve(false);
             }

             return new Promise((resolve) => {
                 try {
                     if (this.speechTimeoutId) {
                         clearTimeout(this.speechTimeoutId);
                         this.speechTimeoutId = null;
                     }

                     window.speechSynthesis.cancel();
                     if (window.speechSynthesis.paused) {
                         window.speechSynthesis.resume();
                     }

                     // Delay de 60ms para evitar cancel + speak simultâneo no Chromium Android
                     setTimeout(() => {
                         try {
                             const utter = new SpeechSynthesisUtterance(texto);
                             utter.lang = 'pt-BR';
                             utter.rate = 0.92;
                             utter.pitch = 1.0;
                             utter.volume = Math.max(0.1, Math.min(1.0, volume));

                             const vozes = window.speechSynthesis.getVoices() || [];
                             if (vozes.length > 0) {
                                 this.totalVozes = vozes.length;
                                 const vozPt = vozes.find(v => v.lang && (v.lang.toLowerCase() === 'pt-br' || v.lang.toLowerCase().startsWith('pt-br') || v.lang.replace('_', '-').toLowerCase() === 'pt-br'))
                                            || vozes.find(v => v.lang && (v.lang.toLowerCase() === 'pt' || v.lang.toLowerCase().startsWith('pt') || v.lang.toLowerCase().startsWith('por')));
                                 if (vozPt) {
                                     utter.voice = vozPt;
                                     this.vozPtDisponivel = true;
                                     this.vozSelecionadaNome = vozPt.name;
                                     this.vozSelecionadaLang = vozPt.lang;
                                 } else {
                                     const vozDefault = vozes.find(v => v.default) || vozes[0];
                                     if (vozDefault) {
                                         utter.voice = vozDefault;
                                         this.vozSelecionadaNome = vozDefault.name;
                                         this.vozSelecionadaLang = vozDefault.lang;
                                     }
                                 }
                             }

                             // Salva em referência persistente para evitar GC prematuro
                             this.activeUtterance = utter;
                             window._cerActiveUtterance = utter;

                             this.speechTimeoutId = setTimeout(() => {
                                 if (this.estadoVoz === 'falando') {
                                     console.warn('[CER Digital TV Audio] Timeout de segurança de síntese (12s). Destravando fila...');
                                     window.speechSynthesis.cancel();
                                     this.estadoVoz = 'pronto';
                                     this.activeUtterance = null;
                                     resolve(false);
                                 }
                             }, 12000);

                             utter.onstart = () => {
                                 this.estadoVoz = 'falando';
                                 this.sinteseFuncionando = true;
                                 this.ultimoEventoVoz = 'Iniciou fala';
                                 console.log('[CER Digital TV Audio] Iniciou pronúncia por voz.');
                             };

                             utter.onend = () => {
                                 if (this.speechTimeoutId) {
                                     clearTimeout(this.speechTimeoutId);
                                     this.speechTimeoutId = null;
                                 }
                                 this.estadoVoz = 'pronto';
                                 this.sinteseFuncionando = true;
                                 this.activeUtterance = null;
                                 window._cerActiveUtterance = null;
                                 this.ultimoEventoVoz = 'Concluiu fala com sucesso';
                                 console.log('[CER Digital TV Audio] Concluiu pronúncia por voz com sucesso.');
                                 resolve(true);
                             };

                             utter.onerror = (e) => {
                                 if (this.speechTimeoutId) {
                                     clearTimeout(this.speechTimeoutId);
                                     this.speechTimeoutId = null;
                                 }
                                 this.estadoVoz = 'erro';
                                 this.ultimoErroVoz = e.error || 'Erro desconhecido';
                                 this.ultimoErroCodigo = e.error || '';
                                 this.activeUtterance = null;
                                 window._cerActiveUtterance = null;
                                 this.ultimoEventoVoz = 'Erro: ' + (e.error || 'desconhecido');
                                 console.warn('[CER Digital TV Audio] Erro na síntese de voz:', e.error);
                                 resolve(false);
                             };

                             if (window.speechSynthesis.paused) {
                                 window.speechSynthesis.resume();
                             }

                             window.speechSynthesis.speak(utter);
                         } catch (errInner) {
                             console.warn('[CER Digital TV Audio] Exceção ao executar speak():', errInner);
                             this.estadoVoz = 'erro';
                             this.ultimoErroVoz = errInner.message || 'Exceção ao falar';
                             resolve(false);
                         }
                     }, 60);
                 } catch (e) {
                     console.warn('[CER Digital TV Audio] Falha geral ao preparar voz:', e);
                     this.estadoVoz = 'erro';
                     this.ultimoErroVoz = e.message || 'Falha geral';
                     resolve(false);
                 }
             });
         },

         testarVoz() {
             this.testeStatus = 'testando';
             this.testeMensagem = 'Executando teste de voz...';
             console.log('[CER Digital TV Audio] Iniciando teste de voz oficial...');

             if (this.toastTimeoutId) {
                 clearTimeout(this.toastTimeoutId);
             }

             if (!this.audioLiberado) {
                 this.ativarAudio();
             }

             if (!this.vozSuportada) {
                 this.testeStatus = 'sem_suporte';
                 this.testeMensagem = 'Navegador sem suporte à síntese (Web Speech API indisponível)';
                 this.toastTimeoutId = setTimeout(() => { this.testeMensagem = ''; }, 6000);
                 return;
             }

             // Toca sinal sonoro e pronuncia frase padrão estipulada
             this.reproduzirChime(0.5);
             const fraseTeste = 'Teste de áudio do CER Digital. A chamada por voz está funcionando.';

             setTimeout(() => {
                 this.falarTexto(fraseTeste, 0.9).then((sucesso) => {
                     if (sucesso) {
                         this.testeStatus = 'sucesso';
                         this.testeMensagem = 'Voz funcionando perfeitamente!';
                     } else {
                         if (this.totalVozes === 0) {
                             this.testeStatus = 'aguardando';
                             this.testeMensagem = 'Nenhuma voz disponível ou aguardando carregamento do sistema.';
                         } else {
                             this.testeStatus = 'erro';
                             this.testeMensagem = 'Falha na reprodução (' + (this.ultimoErroCodigo || this.ultimoErroVoz || 'erro desconhecido') + ')';
                         }
                     }
                     this.toastTimeoutId = setTimeout(() => { this.testeMensagem = ''; }, 6000);
                 });
             }, 950);
         },

         destravarSintese() {
             try {
                 if (window.speechSynthesis) {
                     window.speechSynthesis.cancel();
                     if (window.speechSynthesis.paused) {
                         window.speechSynthesis.resume();
                     }
                 }
                 this.activeUtterance = null;
                 this.estadoVoz = 'pronto';
                 this.ultimoEventoVoz = 'Síntese destravada manualmente';
                 console.log('[CER Digital TV Audio] Síntese destravada e resetada.');
                 this.carregarVozes();
             } catch (e) {
                 console.warn('[CER Digital TV Audio] Erro ao destravar:', e);
             }
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

         // Detecta suporte e características do dispositivo
         detectarDispositivo();
         chimeSuportado = !!(window.AudioContext || window.webkitAudioContext);
         vozSuportada = ('speechSynthesis' in window) && ('SpeechSynthesisUtterance' in window);

         if (vozSuportada) {
             carregarVozes();
             if (window.speechSynthesis.onvoiceschanged !== undefined) {
                 window.speechSynthesis.onvoiceschanged = () => {
                     carregarVozes();
                 };
             }
         } else {
             estadoVoz = 'nao_suportado';
         }

         // Tenta desbloquear áudio no primeiro clique em qualquer lugar da tela
         document.addEventListener('click', () => { if (!audioLiberado) ativarAudio(); }, { once: true });

         // Ouve eventos de nova chamada emitidos pelo Livewire
         $wire.on('nova-chamada-painel', (evento) => {
             const dados = Array.isArray(evento) ? evento[0] : evento;
             if (!dados) return;

             // Reproduz o chime imediatamente
             reproduzirChime(dados.volume ?? 0.8);

             // Se a voz estiver habilitada nas configurações, pronuncia após o término do som (950ms)
             if (dados.vozAtiva && dados.textoVoz) {
                 setTimeout(() => {
                     falarTexto(dados.textoVoz, dados.volume ?? 0.8);
                 }, 950);
             }
         });
     "
     class="min-h-screen flex flex-col justify-between p-4 sm:p-6 md:p-8 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100 select-none">

    <!-- Topo: Identificação da Unidade e Relógio Digital -->
    <header class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center gap-3.5">
            @if ($configuracao->logo_path)
                <img src="{{ $configuracao->logoUrl() }}"
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
                        CNES {{ $configuracao->cnes ?? '0795690' }}
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

        <!-- Toast Feedback Visual de Teste / Áudio -->
        <div x-show="testeMensagem"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-6 right-6 z-50 max-w-md rounded-2xl border px-5 py-3.5 shadow-2xl backdrop-blur-md flex items-center gap-3 text-sm font-semibold"
             :class="{
                 'bg-emerald-950/90 border-emerald-500/80 text-emerald-200': testeStatus === 'sucesso',
                 'bg-amber-950/90 border-amber-500/80 text-amber-200': testeStatus === 'testando' || testeStatus === 'aguardando',
                 'bg-rose-950/90 border-rose-500/80 text-rose-200': testeStatus === 'erro' || testeStatus === 'sem_suporte'
             }"
             style="display: none;">
            <span class="text-xl" x-text="testeStatus === 'sucesso' ? '✅' : (testeStatus === 'testando' ? '⏳' : (testeStatus === 'erro' || testeStatus === 'sem_suporte' ? '❌' : '🔔'))"></span>
            <span x-text="testeMensagem"></span>
        </div>

        <!-- Banner de Desbloqueio de Áudio do Navegador (Aparece até o operador clicar) -->
        <div x-show="!audioLiberado"
             x-transition
             class="my-3 flex flex-col sm:flex-row items-center justify-between gap-3 bg-amber-500/20 border-2 border-amber-500/60 rounded-2xl px-5 py-3.5 text-amber-200 shadow-lg">
            <div class="flex items-center gap-3 text-sm sm:text-base font-semibold">
                <span class="text-2xl shrink-0">🔊</span>
                <span>Atenção: Para ouvir o sinal sonoro e a chamada por voz nesta TV, clique no botão ao lado para ativar o áudio.</span>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <button type="button"
                        @click="ativarAudio()"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 font-bold text-slate-950 hover:bg-amber-400 transition shadow-md cursor-pointer text-sm">
                    Ativar Áudio da TV
                </button>
                <button type="button"
                        @click="testarVoz()"
                        class="rounded-xl border border-amber-500/60 bg-amber-950/40 px-3.5 py-2.5 font-bold text-amber-200 hover:bg-amber-900/60 transition cursor-pointer text-sm">
                    Testar Voz
                </button>
            </div>
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

        <!-- Rodapé: Status do Sistema, Suporte Técnico e Botão Tela Cheia -->
        <footer class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800 pt-4 text-xs text-slate-500">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Dispositivo Autorizado: <strong>{{ $dispositivoNome }}</strong></span>
                </span>
                <span class="hidden md:inline text-slate-600">•</span>
                <span class="hidden md:inline">Atualização instantânea via live stream</span>
                <span class="hidden md:inline text-slate-600">•</span>
                <!-- Indicador de Status do Áudio e Voz -->
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold"
                      :class="{
                          'bg-amber-500/10 text-amber-300 border border-amber-500/30': !audioLiberado,
                          'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30': audioLiberado && vozSuportada && (estadoVoz === 'pronto' || estadoVoz === 'falando'),
                          'bg-sky-500/10 text-sky-300 border border-sky-500/30': audioLiberado && !vozSuportada,
                          'bg-rose-500/10 text-rose-300 border border-rose-500/30': audioLiberado && estadoVoz === 'erro'
                      }">
                    <span x-text="!audioLiberado ? '🔇 Áudio Silenciado (Aguardando Ativação)' : (!vozSuportada ? '🔔 Somente Sinal Sonoro' : (estadoVoz === 'erro' ? '⚠️ Falha na Voz' : (vozPtDisponivel ? '🔊 Som e Voz Ativos (pt-BR)' : '🔊 Som e Voz Ativos')))"></span>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Botão discreto Testar Voz -->
                <button type="button"
                        @click="testarVoz()"
                        :disabled="testeStatus === 'testando'"
                        title="Executa teste do sinal sonoro e pronúncia oficial"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-teal-700/60 bg-teal-950/40 px-3 py-1.5 text-teal-300 hover:bg-teal-900/60 hover:text-white transition cursor-pointer disabled:opacity-50 font-medium">
                    <span x-text="testeStatus === 'testando' ? '⏳' : '🗣️'"></span>
                    <span x-text="testeStatus === 'testando' ? 'Testando...' : 'Testar Voz'"></span>
                </button>

                <!-- Botão Painel Técnico TV -->
                <button type="button"
                        @click="modalDiagnosticoAberto = true"
                        title="Ver painel técnico de áudio, navegador e Web Speech API"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/60 px-3 py-1.5 text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer font-medium">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    <span>Painel Técnico TV</span>
                </button>

                <!-- Botão Tela Cheia -->
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
                        @click="modalDiagnosticoAberto = true"
                        class="text-slate-400 hover:text-white transition text-xs font-semibold cursor-pointer">
                    Painel Técnico TV
                </button>
                <span>•</span>
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

    <!-- ============================================================= -->
    <!-- MODAL DE SUPORTE TÉCNICO SMART TV (ÁUDIO E SÍNTESE DE VOZ)   -->
    <!-- ============================================================= -->
    <div x-show="modalDiagnosticoAberto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
         style="display: none;"
         @keydown.escape.window="modalDiagnosticoAberto = false">

        <div @click.away="modalDiagnosticoAberto = false"
             class="w-full max-w-3xl rounded-3xl border border-slate-700 bg-slate-900 p-6 sm:p-8 shadow-2xl text-slate-100 max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/20 text-teal-400 text-xl border border-teal-500/40">
                        📺
                    </span>
                    <div>
                        <h3 class="text-lg sm:text-xl font-black text-white">
                            Painel Técnico de Áudio e Voz — Smart TV
                        </h3>
                        <p class="text-xs text-slate-400">
                            CER Digital • Informações para validação no televisor ou suporte local
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="modalDiagnosticoAberto = false"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- Grid de Métricas do Dispositivo e Áudio -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-xs sm:text-sm">
                <!-- Dispositivo / Modelo -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Dispositivo Detectado</span>
                    <span class="text-white font-bold block" x-text="deviceInfo.fabricante"></span>
                    <span class="text-slate-400 text-xs block mt-1" x-text="'Resolução: ' + deviceInfo.resolucao"></span>
                </div>

                <!-- Navegador / WebView -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Navegador / Engine</span>
                    <span class="text-white font-bold block" x-text="deviceInfo.navegador + (deviceInfo.versao ? ' (' + deviceInfo.versao + ')' : '')"></span>
                    <span class="text-slate-400 text-xs block mt-1" x-text="deviceInfo.isTv ? 'Ambiente Smart TV / Google TV detectado' : 'Ambiente Desktop detectado'"></span>
                </div>

                <!-- Suporte Web Speech API -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Web Speech API (Síntese de Voz)</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-2.5 w-2.5 rounded-full" :class="vozSuportada ? 'bg-emerald-400' : 'bg-rose-500'"></span>
                        <span class="font-bold" :class="vozSuportada ? 'text-emerald-300' : 'text-rose-400'" x-text="vozSuportada ? 'Suportada pelo Navegador' : 'Não Suportada'"></span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1" x-text="'Vozes Carregadas: ' + totalVozes"></span>
                </div>

                <!-- Suporte Web Audio API (Sinal Sonoro) -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Web Audio API (Chime Sonoro)</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-2.5 w-2.5 rounded-full" :class="chimeSuportado ? 'bg-emerald-400' : 'bg-rose-500'"></span>
                        <span class="font-bold" :class="chimeSuportado ? 'text-emerald-300' : 'text-rose-400'" x-text="chimeSuportado ? 'Suportada pelo Navegador' : 'Não Suportada'"></span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1" x-text="audioLiberado ? 'Contexto de Áudio Liberado' : 'Aguardando Desbloqueio (Clique)'"></span>
                </div>

                <!-- Voz Selecionada -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Voz Ativa Selecionada</span>
                    <span class="text-white font-bold block truncate" x-text="vozSelecionadaNome || 'Nenhuma voz selecionada'"></span>
                    <span class="text-slate-400 text-xs block mt-1" x-text="'Idioma: ' + (vozSelecionadaLang || 'N/A') + (vozPtDisponivel ? ' (pt-BR nativo)' : '')"></span>
                </div>

                <!-- Estado da Síntese e Último Evento -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Estado da Síntese / Evento</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="font-mono font-bold uppercase text-xs px-2 py-0.5 rounded"
                              :class="{
                                  'bg-teal-950 text-teal-300 border border-teal-800': estadoVoz === 'pronto',
                                  'bg-amber-950 text-amber-300 border border-amber-800 animate-pulse': estadoVoz === 'falando',
                                  'bg-rose-950 text-rose-300 border border-rose-800': estadoVoz === 'erro',
                                  'bg-slate-800 text-slate-300': estadoVoz === 'aguardando_ativacao' || estadoVoz === 'inicializando'
                              }"
                              x-text="estadoVoz">
                        </span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1 truncate" x-text="ultimoEventoVoz"></span>
                </div>
            </div>

            <!-- Detalhes de Erro (se houver) -->
            <div x-show="ultimoErroVoz" class="rounded-xl border border-rose-500/40 bg-rose-950/30 p-4 mb-6 text-xs">
                <span class="text-rose-400 font-bold block uppercase mb-1">Último Erro Registrado na Síntese:</span>
                <span class="text-rose-200 font-mono" x-text="ultimoErroVoz"></span>
            </div>

            <!-- User Agent Detalhado -->
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4 mb-6">
                <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">User Agent da Smart TV:</span>
                <p class="font-mono text-[11px] text-slate-300 break-all leading-relaxed" x-text="deviceInfo.userAgent"></p>
            </div>

            <!-- Barra de Ações de Teste e Recuperação -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            @click="testarVoz()"
                            :disabled="testeStatus === 'testando'"
                            class="rounded-xl bg-teal-600 px-4 py-2 font-bold text-white hover:bg-teal-500 transition cursor-pointer text-xs sm:text-sm disabled:opacity-50">
                        Testar Voz Completa
                    </button>

                    <button type="button"
                            @click="reproduzirChime(0.5)"
                            class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 font-bold text-slate-200 hover:bg-slate-700 transition cursor-pointer text-xs sm:text-sm">
                        Testar Chime
                    </button>

                    <button type="button"
                            @click="carregarVozes()"
                            class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 font-medium text-slate-300 hover:bg-slate-700 transition cursor-pointer text-xs">
                        Recarregar Vozes
                    </button>

                    <button type="button"
                            @click="destravarSintese()"
                            class="rounded-xl border border-amber-700/60 bg-amber-950/40 px-3 py-2 font-medium text-amber-300 hover:bg-amber-900/40 transition cursor-pointer text-xs">
                        Destravar Síntese
                    </button>
                </div>

                <button type="button"
                        @click="modalDiagnosticoAberto = false"
                        class="rounded-xl border border-slate-700 px-5 py-2 font-bold text-slate-300 hover:bg-slate-800 hover:text-white transition cursor-pointer text-xs sm:text-sm">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>
