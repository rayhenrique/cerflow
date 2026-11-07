<div x-data="{
        toasts: [],
        adicionar(tipo, titulo, mensagem) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, tipo: tipo || 'sucesso', titulo: titulo || this.tituloPadrao(tipo), mensagem });
            setTimeout(() => this.remover(id), 4500);
        },
        remover(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        tituloPadrao(tipo) {
            if (tipo === 'erro') return 'Erro na Operação';
            if (tipo === 'alerta') return 'Atenção';
            if (tipo === 'info') return 'Informação';
            return 'Operação Concluída';
        }
     }"
     x-on:toast.window="adicionar($event.detail.tipo ?? ($event.detail[0]?.tipo ?? 'sucesso'), $event.detail.titulo ?? ($event.detail[0]?.titulo ?? null), $event.detail.mensagem ?? ($event.detail[0]?.mensagem ?? ''))"
     @if(session()->has('sucesso'))
         x-init="adicionar('sucesso', 'Sucesso', @js(session('sucesso')))"
     @elseif(session()->has('sucesso_config'))
         x-init="adicionar('sucesso', 'Configurações Salvas', @js(session('sucesso_config')))"
     @elseif(session()->has('sucesso_pep'))
         x-init="adicionar('sucesso', 'Prontuário Atualizado', @js(session('sucesso_pep')))"
     @elseif(session()->has('erro'))
         x-init="adicionar('erro', 'Erro', @js(session('erro')))"
     @elseif(session()->has('alerta'))
         x-init="adicionar('alerta', 'Atenção', @js(session('alerta')))"
     @endif
     class="fixed top-3 right-3 left-3 sm:left-auto sm:top-4 sm:right-4 z-[100] flex w-auto sm:w-full sm:max-w-sm flex-col gap-2.5 pointer-events-none">

    <template x-for="t in toasts" :key="t.id">
        <div class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-xl backdrop-blur-xs transition-all duration-200 bg-white"
             :class="{
                'border-emerald-200 ring-1 ring-emerald-500/20': t.tipo === 'sucesso',
                'border-red-200 ring-1 ring-red-500/20': t.tipo === 'erro',
                'border-amber-200 ring-1 ring-amber-500/20': t.tipo === 'alerta',
                'border-sky-200 ring-1 ring-sky-500/20': t.tipo === 'info'
             }">
            <!-- Ícone por Tipo -->
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white"
                 :class="{
                    'bg-emerald-600': t.tipo === 'sucesso',
                    'bg-red-600': t.tipo === 'erro',
                    'bg-amber-500': t.tipo === 'alerta',
                    'bg-sky-600': t.tipo === 'info'
                 }">
                <span x-show="t.tipo === 'sucesso'">✓</span>
                <span x-show="t.tipo === 'erro'">✕</span>
                <span x-show="t.tipo === 'alerta'">!</span>
                <span x-show="t.tipo === 'info'">i</span>
            </div>

            <div class="flex-1 min-w-0 text-xs">
                <p class="font-bold text-slate-900" x-text="t.titulo"></p>
                <p class="mt-0.5 text-slate-600 leading-relaxed" x-text="t.mensagem"></p>
            </div>

            <button type="button"
                    @click="remover(t.id)"
                    class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 text-xs">
                ✕
            </button>
        </div>
    </template>
</div>

