<div>
    @if($aberto)
        <div x-data="{
                visivel: true,
                init() {
                    document.body.classList.add('overflow-hidden');
                    this.$watch('visivel', (val) => {
                        if (val) {
                            document.body.classList.add('overflow-hidden');
                        } else {
                            document.body.classList.remove('overflow-hidden');
                        }
                    });
                    if (typeof this.$cleanup === 'function') {
                        this.$cleanup(() => {
                            document.body.classList.remove('overflow-hidden');
                        });
                    }
                },
                fechar() {
                    this.visivel = false;
                    document.body.classList.remove('overflow-hidden');
                    $wire.fecharModal();
                },
                irParaAjuda() {
                    this.visivel = false;
                    document.body.classList.remove('overflow-hidden');
                },
                destroy() {
                    document.body.classList.remove('overflow-hidden');
                }
             }"
             x-show="visivel"
             x-cloak
             x-on:keydown.escape.window="fechar()"
             @click.self="fechar()"
             role="dialog"
             aria-modal="true"
             aria-labelledby="titulo-modal-novidades"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-3 sm:p-4 md:p-6 backdrop-blur-xs overscroll-contain">
            <div class="flex flex-col w-full max-w-lg sm:max-w-xl max-h-[85vh] max-h-[85dvh] rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                 style="max-height: min(85vh, 85dvh, calc(100dvh - 1.5rem));"
                 @click.stop>
                {{-- CABEÇALHO FIXO --}}
                <div class="flex items-start justify-between gap-3 sm:gap-4 border-b border-slate-100 px-5 py-4 sm:px-6 sm:py-5 shrink-0 bg-white">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-0.5 text-xs font-mono font-bold text-teal-700 ring-1 ring-inset ring-teal-600/20">
                                v{{ $releaseAtual['versao'] }}
                            </span>
                            <span class="text-xs font-medium text-slate-400">
                                {{ $releaseAtual['tipo'] }} • {{ $releaseAtual['data'] }}
                            </span>
                        </div>
                        <h3 id="titulo-modal-novidades" class="mt-2 text-sm sm:text-base font-bold text-slate-900 leading-snug break-words">
                            Novidades da Versão — {{ $releaseAtual['titulo'] }}
                        </h3>
                    </div>
                    <button type="button"
                            x-on:click="fechar()"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer shrink-0"
                            aria-label="Fechar janela de novidades">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- CONTEÚDO COM ROLAGEM VERTICAL INDEPENDENTE --}}
                <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain px-5 py-4 sm:px-6 sm:py-5 space-y-3 text-xs text-slate-600 [&_img]:max-w-full [&_img]:h-auto [&_img]:rounded-lg">
                    <p class="font-semibold text-slate-800 text-xs sm:text-sm">O que há de novo nesta atualização:</p>
                    <ul class="space-y-2.5">
                        @foreach($releaseAtual['destaques'] as $destaque)
                            <li class="flex items-start gap-2.5 sm:gap-3 rounded-lg bg-slate-50 p-3 border border-slate-100">
                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-teal-600 text-[10px] font-bold text-white">
                                    ✓
                                </span>
                                <span class="leading-relaxed text-slate-700 break-words flex-1">{{ $destaque }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- RODAPÉ FIXO COM AÇÃO DE CONTINUAR --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:px-6 sm:py-4 bg-slate-50/70 shrink-0 text-xs">
                    <button type="button"
                            wire:click="irParaAjudaSobre"
                            x-on:click="irParaAjuda()"
                            class="inline-flex items-center justify-center font-semibold text-teal-700 hover:text-teal-600 underline underline-offset-4 transition cursor-pointer text-center py-1 sm:py-0 w-full sm:w-auto">
                        Ver evolução completa das versões →
                    </button>

                    <button type="button"
                            x-on:click="fechar()"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white shadow-xs hover:bg-slate-800 transition cursor-pointer text-center shrink-0 touch-target w-full sm:w-auto">
                        Entendi, continuar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>