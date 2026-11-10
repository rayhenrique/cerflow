@props([
    'item',
    'isMobile' => false,
])

@php
    $ativo = $item->estaAtivo(request());
@endphp

<a href="{{ $item->url() }}"
   @if ($isMobile)
       @click="mobileMenuOpen = false"
   @endif
   @if ($item->target)
       target="{{ $item->target }}"
       rel="noopener noreferrer"
   @endif
   title="{{ $item->getTooltip() }}"
   aria-label="{{ $item->rotulo }}"
   @if ($ativo)
       aria-current="page"
   @endif
   :class="sidebarCollapsed && !{{ $isMobile ? 'true' : 'false' }} ? 'justify-center px-2 py-2.5' : 'px-3 py-2.5'"
   class="group flex items-center gap-3 rounded-lg text-sm font-medium transition cursor-pointer {{ $ativo ? 'bg-slate-800 text-teal-300 font-semibold shadow-xs' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
    <svg class="h-5 w-5 shrink-0 transition {{ $ativo ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        {!! $item->icone !!}
    </svg>
    <span x-show="{{ $isMobile ? 'true' : '!sidebarCollapsed' }}" class="truncate flex-1 flex items-center justify-between gap-2">
        <span class="truncate">{{ $item->rotulo }}</span>
        @if ($item->badge)
            <span class="rounded bg-teal-950 px-1.5 py-0.5 text-[10px] font-mono text-teal-300 border border-teal-800 shrink-0">
                {{ $item->badge }}
            </span>
        @endif
    </span>
</a>

