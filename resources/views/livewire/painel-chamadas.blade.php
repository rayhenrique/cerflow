<div wire:poll.4s="atualizarPainel"
     x-data="{
         // --- ESTADOS DE CONTROLE DE ÁUDIO E FILA ---
         audioLiberado: false,
         audioCtx: null,
         chimeSuportado: false,
         sinalSonoroFuncionando: false,
         filaChamadas: [],
         processandoFila: false,
         itemAtualFila: null,
         ultimaChamadaIdProcessada: null,
         ultimoTimestampChamada: 0,
         watchdogTimerId: null,

         // --- SÍNTESE DE VOZ E MOTORES ---
         vozSuportada: false,
         vozesCarregadas: false,
         totalVozes: 0,
         listaVozesDisponiveis: [],
         vozPtDisponivel: false,
         vozPtPtDisponivel: false,
         vozSelecionadaNome: '',
         vozSelecionadaLang: '',
         vozSelecionada: null,
         motorVozAtivo: 'detectando', // 'web_speech' | 'audio_local' | 'indisponivel'
         motorLocalDisponivel: false,
         motorLocalDriver: 'none',
         motorLocalDescricao: 'Verificando motor local...',
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
             sistemaOperacional: 'Detectando...',
             navegador: 'Detectando...',
             versao: '',
             userAgent: '',
             resolucao: '',
             isTv: false,
             isTitanOs: false,
             isGoogleTv: false,
             isPhilips: false,
         },

         // Referências ativas para evitar GC bug e gerenciar reprodução
         activeUtterance: null,
         audioPlayerElement: null,

         normalizarLang(lang) {
             if (!lang || typeof lang !== 'string') return '';
             return lang.trim().replace('_', '-').toLowerCase();
         },

         detectarDispositivo() {
             const ua = navigator.userAgent || '';
             this.deviceInfo.userAgent = ua;
             this.deviceInfo.resolucao = `${window.innerWidth}x${window.innerHeight} (Tela: ${window.screen.width}x${window.screen.height})`;

             // 1. Identificação do Navegador
             let nav = 'Navegador Web';
             let ver = '';
             if (ua.includes('OPR/') || ua.includes('OMI/')) {
                 nav = 'Opera TV / OMI (Chromium)';
                 ver = (ua.match(/Chrome\/([\d.]+)/) || [])[1] || (ua.match(/OPR\/([\d.]+)/) || [])[1] || '';
             } else if (ua.includes('Edg/')) {
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

             // 2. Extração do Modelo da TV (ex: PUK7300, PUG7300, etc.)
             const matchModelo = ua.match(/(?:Philips,\s*|Philips\s+)([A-Za-z0-9_-]+)/i)
                 || ua.match(/(PU[GK]\d{4}|[0-9]{2}PU[GK]\d{4})/i)
                 || ua.match(/(TV_[A-Za-z0-9_]+)/i);
             this.deviceInfo.modelo = matchModelo ? matchModelo[1].replace(/[\/,]/g, '') : '';

             // 3. Detecção Específica de Plataforma / SO
             const isTitanOs = ua.includes('TitanOS') || ua.includes('WhaleTV') || (ua.includes('NETTV') && !ua.includes('Android'));
             const isAndroid = ua.includes('Android');
             const isGoogleTv = !isTitanOs && (ua.includes('GoogleTV') || ua.includes('Android TV') || (isAndroid && (ua.includes('Large Screen') || ua.includes('SmartTV') || ua.includes('TV'))));
             const isPhilips = ua.toUpperCase().includes('PHILIPS') || ua.toUpperCase().includes('TPM') || ua.toUpperCase().includes('TPV') || isTitanOs;
             const isWebOs = ua.includes('Web0S') || ua.includes('webOS');
             const isTizen = ua.includes('Tizen');
             const isWindows = ua.includes('Windows') || ua.includes('Win64') || ua.includes('Win32');
             const isMac = ua.includes('Macintosh') || ua.includes('Mac OS X');
             const isLinuxDesktop = ua.includes('Linux') && !isAndroid && !isTitanOs && !isTizen && !isWebOs;

             this.deviceInfo.isTv = isTitanOs || isGoogleTv || isPhilips || isWebOs || isTizen || ua.includes('SmartTV') || ua.includes('TV');
             this.deviceInfo.isTitanOs = isTitanOs;
             this.deviceInfo.isGoogleTv = isGoogleTv;
             this.deviceInfo.isPhilips = isPhilips;

             if (isPhilips && isTitanOs) {
                 this.deviceInfo.fabricante = 'Philips Smart TV (TitanOS / WhaleTV)';
                 this.deviceInfo.sistemaOperacional = 'TitanOS 3.0 / WhaleTV 2.0';
             } else if (isPhilips && isGoogleTv) {
                 this.deviceInfo.fabricante = 'Philips Smart TV (Google TV / Android TV)';
                 this.deviceInfo.sistemaOperacional = 'Google TV / Android TV';
             } else if (isPhilips) {
                 this.deviceInfo.fabricante = 'Philips Smart TV';
                 this.deviceInfo.sistemaOperacional = 'Philips Smart TV OS';
             } else if (isGoogleTv) {
                 this.deviceInfo.fabricante = 'Smart TV com Google TV / Android TV';
                 this.deviceInfo.sistemaOperacional = 'Google TV / Android TV';
             } else if (isWebOs) {
                 this.deviceInfo.fabricante = 'LG Smart TV (webOS)';
                 this.deviceInfo.sistemaOperacional = 'LG webOS';
             } else if (isTizen) {
                 this.deviceInfo.fabricante = 'Samsung Smart TV (Tizen)';
                 this.deviceInfo.sistemaOperacional = 'Samsung Tizen OS';
             } else if (isWindows) {
                 this.deviceInfo.fabricante = 'Computador Desktop / Notebook';
                 this.deviceInfo.sistemaOperacional = 'Microsoft Windows';
             } else if (isMac) {
                 this.deviceInfo.fabricante = 'Computador Apple Mac';
                 this.deviceInfo.sistemaOperacional = 'Apple macOS';
             } else if (isLinuxDesktop) {
                 this.deviceInfo.fabricante = 'Computador Linux Desktop';
                 this.deviceInfo.sistemaOperacional = 'Linux';
             } else {
                 this.deviceInfo.fabricante = 'Dispositivo Web';
                 this.deviceInfo.sistemaOperacional = 'Sistema Operacional Web';
             }
         },

         consultarStatusMotorLocal() {
             fetch('/painel-chamadas/audio/status', {
                 headers: { 'Accept': 'application/json' }
             })
             .then(r => r.ok ? r.json() : null)
             .then(dados => {
                 if (dados) {
                     this.motorLocalDisponivel = !!dados.disponivel;
                     this.motorLocalDriver = dados.driver || 'none';
                     this.motorLocalDescricao = dados.descricao || 'Motor local ativo';

                     // Se o navegador não tem voz pt-BR, estabelece o motor local como primário
                     if (!this.vozPtDisponivel) {
                         this.motorVozAtivo = 'audio_local';
                     }
                 }
             })
             .catch(() => {
                 this.motorLocalDisponivel = false;
             });
         },

         carregarVozes() {
             if (!this.vozSuportada) {
                 this.estadoVoz = 'nao_suportado';
                 this.motorVozAtivo = 'audio_local';
                 return [];
             }
             try {
                 const vozes = window.speechSynthesis.getVoices() || [];
                 this.totalVozes = vozes.length;

                 // Cataloga cada voz com seus atributos completos
                 this.listaVozesDisponiveis = vozes.map(v => {
                     const lNorm = this.normalizarLang(v.lang);
                     const isPtBr = lNorm === 'pt-br' || lNorm.startsWith('pt-br');
                     const isPtPt = lNorm === 'pt-pt' || lNorm.startsWith('pt-pt');
                     return {
                         nome: v.name,
                         lang: v.lang,
                         langNorm: lNorm,
                         origem: v.localService !== undefined ? (v.localService ? 'Local (Offline)' : 'Remota (Rede)') : 'N/I',
                         isDefault: !!v.default,
                         isPtBr: isPtBr,
                         isPtPt: isPtPt,
                     };
                 });

                 if (vozes.length > 0) {
                     this.vozesCarregadas = true;

                     // 1. Prioriza EXCLUSIVAMENTE vozes com idioma pt-BR
                     const vozesPtBr = vozes.filter(v => {
                         const l = this.normalizarLang(v.lang);
                         return l === 'pt-br' || l.startsWith('pt-br');
                     });

                     const vozesPtPt = vozes.filter(v => {
                         const l = this.normalizarLang(v.lang);
                         return l === 'pt-pt' || l.startsWith('pt-pt');
                     });

                     this.vozPtPtDisponivel = vozesPtPt.length > 0;

                     if (vozesPtBr.length > 0) {
                         this.vozPtDisponivel = true;
                         // Prioriza voz com flag default ou localService
                         const melhorVoz = vozesPtBr.find(v => v.default) || vozesPtBr.find(v => v.localService) || vozesPtBr[0];
                         this.vozSelecionada = melhorVoz;
                         this.vozSelecionadaNome = melhorVoz.name;
                         this.vozSelecionadaLang = melhorVoz.lang;
                         this.motorVozAtivo = 'web_speech';
                         this.ultimoEventoVoz = 'Voz nativa pt-BR selecionada: ' + melhorVoz.name;
                     } else {
                         // NUNCA seleciona voz americana/estrangeira para falar em português
                         this.vozPtDisponivel = false;
                         this.vozSelecionada = null;
                         this.vozSelecionadaNome = '';
                         this.vozSelecionadaLang = '';
                         // Define o Motor Local do CER Digital como o responsável pela pronúncia
                         this.motorVozAtivo = 'audio_local';
                         this.ultimoEventoVoz = 'Navegador sem voz pt-BR. Motor de Áudio Local ativado.';
                     }

                     if (this.estadoVoz === 'inicializando' || this.estadoVoz === 'aguardando_ativacao') {
                         this.estadoVoz = this.audioLiberado ? 'pronto' : 'aguardando_ativacao';
                     }
                 } else {
                     this.vozesCarregadas = false;
                     this.vozPtDisponivel = false;
                     this.vozSelecionada = null;
                     this.vozSelecionadaNome = '';
                     this.vozSelecionadaLang = '';
                     this.motorVozAtivo = 'audio_local';
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
             return new Promise((resolve) => {
                 try {
                     const ctx = this.getAudioContext();
                     if (!ctx) {
                         this.sinalSonoroFuncionando = false;
                         return resolve(false);
                     }

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

                     // Conexão do evento real de término sem temporizadores arbitrários
                     let resolvido = false;
                     osc2.onended = () => {
                         if (!resolvido) {
                             resolvido = true;
                             this.sinalSonoroFuncionando = true;
                             resolve(true);
                         }
                     };

                     osc2.start(agora + 0.25);
                     osc2.stop(agora + 0.95);

                     // Salvaguarda caso onended não dispare no navegador embarcado
                     setTimeout(() => {
                         if (!resolvido) {
                             resolvido = true;
                             this.sinalSonoroFuncionando = true;
                             resolve(true);
                         }
                     }, 1000);
                 } catch (e) {
                     console.warn('[CER Digital TV Audio] Erro ao reproduzir aviso sonoro:', e);
                     this.sinalSonoroFuncionando = false;
                     resolve(false);
                 }
             });
         },

         ativarAudio() {
             this.audioLiberado = true;
             console.log('[CER Digital TV Audio] Interação do usuário: destravando áudio e canais de síntese...');

             // 1. Destrava AudioContext
             const ctx = this.getAudioContext();
             this.reproduzirChime(0.2);

             // 2. Destrava SpeechSynthesis diretamente durante o gesto do usuário
             if (this.vozSuportada) {
                 try {
                     if (window.speechSynthesis.paused) {
                         window.speechSynthesis.resume();
                     }
                     this.carregarVozes();
                 } catch (e) {
                     console.warn('[CER Digital TV Audio] Erro ao reativar síntese no gesto:', e);
                 }
             }

             // 3. Atualiza status do motor local
             this.consultarStatusMotorLocal();
             this.estadoVoz = 'pronto';
         },

         // --- FILA CONTROLADA DE REPRODUÇÃO FIFO ---
         enfileirarChamada(dados) {
             if (!dados || !dados.textoVoz) return;

             // Evita duplicação se o mesmo ID foi recebido nos últimos 4 segundos
             if (this.ultimaChamadaIdProcessada === dados.id && (Date.now() - this.ultimoTimestampChamada) < 4000) {
                 return;
             }
             this.ultimaChamadaIdProcessada = dados.id;
             this.ultimoTimestampChamada = Date.now();

             this.filaChamadas.push({
                 id: dados.id,
                 texto: dados.textoVoz,
                 volume: dados.volume ?? 0.8,
                 vozAtiva: dados.vozAtiva ?? true,
             });

             if (!this.processandoFila) {
                 this.processarFila();
             }
         },

         processarFila() {
             if (this.filaChamadas.length === 0) {
                 this.processandoFila = false;
                 this.itemAtualFila = null;
                 this.estadoVoz = 'pronto';
                 return;
             }

             this.processandoFila = true;
             const item = this.filaChamadas.shift();
             this.itemAtualFila = item;

             if (this.watchdogTimerId) {
                 clearTimeout(this.watchdogTimerId);
             }
             // Watchdog de 15 segundos para destravar a fila caso ocorra qualquer pane inesperada
             this.watchdogTimerId = setTimeout(() => {
                 console.warn('[CER Digital TV Audio] Watchdog da fila acionado (15s). Destravando fila...');
                 this.processarFila();
             }, 15000);

             // 1. Toca o sinal sonoro e aguarda sua conclusão real
             this.reproduzirChime(item.volume).then(() => {
                 // 2. Se a voz estiver habilitada, executa a pronúncia
                 if (item.vozAtiva && item.texto) {
                     this.executarFalaItem(item.texto, item.volume).then(() => {
                         if (this.watchdogTimerId) {
                             clearTimeout(this.watchdogTimerId);
                             this.watchdogTimerId = null;
                         }
                         this.processarFila();
                     });
                 } else {
                     if (this.watchdogTimerId) {
                         clearTimeout(this.watchdogTimerId);
                         this.watchdogTimerId = null;
                     }
                     this.processarFila();
                 }
             });
         },

         executarFalaItem(texto, volume) {
             // Se houver voz nativa pt-BR no navegador, tenta Web Speech API
             if (this.vozSuportada && this.vozPtDisponivel) {
                 return this.falarWebSpeech(texto, volume).then((sucesso) => {
                     if (sucesso) {
                         return true;
                     }
                     // Fallback imediato se a Web Speech API falhou (ex: synthesis-failed no TitanOS)
                     console.warn('[CER Digital TV Audio] Web Speech falhou. Executando fallback para Motor de Áudio Local...');
                     return this.reproduzirAudioLocal(texto, volume);
                 });
             }

             // Se não houver voz pt-BR no navegador (ex: TitanOS com apenas vozes em inglês), usa o Motor Local
             return this.reproduzirAudioLocal(texto, volume);
         },

         falarWebSpeech(texto, volume = 0.8) {
             if (!this.vozSuportada || !this.vozPtDisponivel) {
                 return Promise.resolve(false);
             }

             return new Promise((resolve) => {
                 try {
                     const utter = new SpeechSynthesisUtterance(texto);
                     utter.lang = 'pt-BR';
                     utter.rate = 0.95;
                     utter.pitch = 1.0;
                     utter.volume = Math.max(0.1, Math.min(1.0, volume));

                     if (this.vozSelecionada) {
                         utter.voice = this.vozSelecionada;
                     }

                     // Referências persistentes contra o bug de Garbage Collection no Chromium
                     this.activeUtterance = utter;
                     window._cerActiveUtterance = utter;

                     utter.onstart = () => {
                         this.estadoVoz = 'falando';
                         this.motorVozAtivo = 'web_speech';
                         this.sinteseFuncionando = true;
                         this.ultimoEventoVoz = 'Reproduzindo voz via Web Speech API (pt-BR)';
                     };

                     utter.onend = () => {
                         this.estadoVoz = 'pronto';
                         this.sinteseFuncionando = true;
                         this.activeUtterance = null;
                         window._cerActiveUtterance = null;
                         this.ultimoEventoVoz = 'Concluiu fala com sucesso (Web Speech)';
                         resolve(true);
                     };

                     utter.onerror = (e) => {
                         this.estadoVoz = 'erro';
                         this.ultimoErroVoz = e.error || 'Erro na síntese';
                         this.ultimoErroCodigo = e.error || 'synthesis-failed';
                         this.activeUtterance = null;
                         window._cerActiveUtterance = null;
                         this.ultimoEventoVoz = 'Erro Web Speech: ' + (e.error || 'falha');
                         console.warn('[CER Digital TV Audio] Erro na síntese nativa:', e.error);
                         resolve(false);
                     };

                     if (window.speechSynthesis.paused) {
                         window.speechSynthesis.resume();
                     }

                     window.speechSynthesis.speak(utter);
                 } catch (e) {
                     console.warn('[CER Digital TV Audio] Falha ao acionar speak():', e);
                     this.ultimoErroVoz = e.message || 'Exceção speak()';
                     resolve(false);
                 }
             });
         },

         reproduzirAudioLocal(texto, volume = 0.8) {
             return new Promise((resolve) => {
                 try {
                     const url = '/painel-chamadas/audio/fala?texto=' + encodeURIComponent(texto);
                     const audio = new Audio(url);
                     audio.volume = Math.max(0.1, Math.min(1.0, volume));
                     this.audioPlayerElement = audio;

                     audio.onplay = () => {
                         this.estadoVoz = 'falando';
                         this.motorVozAtivo = 'audio_local';
                         this.sinteseFuncionando = true;
                         this.ultimoEventoVoz = 'Reproduzindo voz via Motor de Áudio Local CER Digital';
                     };

                     audio.onended = () => {
                         this.estadoVoz = 'pronto';
                         this.audioPlayerElement = null;
                         this.sinteseFuncionando = true;
                         this.ultimoEventoVoz = 'Concluiu fala com sucesso (Motor Local)';
                         resolve(true);
                     };

                     audio.onerror = (err) => {
                         console.warn('[CER Digital TV Audio] Erro no player de áudio local:', err);
                         this.estadoVoz = 'erro';
                         this.ultimoErroVoz = 'Falha ao carregar fluxo de áudio local';
                         this.ultimoErroCodigo = 'audio-load-error';
                         this.audioPlayerElement = null;
                         resolve(false);
                     };

                     const playPromise = audio.play();
                     if (playPromise !== undefined) {
                         playPromise.catch((e) => {
                             console.warn('[CER Digital TV Audio] Bloqueio ao reproduzir áudio local:', e);
                             this.estadoVoz = 'erro';
                             this.ultimoErroVoz = e.message || 'Bloqueio de reprodução de áudio';
                             this.audioPlayerElement = null;
                             resolve(false);
                         });
                     }
                 } catch (e) {
                     console.warn('[CER Digital TV Audio] Exceção em reproduzirAudioLocal:', e);
                     this.estadoVoz = 'erro';
                     this.ultimoErroVoz = e.message || 'Erro no player de áudio local';
                     this.audioPlayerElement = null;
                     resolve(false);
                 }
             });
         },

         // --- TESTES INDIVIDUAIS DE COMPATIBILIDADE ---
         testarChime() {
             this.testeStatus = 'testando';
             this.testeMensagem = 'Testando sinal sonoro (Chime)...';
             this.reproduzirChime(0.6).then((ok) => {
                 this.testeStatus = ok ? 'sucesso' : 'erro';
                 this.testeMensagem = ok ? 'Sinal sonoro reproduzido com sucesso!' : 'Falha ao reproduzir sinal sonoro';
                 setTimeout(() => { this.testeMensagem = ''; }, 5000);
             });
         },

         testarVozBrasileira() {
             this.testeStatus = 'testando';
             this.testeMensagem = 'Testando voz brasileira nativa (Web Speech API)...';
             if (!this.vozSuportada || !this.vozPtDisponivel) {
                 this.testeStatus = 'sem_suporte';
                 this.testeMensagem = 'Navegador não possui voz nativa em português brasileiro (pt-BR). Utilize o Motor Local.';
                 setTimeout(() => { this.testeMensagem = ''; }, 6000);
                 return;
             }
             const frase = 'Teste de áudio do CER Digital. A chamada por voz está funcionando.';
             this.falarWebSpeech(frase, 0.9).then((ok) => {
                 this.testeStatus = ok ? 'sucesso' : 'erro';
                 this.testeMensagem = ok ? 'Voz brasileira nativa reproduzida com sucesso!' : 'Falha na voz nativa (' + (this.ultimoErroCodigo || this.ultimoErroVoz || 'erro') + ')';
                 setTimeout(() => { this.testeMensagem = ''; }, 6000);
             });
         },

         testarVozDisponivel() {
             this.testeStatus = 'testando';
             this.testeMensagem = 'Testando qualquer voz disponível no navegador...';
             if (!this.vozSuportada || this.totalVozes === 0) {
                 this.testeStatus = 'sem_suporte';
                 this.testeMensagem = 'Nenhuma voz carregada no navegador.';
                 setTimeout(() => { this.testeMensagem = ''; }, 6000);
                 return;
             }
             try {
                 const vozes = window.speechSynthesis.getVoices() || [];
                 const voz = vozes[0];
                 const utter = new SpeechSynthesisUtterance('Test of audio output.');
                 utter.voice = voz;
                 utter.onend = () => {
                     this.testeStatus = 'sucesso';
                     this.testeMensagem = 'Voz do sistema (' + voz.name + ') reproduzida.';
                     setTimeout(() => { this.testeMensagem = ''; }, 5000);
                 };
                 utter.onerror = (e) => {
                     this.testeStatus = 'erro';
                     this.testeMensagem = 'Falha na voz do sistema (' + (e.error || 'erro') + ')';
                     setTimeout(() => { this.testeMensagem = ''; }, 6000);
                 };
                 window.speechSynthesis.speak(utter);
             } catch (e) {
                 this.testeStatus = 'erro';
                 this.testeMensagem = 'Exceção ao testar voz do sistema';
                 setTimeout(() => { this.testeMensagem = ''; }, 5000);
             }
         },

         testarMotorLocal() {
             this.testeStatus = 'testando';
             this.testeMensagem = 'Testando Motor de Áudio Local CER Digital...';
             const frase = 'Teste de áudio do CER Digital. A chamada por voz está funcionando.';
             this.reproduzirAudioLocal(frase, 0.9).then((ok) => {
                 this.testeStatus = ok ? 'sucesso' : 'erro';
                 this.testeMensagem = ok ? 'Motor Local reproduziu com sucesso!' : 'Falha no Motor Local (' + (this.ultimoErroVoz || 'erro') + ')';
                 setTimeout(() => { this.testeMensagem = ''; }, 6000);
             });
         },

         testarVoz() {
             // Teste oficial geral: Chime + Voz ativa (Web Speech se tiver pt-BR, senão Motor Local)
             this.testeStatus = 'testando';
             this.testeMensagem = 'Iniciando teste completo de áudio e voz...';

             if (!this.audioLiberado) {
                 this.ativarAudio();
             }

             const fraseTeste = 'Teste de áudio do CER Digital. A chamada por voz está funcionando.';
             this.enfileirarChamada({
                 id: 'teste-' + Date.now(),
                 textoVoz: fraseTeste,
                 volume: 0.9,
                 vozAtiva: true
             });
         },

         destravarSintese() {
             try {
                 if (window.speechSynthesis) {
                     window.speechSynthesis.cancel();
                     if (window.speechSynthesis.paused) {
                         window.speechSynthesis.resume();
                     }
                 }
                 if (this.audioPlayerElement) {
                     this.audioPlayerElement.pause();
                     this.audioPlayerElement = null;
                 }
                 if (this.watchdogTimerId) {
                     clearTimeout(this.watchdogTimerId);
                     this.watchdogTimerId = null;
                 }
                 this.filaChamadas = [];
                 this.processandoFila = false;
                 this.itemAtualFila = null;
                 this.activeUtterance = null;
                 this.estadoVoz = 'pronto';
                 this.ultimoEventoVoz = 'Fila destravada e resetada manualmente';
                 console.log('[CER Digital TV Audio] Fila e canais de áudio destravados.');
                 this.carregarVozes();
                 this.consultarStatusMotorLocal();
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

         // 1. Detecta características do dispositivo e motor local
         detectarDispositivo();
         consultarStatusMotorLocal();

         // 2. Verifica suporte Web Audio e Web Speech API
         chimeSuportado = !!(window.AudioContext || window.webkitAudioContext);
         vozSuportada = ('speechSynthesis' in window) && ('SpeechSynthesisUtterance' in window);

         if (vozSuportada) {
             carregarVozes();
             if (window.speechSynthesis.onvoiceschanged !== undefined) {
                 window.speechSynthesis.onvoiceschanged = () => {
                     carregarVozes();
                 };
             }
             // Polling de segurança para navegadores embarcados com carregamento lento de vozes
             setTimeout(() => { if (totalVozes === 0) carregarVozes(); }, 800);
             setTimeout(() => { if (totalVozes === 0) carregarVozes(); }, 2000);
         } else {
             estadoVoz = 'nao_suportado';
             motorVozAtivo = 'audio_local';
         }

         // 3. Ativação física universal (Mouse, Toque, Teclado e Controle Remoto da TV)
         const listenerAtivacao = () => { if (!audioLiberado) ativarAudio(); };
         ['click', 'pointerdown', 'keydown', 'touchstart'].forEach(evt => {
             document.addEventListener(evt, listenerAtivacao, { passive: true, once: true });
         });

         // 4. Ouve eventos de nova chamada emitidos pelo Livewire e enfileira na fila controlada
         $wire.on('nova-chamada-painel', (evento) => {
             const dados = Array.isArray(evento) ? evento[0] : evento;
             if (!dados) return;

             enfileirarChamada(dados);
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
                          'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30': audioLiberado && motorVozAtivo === 'web_speech' && (estadoVoz === 'pronto' || estadoVoz === 'falando'),
                          'bg-teal-500/10 text-teal-300 border border-teal-500/30': audioLiberado && motorVozAtivo === 'audio_local' && (estadoVoz === 'pronto' || estadoVoz === 'falando'),
                          'bg-rose-500/10 text-rose-300 border border-rose-500/30': audioLiberado && estadoVoz === 'erro',
                          'bg-sky-500/10 text-sky-300 border border-sky-500/30': audioLiberado && motorVozAtivo === 'indisponivel'
                      }">
                    <span x-text="!audioLiberado ? '🔇 Áudio Silenciado (Aguardando Ativação)' : (motorVozAtivo === 'web_speech' ? '🔊 Som e Voz Ativos (pt-BR)' : (motorVozAtivo === 'audio_local' ? '🔊 Som e Voz Ativos (Motor Local)' : (estadoVoz === 'erro' ? '⚠️ Falha no Áudio' : '🔔 Somente Sinal Sonoro')))"></span>
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
                <!-- Dispositivo / Modelo / SO -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Dispositivo &amp; Sistema Operacional</span>
                    <span class="text-white font-bold block" x-text="deviceInfo.fabricante"></span>
                    <div class="flex items-center gap-2 mt-1 text-slate-300">
                        <span x-text="'Modelo: ' + (deviceInfo.modelo || 'Não identificado no UA')"></span>
                        <span>•</span>
                        <span x-text="'SO: ' + deviceInfo.sistemaOperacional"></span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1" x-text="'Resolução da Tela: ' + deviceInfo.resolucao"></span>
                </div>

                <!-- Navegador / WebView -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Navegador &amp; Engine</span>
                    <span class="text-white font-bold block" x-text="deviceInfo.navegador + (deviceInfo.versao ? ' (' + deviceInfo.versao + ')' : '')"></span>
                    <span class="text-slate-400 text-xs block mt-1" x-text="deviceInfo.isTv ? (deviceInfo.isTitanOs ? 'Ambiente Philips TitanOS / WhaleTV identificado' : 'Ambiente Smart TV identificado') : 'Ambiente Desktop identificado'"></span>
                    <span class="text-slate-400 text-xs block mt-0.5" x-text="'Contexto de Áudio: ' + (audioLiberado ? 'Liberado via interação' : 'Aguardando gesto inicial')"></span>
                </div>

                <!-- Web Speech API & Vozes -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Web Speech API (Síntese Nativa)</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-2.5 w-2.5 rounded-full" :class="vozSuportada ? 'bg-emerald-400' : 'bg-rose-500'"></span>
                        <span class="font-bold" :class="vozSuportada ? 'text-emerald-300' : 'text-rose-400'" x-text="vozSuportada ? 'Disponível no Navegador' : 'Não Suportada'"></span>
                    </div>
                    <div class="flex items-center gap-2 mt-1 text-xs">
                        <span class="text-slate-300" x-text="'Total de Vozes: ' + totalVozes"></span>
                        <span>•</span>
                        <span :class="vozPtDisponivel ? 'text-emerald-400' : 'text-amber-400'" x-text="vozPtDisponivel ? 'Voz pt-BR: Sim' : 'Voz pt-BR: Não'"></span>
                    </div>
                </div>

                <!-- Motor Local CER Digital (Servidor) -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Motor Local CER Digital (Servidor)</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-2.5 w-2.5 rounded-full" :class="motorLocalDisponivel ? 'bg-emerald-400' : 'bg-sky-400'"></span>
                        <span class="font-bold text-slate-100" x-text="motorLocalDisponivel ? 'Operacional (' + motorLocalDriver.toUpperCase() + ')' : 'Fluxo Direto via Servidor'"></span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1" x-text="motorLocalDescricao"></span>
                </div>

                <!-- Motor Ativo em Uso -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Mecanismo Ativo para Anúncios</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="h-2.5 w-2.5 rounded-full" :class="motorVozAtivo === 'web_speech' ? 'bg-emerald-400' : (motorVozAtivo === 'audio_local' ? 'bg-teal-400' : 'bg-rose-500')"></span>
                        <span class="font-bold" :class="motorVozAtivo === 'web_speech' ? 'text-emerald-300' : (motorVozAtivo === 'audio_local' ? 'text-teal-300' : 'text-rose-400')"
                              x-text="motorVozAtivo === 'web_speech' ? 'Web Speech API (Voz Nativa pt-BR)' : (motorVozAtivo === 'audio_local' ? 'CER Digital Local Audio Engine' : 'Indisponível')"></span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1 truncate" x-text="vozPtDisponivel ? ('Voz: ' + vozSelecionadaNome) : 'Voz gerada localmente pelo servidor CER Digital'"></span>
                </div>

                <!-- Fila de Reprodução & Estado -->
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Fila de Chamadas &amp; Estado</span>
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
                        <span class="text-xs text-slate-300" x-text="processandoFila ? 'Processando chamada...' : (filaChamadas.length + ' pendentes na fila')"></span>
                    </div>
                    <span class="text-slate-400 text-xs block mt-1 truncate" x-text="ultimoEventoVoz"></span>
                </div>
            </div>

            <!-- SEÇÃO DE COMPATIBILIDADE TTS (AUDITORIA) -->
            <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-5 mb-6 text-xs">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 mb-3">
                    <span class="font-bold text-slate-200 uppercase tracking-wider text-xs flex items-center gap-2">
                        <span>🔊</span>
                        <span>STATUS DE COMPATIBILIDADE TTS</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                          :class="{
                              'bg-emerald-950 text-emerald-300 border border-emerald-800': sinteseFuncionando || motorVozAtivo === 'web_speech' || motorVozAtivo === 'audio_local',
                              'bg-amber-950 text-amber-300 border border-amber-800': estadoVoz === 'aguardando_ativacao' || estadoVoz === 'inicializando',
                              'bg-rose-950 text-rose-300 border border-rose-800': estadoVoz === 'erro' || motorVozAtivo === 'indisponivel'
                          }">
                        <span class="h-2 w-2 rounded-full"
                              :class="{
                                  'bg-emerald-400': sinteseFuncionando || motorVozAtivo === 'web_speech' || motorVozAtivo === 'audio_local',
                                  'bg-amber-400': estadoVoz === 'aguardando_ativacao' || estadoVoz === 'inicializando',
                                  'bg-rose-400': estadoVoz === 'erro' || motorVozAtivo === 'indisponivel'
                              }"></span>
                        <span x-text="sinteseFuncionando ? 'Síntese Operacional' : (estadoVoz === 'aguardando_ativacao' ? 'Aguardando Ativação' : (estadoVoz === 'erro' ? 'Falha Registrada' : 'Pronto'))"></span>
                    </span>
                </div>

                <!-- Detalhamento de Vozes Carregadas pelo Navegador -->
                <div class="mb-4">
                    <span class="text-slate-400 block text-[11px] font-semibold uppercase mb-2">Vozes Carregadas no Navegador da TV:</span>
                    <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1 font-mono text-[11px]">
                        <template x-for="(v, idx) in listaVozesDisponiveis" :key="idx">
                            <div class="flex items-center justify-between p-2 rounded-lg border border-slate-800 bg-slate-900/60">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="text-slate-400" x-text="(idx + 1) + '.'"></span>
                                    <span class="text-white font-semibold truncate" x-text="v.nome"></span>
                                    <span class="text-slate-400 text-[10px]" x-text="'(' + v.lang + ')'"></span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                          :class="v.isPtBr ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : (v.isPtPt ? 'bg-sky-950 text-sky-300 border border-sky-800' : 'bg-slate-800 text-slate-400')"
                                          x-text="v.isPtBr ? 'pt-BR' : (v.isPtPt ? 'pt-PT' : 'Outro')"></span>
                                    <span class="text-slate-500 text-[10px]" x-text="v.origem"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="listaVozesDisponiveis.length === 0" class="text-slate-500 text-center py-3">
                            Nenhuma voz carregada ou aguardando inicialização do motor de síntese.
                        </div>
                    </div>
                </div>

                <!-- Detalhes de Erro (se houver) -->
                <div x-show="ultimoErroVoz" class="rounded-xl border border-rose-500/40 bg-rose-950/30 p-3 mb-3 text-xs">
                    <span class="text-rose-400 font-bold block uppercase mb-1">Último Erro Registrado na Síntese:</span>
                    <span class="text-rose-200 font-mono" x-text="(ultimoErroCodigo ? '[' + ultimoErroCodigo + '] ' : '') + ultimoErroVoz"></span>
                </div>

                <!-- User Agent Detalhado -->
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">
                    <span class="text-slate-400 block text-[11px] font-semibold uppercase mb-1">User Agent da TV:</span>
                    <p class="font-mono text-[10px] text-slate-300 break-all leading-relaxed" x-text="deviceInfo.userAgent"></p>
                </div>
            </div>

            <!-- Barra de Ações de Teste e Recuperação -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            @click="testarChime()"
                            class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 font-bold text-slate-200 hover:bg-slate-700 transition cursor-pointer text-xs">
                        1. Testar Chime
                    </button>

                    <button type="button"
                            @click="testarVozBrasileira()"
                            class="rounded-xl border border-teal-700 bg-teal-950/50 px-3 py-2 font-bold text-teal-300 hover:bg-teal-900 transition cursor-pointer text-xs">
                        2. Testar Voz pt-BR (Nativa)
                    </button>

                    <button type="button"
                            @click="testarVozDisponivel()"
                            class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 font-bold text-slate-300 hover:bg-slate-700 transition cursor-pointer text-xs">
                        3. Testar Voz do Sistema
                    </button>

                    <button type="button"
                            @click="testarMotorLocal()"
                            class="rounded-xl bg-teal-600 px-3.5 py-2 font-bold text-white hover:bg-teal-500 transition cursor-pointer text-xs shadow-md">
                        4. Testar Áudio Local (Servidor)
                    </button>

                    <button type="button"
                            @click="carregarVozes()"
                            class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 font-medium text-slate-300 hover:bg-slate-700 transition cursor-pointer text-xs">
                        5. Recarregar Vozes
                    </button>

                    <button type="button"
                            @click="destravarSintese()"
                            class="rounded-xl border border-amber-700/60 bg-amber-950/40 px-3 py-2 font-medium text-amber-300 hover:bg-amber-900/40 transition cursor-pointer text-xs">
                        6. Destravar Fila
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
