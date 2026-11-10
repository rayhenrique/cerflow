@props([
    'grupo',
    'isMobile' => false,
    'user' => null,
])

@php
    $user = $user ?? auth()->user();
    $itens = $grupo->itensAutorizados($user);
    $temAtivo = $grupo->temItemAtivo(request(), $user);
@endphp

@if (count($itens) > 0)
    <div class="space-y-1 {{ $grupo->id !== 'inicio' ? 'pt-3 mt-3 border-t border-slate-800/80' : '' }}"
         id="bloco-nav-{{ $grupo->id }}">

        @if (! $grupo->expansivel)
            {{-- Grupo Não Expansível (ex: INÍCIO) --}}
            <div x-show="{{ $isMobile ? 'true' : '!sidebarCollapsed' }}"
                 class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono">
                {{ $grupo->titulo }}
            </div>

            <div class="space-y-1">
                @foreach ($itens as $item)
                    <x-navigation.item :item="$item" :is-mobile="$isMobile" />
                @endforeach
            </div>
        @else
            {{-- Grupo Expansível (ATENDIMENTO, GESTÃO, CADASTROS, ADMINISTRAÇÃO) --}}
            <button type="button"
                    @click="if (!{{ $isMobile ? 'true' : 'false' }} && sidebarCollapsed) { toggleSidebar(); gruposAbertos['{{ $grupo->id }}'] = true; } else { toggleGrupo('{{ $grupo->id }}'); }"
                    :aria-expanded="isGrupoAberto('{{ $grupo->id }}')"
                    aria-controls="nav-grupo-{{ $grupo->id }}"
                    title="{{ $grupo->titulo }} ({{ $grupo->expansivel ? 'expandir/recolher' : '' }})"
                    :class="sidebarCollapsed && !{{ $isMobile ? 'true' : 'false' }} ? 'justify-center px-1.5 py-1.5' : 'justify-between px-3 py-2'"
                    class="w-full flex items-center rounded-lg transition cursor-pointer select-none {{ $temAtivo ? 'text-teal-400 bg-slate-800/50' : 'text-slate-400 hover:bg-slate-800/40 hover:text-white' }}">

                {{-- Visualização Expandida e Mobile --}}
                <span x-show="{{ $isMobile ? 'true' : '!sidebarCollapsed' }}"
                      class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider font-mono truncate">
                    <span>{{ $grupo->titulo }}</span>
                </span>
                <span x-show="{{ $isMobile ? 'true' : '!sidebarCollapsed' }}"
                      class="text-[10px] transition-transform duration-200 text-slate-400"
                      :class="isGrupoAberto('{{ $grupo->id }}') ? 'rotate-90 text-teal-400' : ''">
                    ▸
                </span>

                {{-- Visualização Compacta (Desktop lg:w-20) --}}
                <span x-show="!{{ $isMobile ? 'true' : 'false' }} && sidebarCollapsed"
                      class="text-[10px] font-mono font-bold tracking-wider text-slate-400 hover:text-teal-400 transition"
                      :class="isGrupoAberto('{{ $grupo->id }}') ? 'text-teal-400 font-bold' : ''">
                    {{ mb_strtoupper(mb_substr($grupo->titulo, 0, 3)) }}
                </span>
            </button>

            <div x-show="isGrupoAberto('{{ $grupo->id }}')"
                 id="nav-grupo-{{ $grupo->id }}"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-1">
                @foreach ($itens as $item)
                    <x-navigation.item :item="$item" :is-mobile="$isMobile" />
                @endforeach
            </div>
        @endif
    </div>
@endif

