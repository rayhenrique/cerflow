<div>
    @if($aberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-0.5 text-xs font-mono font-bold text-teal-700 ring-1 ring-inset ring-teal-600/20">
                                v{{ $releaseAtual['versao'] }}
                            </span>
                            <span class="text-xs font-medium text-slate-400">
                                {{ $releaseAtual['tipo'] }} • {{ $releaseAtual['data'] }}
                            </span>
                        </div>
                        <h3 class="mt-2 text-base font-bold text-slate-900">
                            Novidades da Versão — {{ $releaseAtual['titulo'] }}
                        </h3>
                    </div>
                    <button type="button"
                            wire:click="fecharModal"
                            class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        ✕
                    </button>
                </div>

                <div class="space-y-2.5 text-xs text-slate-600">
                    <p class="font-semibold text-slate-800">O que há de novo nesta atualização:</p>
                    <ul class="space-y-2">
                        @foreach($releaseAtual['destaques'] as $destaque)
                            <li class="flex items-start gap-2.5 rounded-lg bg-slate-50 p-2.5 border border-slate-100">
                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-teal-600 text-[10px] font-bold text-white">
                                    ✓
                                </span>
                                <span class="leading-relaxed text-slate-700">{{ $destaque }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                    <a href="{{ route('ajuda.sobre') }}"
                       wire:click="fecharModal"
                       class="font-semibold text-teal-700 hover:text-teal-600 underline underline-offset-4">
                        Ver evolução completa das versões →
                    </a>

                    <button type="button"
                            wire:click="fecharModal"
                            class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white shadow-xs hover:bg-slate-800">
                        Entendi, continuar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

