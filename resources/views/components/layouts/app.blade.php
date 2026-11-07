<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CER Flow — Gestão de Reabilitação e BPA-i' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-900 antialiased"
      x-data="{ mobileMenuOpen: false }"
      @keydown.escape.window="mobileMenuOpen = false">
    @php
        $versaoSistema = app(\App\Services\VersionService::class)->versaoAtual();
        $emConfiguracoes = request()->routeIs('configuracoes.*');
        $secaoConfigAtual = request()->route('secao');
    @endphp

    <!-- Sistema Global de Toasts (Sucesso, Erro, Alerta, Info) -->
    <x-toast-notifications />

    <div class="min-h-full flex">
        <!-- Drawer Mobile & Tablet (< lg) -->
        <div x-show="mobileMenuOpen"
             style="display: none;"
             class="fixed inset-0 z-50 flex lg:hidden"
             role="dialog"
             aria-modal="true">
            <!-- Backdrop escuro -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false"
                 class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"></div>

            <!-- Painel Lateral Mobile -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-in-out duration-200 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative flex w-full max-w-xs flex-1 flex-col bg-slate-900 text-slate-300 border-r border-slate-800">
                <div class="flex h-16 shrink-0 items-center justify-between px-5 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-500 text-slate-950 font-bold text-sm shadow-xs">
                            CF
                        </span>
                        <div>
                            <span class="text-sm font-semibold tracking-tight text-white block leading-none">CER Flow</span>
                            <span class="text-[11px] text-slate-400 font-mono">SIA/SUS • v{{ $versaoSistema }}</span>
                        </div>
                    </div>
                    <button type="button"
                            @click="mobileMenuOpen = false"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white cursor-pointer"
                            aria-label="Fechar menu">
                        ✕
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto space-y-1 px-3 py-4 text-sm" x-data="{ menuConfigMobile: true }">
                    @auth
                        @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']))
                            <a href="{{ route('grade.semanal') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('grade.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('grade.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                                Grade Semanal
                            </a>
                        @endif

                        @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'coordenacao']))
                            <a href="{{ route('recepcao.checkin') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('recepcao.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('recepcao.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                                Recepção & Check-in
                            </a>
                        @endif

                        @if(auth()->user()->hasAnyRole(['terapeuta', 'coordenacao']))
                            <a href="{{ route('prontuario.index') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('prontuario.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('prontuario.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                                Prontuário (PEP Multi)
                            </a>
                        @endif

                        @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                            <a href="{{ route('faturamento.bpai') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('faturamento.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="h-2 w-2 rounded-full {{ request()->routeIs('faturamento.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                                Faturamento BPA-i
                            </a>
                        @endif

                        <div class="pt-4 mt-4 border-t border-slate-800/80 space-y-1">
                            <button type="button"
                                    @click="menuConfigMobile = !menuConfigMobile"
                                    class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider {{ $emConfiguracoes ? 'text-teal-400 bg-slate-800/50' : 'text-slate-400 hover:bg-slate-800/40 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <span>⚙</span>
                                    <span>Configurações</span>
                                </span>
                                <span class="text-[10px] transition-transform duration-150" :class="menuConfigMobile ? 'rotate-90' : ''">▸</span>
                            </button>

                            <div x-show="menuConfigMobile" class="space-y-1 pl-4 border-l border-slate-800 ml-3 text-xs">
                                @if(auth()->user()->hasAnyRole(['recepcao', 'coordenacao']))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'pacientes']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'pacientes' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <span>•</span>
                                        <span>Pacientes SUS</span>
                                    </a>
                                @endif

                                @if(auth()->user()->hasRole('coordenacao'))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'salas']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'salas' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <span>•</span>
                                        <span>Salas & Espaços</span>
                                    </a>

                                    <a href="{{ route('configuracoes.secao', ['secao' => 'profissionais']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'profissionais' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <span>•</span>
                                        <span>Equipe & Perfis</span>
                                    </a>
                                @endif

                                @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'sigtap']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'sigtap' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <span>•</span>
                                        <span>Tabela SIGTAP</span>
                                    </a>
                                @endif

                                @if(auth()->user()->hasRole('coordenacao'))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'auditoria']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'auditoria' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <span>•</span>
                                        <span>Auditoria LGPD</span>
                                    </a>
                                @endif

                                <a href="{{ route('configuracoes.secao', ['secao' => 'perfil']) }}"
                                   @click="mobileMenuOpen = false"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'perfil' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>•</span>
                                    <span>Minha Conta & PIN</span>
                                </a>
                            </div>

                            <div class="pt-2">
                                <a href="{{ route('ajuda.sobre') }}"
                                   @click="mobileMenuOpen = false"
                                   class="group flex items-center justify-between rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('ajuda.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                    <span class="flex items-center gap-3">
                                        <span class="h-2 w-2 rounded-full {{ request()->routeIs('ajuda.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                                        Ajuda & Sobre
                                    </span>
                                    <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-teal-400 border border-slate-700">
                                        v{{ $versaoSistema }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    @endauth
                </nav>

                @auth
                    <div class="border-t border-slate-800 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div class="truncate">
                                <p class="text-xs font-medium text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-teal-400 uppercase tracking-wider font-mono mt-0.5">
                                    {{ auth()->user()->getRoleNames()->first() ?? 'usuário' }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white">
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Sidebar Desktop (Estilo Linear / Filament) -->
        <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 bg-slate-900 text-slate-300 border-r border-slate-800">
            <div class="flex h-16 shrink-0 items-center justify-between px-5 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-500 text-slate-950 font-bold text-sm shadow-xs">
                        CF
                    </span>
                    <div>
                        <span class="text-sm font-semibold tracking-tight text-white block leading-none">CER Flow</span>
                        <span class="text-[11px] text-slate-400 font-mono">SIA/SUS • v{{ $versaoSistema }}</span>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto space-y-1 px-3 py-4 text-sm" x-data="{ menuConfigAberto: {{ $emConfiguracoes ? 'true' : 'true' }} }">
                @auth
                    @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']))
                        <a href="{{ route('grade.semanal') }}"
                           class="group flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('grade.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full {{ request()->routeIs('grade.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                            Grade Semanal
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'coordenacao']))
                        <a href="{{ route('recepcao.checkin') }}"
                           class="group flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('recepcao.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full {{ request()->routeIs('recepcao.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                            Recepção & Check-in
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['terapeuta', 'coordenacao']))
                        <a href="{{ route('prontuario.index') }}"
                           class="group flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('prontuario.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full {{ request()->routeIs('prontuario.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                            Prontuário (PEP Multi)
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                        <a href="{{ route('faturamento.bpai') }}"
                           class="group flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('faturamento.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full {{ request()->routeIs('faturamento.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                            Faturamento BPA-i
                        </a>
                    @endif

                    <!-- Menu Expansível de Configurações com Subitens -->
                    <div class="pt-4 mt-4 border-t border-slate-800/80 space-y-1">
                        <button type="button"
                                @click="menuConfigAberto = !menuConfigAberto"
                                class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider {{ $emConfiguracoes ? 'text-teal-400 bg-slate-800/50' : 'text-slate-400 hover:bg-slate-800/40 hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <span>⚙</span>
                                <span>Configurações</span>
                            </span>
                            <span class="text-[10px] transition-transform duration-150" :class="menuConfigAberto ? 'rotate-90' : ''">▸</span>
                        </button>

                        <div x-show="menuConfigAberto" class="space-y-0.5 pl-4 border-l border-slate-800 ml-3 text-xs">
                            @if(auth()->user()->hasAnyRole(['recepcao', 'coordenacao']))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'pacientes']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'pacientes' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>•</span>
                                    <span>Pacientes SUS</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasRole('coordenacao'))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'salas']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'salas' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>•</span>
                                    <span>Salas & Espaços</span>
                                </a>

                                <a href="{{ route('configuracoes.secao', ['secao' => 'profissionais']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'profissionais' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>•</span>
                                    <span>Equipe & Perfis RBAC</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'sigtap']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'sigtap' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>•</span>
                                    <span>Tabela SIGTAP x CBO</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasRole('coordenacao'))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'auditoria']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'auditoria' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>•</span>
                                    <span>Auditoria LGPD</span>
                                </a>
                            @endif

                            <a href="{{ route('configuracoes.secao', ['secao' => 'perfil']) }}"
                               class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'perfil' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                <span>•</span>
                                <span>Minha Conta & PIN</span>
                            </a>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('ajuda.sobre') }}"
                               class="group flex items-center justify-between rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('ajuda.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <span class="h-2 w-2 rounded-full {{ request()->routeIs('ajuda.*') ? 'bg-teal-400' : 'bg-slate-600' }}"></span>
                                    Ajuda & Sobre
                                </span>
                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-teal-400 border border-slate-700">
                                    v{{ $versaoSistema }}
                                </span>
                            </a>
                        </div>
                    </div>
                @endauth
            </nav>

            @auth
                <div class="border-t border-slate-800 p-4">
                    <div class="flex items-center justify-between">
                        <div class="truncate">
                            <p class="text-xs font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-teal-400 uppercase tracking-wider font-mono mt-0.5">
                                {{ auth()->user()->getRoleNames()->first() ?? 'usuário' }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white">
                                Sair
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </aside>

        <!-- Conteúdo Principal -->
        <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
            <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-slate-200 bg-white/95 px-3 sm:px-6 backdrop-blur-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <!-- Botão Hamburger Mobile/Tablet -->
                    <button type="button"
                            @click="mobileMenuOpen = true"
                            class="lg:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none cursor-pointer"
                            aria-label="Abrir menu de navegação">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-xs sm:text-sm font-semibold text-slate-900 truncate">{{ $header ?? 'Painel Operacional CER' }}</h1>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-3 text-xs text-slate-500 shrink-0">
                    <button type="button"
                            onclick="Livewire.dispatch('abrir-modal-versao')"
                            class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 sm:px-2.5 py-1 font-mono text-[11px] sm:text-xs font-semibold text-teal-700 border border-teal-200 hover:bg-teal-100 transition cursor-pointer"
                            title="Clique para ver as novidades desta versão">
                        <span>✨ v{{ $versaoSistema }}</span>
                        <span class="hidden sm:inline">— Novidades</span>
                    </button>
                    <span class="hidden md:inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        CNES {{ \App\Services\BpaiExportService::CNES_PADRAO }}
                    </span>
                </div>
            </header>

            <main class="flex-1 p-3 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @auth
        <livewire:modal-novidades-versao />
    @endauth

    @livewireScripts
</body>
</html>
