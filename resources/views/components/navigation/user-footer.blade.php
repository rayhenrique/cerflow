@props([
    'isMobile' => false,
])

@auth
    @php
        /** @var \App\Models\User $user */
        $user = auth()->user();
    @endphp
    <div class="border-t border-slate-800 p-3 shrink-0 bg-slate-900/90">
        <div class="flex items-center"
             :class="sidebarCollapsed && !{{ $isMobile ? 'true' : 'false' }} ? 'flex-col gap-2 justify-center px-0.5' : 'justify-between gap-2 px-1'">
            <div x-show="{{ $isMobile ? 'true' : '!sidebarCollapsed' }}" class="truncate min-w-0 flex-1">
                <p class="text-xs font-semibold text-white truncate" title="{{ $user->name }}">{{ $user->name }}</p>
                <p class="text-[10px] text-teal-400 uppercase tracking-wider font-mono mt-0.5 truncate">
                    {{ $user->getRoleNames()->first() ?? 'usuário' }}
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        title="Sair do Sistema"
                        aria-label="Sair do Sistema"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-rose-950/50 hover:text-rose-300 hover:border-rose-800/60 border border-slate-700/50 transition cursor-pointer">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    <span x-show="{{ $isMobile ? 'true' : '!sidebarCollapsed' }}">Sair</span>
                </button>
            </form>
        </div>
    </div>
@endauth

