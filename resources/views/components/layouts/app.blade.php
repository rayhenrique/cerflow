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
<body class="h-full font-sans text-slate-900 antialiased" x-data="{ mobileMenuOpen: false }">
    @php
        $versaoSistema = app(\App\Services\VersionService::class)->versaoAtual();
        $emConfiguracoes = request()->routeIs('configuracoes.*');
        $secaoConfigAtual = request()->route('secao');
    @endphp

    <!-- Sistema Global de Toasts (Sucesso, Erro, Alerta, Info) -->
    <x-toast-notifications />

    <div class="min-h-full flex">
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
            <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between border-b border-slate-200 bg-white/90 px-6 backdrop-blur-xs">
                <div class="flex items-center gap-3">
                    <h1 class="text-sm font-semibold text-slate-900">{{ $header ?? 'Painel Operacional CER' }}</h1>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <button type="button"
                            onclick="Livewire.dispatch('abrir-modal-versao')"
                            class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-2.5 py-1 font-mono font-semibold text-teal-700 border border-teal-200 hover:bg-teal-100 transition"
                            title="Clique para ver as novidades desta versão">
                        ✨ v{{ $versaoSistema }} — Novidades
                    </button>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        CNES {{ \App\Services\BpaiExportService::CNES_PADRAO }}
                    </span>
                </div>
            </header>

            <main class="flex-1 p-6">
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
