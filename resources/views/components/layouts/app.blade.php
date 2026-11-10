<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CER Digital — Gestão de Reabilitação e BPA-i' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $versaoSistema = app(\App\Services\VersionService::class)->versaoAtual();
    $configuracaoEstabelecimento = \App\Models\ConfiguracaoEstabelecimento::atual();
    $navigationMenu = app(\App\Support\NavigationMenu::class);
    /** @var \App\Models\User|null $currentUser */
    $currentUser = auth()->user();
    $menuGrupos = $navigationMenu->gruposParaUsuario($currentUser);
    $grupoAtivoId = $navigationMenu->grupoAtivoId(request(), $currentUser);
    $estadosIniciais = $navigationMenu->estadoInicialGrupos(request(), $currentUser);
@endphp
<body class="h-full font-sans text-slate-900 antialiased"
      x-data="{
          mobileMenuOpen: false,
          sidebarCollapsed: (localStorage.getItem('cerdigital_sidebar_collapsed') ?? localStorage.getItem('cerflow_sidebar_collapsed')) === 'true',
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('cerdigital_sidebar_collapsed', this.sidebarCollapsed);
          },
          gruposAbertos: (() => {
              let saved = {};
              try {
                  saved = JSON.parse(localStorage.getItem('cerdigital_menu_groups') || '{}');
              } catch (e) {}
              const defaults = @js($estadosIniciais);
              const state = Object.assign({}, defaults, saved);
              @if ($grupoAtivoId)
                  state['{{ $grupoAtivoId }}'] = true;
              @endif
              return state;
          })(),
          toggleGrupo(id) {
              this.gruposAbertos[id] = !this.gruposAbertos[id];
              try {
                  localStorage.setItem('cerdigital_menu_groups', JSON.stringify(this.gruposAbertos));
              } catch (e) {}
          },
          isGrupoAberto(id) {
              return !!this.gruposAbertos[id];
          },
          init() {
              this.$watch('mobileMenuOpen', value => {
                  if (value) {
                      document.body.classList.add('overflow-hidden');
                  } else {
                      document.body.classList.remove('overflow-hidden');
                  }
              });
          }
      }"
      @keydown.escape.window="mobileMenuOpen = false">

    <!-- Sistema Global de Toasts (Sucesso, Erro, Alerta, Info) -->
    <x-toast-notifications />

    <div class="min-h-full flex">
        <!-- Drawer Mobile & Tablet (< lg) -->
        <div x-show="mobileMenuOpen"
             style="display: none;"
             class="fixed inset-0 z-50 flex lg:hidden"
             role="dialog"
             aria-modal="true"
             aria-label="Menu de navegação">
            <!-- Backdrop escuro com blur -->
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
                 class="relative flex w-full max-w-xs flex-1 flex-col bg-slate-900 text-slate-300 border-r border-slate-800 shadow-2xl">
                <!-- Cabeçalho do Drawer -->
                <div class="flex h-16 shrink-0 items-center justify-between px-5 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        @if ($configuracaoEstabelecimento->logo_path)
                            <img src="{{ $configuracaoEstabelecimento->logoUrl() }}" alt="Logo do estabelecimento" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5">
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-500 text-slate-950 font-bold text-sm shadow-xs">CD</span>
                        @endif
                        <div>
                            <span class="text-sm font-semibold tracking-tight text-white block leading-none">CER Digital</span>
                            <span class="text-[11px] text-slate-400 font-mono">SIA/SUS • v{{ $versaoSistema }}</span>
                        </div>
                    </div>
                    <button type="button"
                            @click="mobileMenuOpen = false"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer"
                            aria-label="Fechar menu">
                        ✕
                    </button>
                </div>

                <!-- Navegação Mobile com Rolagem Independente -->
                <nav class="flex-1 overflow-y-auto space-y-1 px-3 py-4 text-sm">
                    @auth
                        @foreach ($menuGrupos as $grupo)
                            <x-navigation.group :grupo="$grupo" :is-mobile="true" :user="$currentUser" />
                        @endforeach
                    @endauth
                </nav>

                <!-- Rodapé Fixo do Drawer Mobile com Perfil e Sair -->
                <x-navigation.user-footer :is-mobile="true" />
            </div>
        </div>

        <!-- Sidebar Desktop com Toggle Retrátil (lg:w-64 <-> lg:w-20) -->
        <aside :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'"
               class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 bg-slate-900 text-slate-300 border-r border-slate-800 transition-all duration-200 z-30">
            <!-- Cabeçalho da Sidebar Desktop -->
            <div class="flex h-16 shrink-0 items-center justify-between px-4 border-b border-slate-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0" :class="sidebarCollapsed ? 'mx-auto' : ''">
                    @if ($configuracaoEstabelecimento->logo_path)
                        <img src="{{ $configuracaoEstabelecimento->logoUrl() }}" alt="Logo do estabelecimento" class="h-9 w-9 shrink-0 rounded-lg bg-white object-contain p-0.5">
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500 text-slate-950 font-bold text-sm shadow-xs">CD</span>
                    @endif
                    <div x-show="!sidebarCollapsed" class="truncate">
                        <span class="text-sm font-semibold tracking-tight text-white block leading-none">CER Digital</span>
                        <span class="text-[11px] text-slate-400 font-mono">SIA/SUS • v{{ $versaoSistema }}</span>
                    </div>
                </a>

                <button type="button"
                        x-show="!sidebarCollapsed"
                        @click="toggleSidebar()"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white transition cursor-pointer"
                        title="Recolher menu lateral"
                        aria-label="Recolher menu lateral">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <!-- Navegação Desktop -->
            <nav class="flex-1 overflow-y-auto space-y-1 px-2.5 py-4 text-sm">
                @auth
                    @foreach ($menuGrupos as $grupo)
                        <x-navigation.group :grupo="$grupo" :is-mobile="false" :user="$currentUser" />
                    @endforeach
                @endauth
            </nav>

            <!-- Rodapé Desktop Fixo com Perfil e Sair -->
            <x-navigation.user-footer :is-mobile="false" />
        </aside>

        <!-- Conteúdo Principal -->
        <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'"
             class="flex-1 flex flex-col min-w-0 transition-all duration-200">
            <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-slate-200 bg-white/95 px-3 sm:px-6 backdrop-blur-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <!-- Botão Hamburger Mobile/Tablet (< lg) -->
                    <button type="button"
                            @click="mobileMenuOpen = true"
                            :aria-expanded="mobileMenuOpen"
                            class="lg:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none cursor-pointer"
                            aria-label="Abrir menu de navegação">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Botão Toggle da Sidebar Desktop (>= lg) -->
                    <button type="button"
                            @click="toggleSidebar()"
                            class="hidden lg:inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none cursor-pointer"
                            :title="sidebarCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'"
                            :aria-label="sidebarCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
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
                        CNES {{ $configuracaoEstabelecimento->cnes }}
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
