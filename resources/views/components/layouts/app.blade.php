<!DOCTYPE html>
@php($configuracaoEstabelecimento = \App\Models\ConfiguracaoEstabelecimento::atual())
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CER Digital — Gestão de Reabilitação e BPA-i' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-900 antialiased"
      x-data="{
          mobileMenuOpen: false,
          sidebarCollapsed: (localStorage.getItem('cerdigital_sidebar_collapsed') ?? localStorage.getItem('cerflow_sidebar_collapsed')) === 'true',
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('cerdigital_sidebar_collapsed', this.sidebarCollapsed);
          }
      }"
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
                        @if ($configuracaoEstabelecimento->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuracaoEstabelecimento->logo_path) }}" alt="Logo do estabelecimento" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5">
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
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white cursor-pointer"
                            aria-label="Fechar menu">
                        ✕
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto space-y-1 px-3 py-4 text-sm" x-data="{ menuConfigMobile: true }">
                    @auth
                        @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']))
                            <a href="{{ route('dashboard') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                                </svg>
                                <span>Painel Inicial</span>
                            </a>

                            <a href="{{ route('grade.semanal') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('grade.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('grade.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                </svg>
                                <span>Grade Semanal</span>
                            </a>
                        @endif

                        @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'coordenacao']))
                            <a href="{{ route('recepcao.checkin') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('recepcao.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('recepcao.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Recepção & Check-in</span>
                            </a>
                        @endif

                        @if(auth()->user()->hasAnyRole(['terapeuta', 'coordenacao']))
                            <a href="{{ route('prontuario.index') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('prontuario.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('prontuario.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                                <span>Prontuário (PEP Multi)</span>
                            </a>
                        @endif

                        @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                            <a href="{{ route('faturamento.bpai') }}"
                               @click="mobileMenuOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('faturamento.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('faturamento.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                                <span>Faturamento BPA-i</span>
                            </a>
                        @endif

                        <div class="pt-4 mt-4 border-t border-slate-800/80 space-y-1">
                            <button type="button"
                                    @click="menuConfigMobile = !menuConfigMobile"
                                    class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider cursor-pointer {{ $emConfiguracoes ? 'text-teal-400 bg-slate-800/50' : 'text-slate-400 hover:bg-slate-800/40 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>Configurações</span>
                                </span>
                                <span class="text-[10px] transition-transform duration-150" :class="menuConfigMobile ? 'rotate-90' : ''">▸</span>
                            </button>

                            <div x-show="menuConfigMobile" class="space-y-1 pl-4 border-l border-slate-800 ml-3 text-xs">
                                @if(auth()->user()->hasAnyRole(['recepcao', 'coordenacao']))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'pacientes']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'pacientes' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                        </svg>
                                        <span>Pacientes SUS</span>
                                    </a>
                                @endif

                                @if(auth()->user()->hasRole('coordenacao'))

                                <a href="{{ route('configuracoes.secao', ['secao' => 'estabelecimento']) }}"
                                   @click="mobileMenuOpen = false"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 font-medium transition {{ $secaoConfigAtual === 'estabelecimento' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <span>Identificação do Estabelecimento</span>
                                </a>
                                <a href="{{ route('configuracoes.secao', ['secao' => 'salas']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'salas' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                                        </svg>
                                        <span>Salas & Espaços</span>
                                    </a>

                                    <a href="{{ route('configuracoes.secao', ['secao' => 'profissionais']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'profissionais' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                                        </svg>
                                        <span>Equipe & Perfis</span>
                                    </a>
                                @endif

                                @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'sigtap']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'sigtap' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5"/>
                                        </svg>
                                        <span>Tabela SIGTAP</span>
                                    </a>
                                @endif

                                @if(auth()->user()->hasRole('coordenacao'))
                                    <a href="{{ route('configuracoes.secao', ['secao' => 'auditoria']) }}"
                                       @click="mobileMenuOpen = false"
                                       class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'auditoria' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                        </svg>
                                        <span>Auditoria LGPD</span>
                                    </a>
                                @endif

                                <a href="{{ route('configuracoes.secao', ['secao' => 'perfil']) }}"
                                   @click="mobileMenuOpen = false"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ $secaoConfigAtual === 'perfil' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                    </svg>
                                    <span>Minha Conta & PIN</span>
                                </a>
                            </div>

                            <div class="pt-2">
                                <a href="{{ route('ajuda.sobre') }}"
                                   @click="mobileMenuOpen = false"
                                   class="group flex items-center justify-between rounded-lg px-3 py-2.5 font-medium transition {{ request()->routeIs('ajuda.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                    <span class="flex items-center gap-3">
                                        <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('ajuda.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                                        </svg>
                                        <span>Ajuda & Sobre</span>
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
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white cursor-pointer">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                    </svg>
                                    <span>Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Sidebar Desktop com Toggle Retrátil (lg:w-64 <-> lg:w-20) -->
        <aside :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'"
               class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 bg-slate-900 text-slate-300 border-r border-slate-800 transition-all duration-200 z-30">
            <div class="flex h-16 shrink-0 items-center justify-between px-4 border-b border-slate-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0" :class="sidebarCollapsed ? 'mx-auto' : ''">
                    @if ($configuracaoEstabelecimento->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($configuracaoEstabelecimento->logo_path) }}" alt="Logo do estabelecimento" class="h-9 w-9 shrink-0 rounded-lg bg-white object-contain p-0.5">
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
                        title="Recolher menu lateral">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto space-y-1 px-2.5 py-4 text-sm" x-data="{ menuConfigAberto: true }">
                @auth
                    @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'faturamento', 'coordenacao']))
                        <a href="{{ route('dashboard') }}"
                           title="Painel Inicial"
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'px-3'"
                           class="group flex items-center gap-3 rounded-lg py-2.5 font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate">Painel Inicial</span>
                        </a>

                        <a href="{{ route('grade.semanal') }}"
                           title="Grade Semanal"
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'px-3'"
                           class="group flex items-center gap-3 rounded-lg py-2.5 font-medium transition {{ request()->routeIs('grade.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('grade.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate">Grade Semanal</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['recepcao', 'terapeuta', 'coordenacao']))
                        <a href="{{ route('recepcao.checkin') }}"
                           title="Recepção & Check-in"
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'px-3'"
                           class="group flex items-center gap-3 rounded-lg py-2.5 font-medium transition {{ request()->routeIs('recepcao.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('recepcao.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate">Recepção & Check-in</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['terapeuta', 'coordenacao']))
                        <a href="{{ route('prontuario.index') }}"
                           title="Prontuário (PEP Multi)"
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'px-3'"
                           class="group flex items-center gap-3 rounded-lg py-2.5 font-medium transition {{ request()->routeIs('prontuario.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('prontuario.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate">Prontuário (PEP Multi)</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                        <a href="{{ route('faturamento.bpai') }}"
                           title="Faturamento BPA-i"
                           :class="sidebarCollapsed ? 'justify-center px-2' : 'px-3'"
                           class="group flex items-center gap-3 rounded-lg py-2.5 font-medium transition {{ request()->routeIs('faturamento.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('faturamento.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate">Faturamento BPA-i</span>
                        </a>
                    @endif

                    <!-- Menu Expansível de Configurações com Subitens e Ícones -->
                    <div class="pt-4 mt-4 border-t border-slate-800/80 space-y-1">
                        <button type="button"
                                @click="if (sidebarCollapsed) { toggleSidebar(); menuConfigAberto = true; } else { menuConfigAberto = !menuConfigAberto; }"
                                title="Configurações"
                                :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-3'"
                                class="w-full flex items-center rounded-lg py-2 text-xs font-bold uppercase tracking-wider cursor-pointer {{ $emConfiguracoes ? 'text-teal-400 bg-slate-800/50' : 'text-slate-400 hover:bg-slate-800/40 hover:text-white' }}">
                            <span class="flex items-center gap-3">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed">Configurações</span>
                            </span>
                            <span x-show="!sidebarCollapsed" class="text-[10px] transition-transform duration-150" :class="menuConfigAberto ? 'rotate-90' : ''">▸</span>
                        </button>

                        <div x-show="menuConfigAberto"
                             :class="sidebarCollapsed ? 'space-y-1' : 'space-y-0.5 pl-3 border-l border-slate-800 ml-3'"
                             class="text-xs">
                            @if(auth()->user()->hasAnyRole(['recepcao', 'coordenacao']))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'pacientes']) }}"
                                   title="Pacientes SUS"
                                   :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                                   class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'pacientes' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Pacientes SUS</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasRole('coordenacao'))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'estabelecimento']) }}"
                                   title="Identificação do Estabelecimento"
                                   :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                                   class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'estabelecimento' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4.5h18v15H3zM7.5 9h9M7.5 12h9M7.5 15h5"/></svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Identificação do Estabelecimento</span>
                                </a>
                                <a href="{{ route('configuracoes.secao', ['secao' => 'salas']) }}"
                                   title="Salas & Espaços"
                                   :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                                   class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'salas' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Salas & Espaços</span>
                                </a>

                                <a href="{{ route('configuracoes.secao', ['secao' => 'profissionais']) }}"
                                   title="Equipe & Perfis"
                                   :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                                   class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'profissionais' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Equipe & Perfis</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasAnyRole(['faturamento', 'coordenacao']))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'sigtap']) }}"
                                   title="Tabela SIGTAP x CBO"
                                   :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                                   class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'sigtap' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Tabela SIGTAP x CBO</span>
                                </a>
                            @endif

                            @if(auth()->user()->hasRole('coordenacao'))
                                <a href="{{ route('configuracoes.secao', ['secao' => 'auditoria']) }}"
                                   title="Auditoria LGPD"
                                   :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                                   class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'auditoria' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Auditoria LGPD</span>
                                </a>
                            @endif

                            <a href="{{ route('configuracoes.secao', ['secao' => 'perfil']) }}"
                               title="Minha Conta & PIN"
                               :class="sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-1.5'"
                               class="flex items-center gap-2.5 rounded-lg font-medium transition {{ $secaoConfigAtual === 'perfil' ? 'bg-slate-800 text-teal-400 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="truncate">Minha Conta & PIN</span>
                            </a>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('ajuda.sobre') }}"
                               title="Ajuda & Sobre"
                               :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-3'"
                               class="group flex items-center rounded-lg py-2.5 font-medium transition {{ request()->routeIs('ajuda.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('ajuda.*') ? 'text-teal-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" class="truncate">Ajuda & Sobre</span>
                                </span>
                                <span x-show="!sidebarCollapsed" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono text-teal-400 border border-slate-700">
                                    v{{ $versaoSistema }}
                                </span>
                            </a>
                        </div>
                    </div>
                @endauth
            </nav>

            @auth
                <div class="border-t border-slate-800 p-3">
                    <div class="flex items-center" :class="sidebarCollapsed ? 'flex-col gap-2 justify-center' : 'justify-between gap-2'">
                        <div x-show="!sidebarCollapsed" class="truncate">
                            <p class="text-xs font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-teal-400 uppercase tracking-wider font-mono mt-0.5">
                                {{ auth()->user()->getRoleNames()->first() ?? 'usuário' }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    title="Sair do Sistema"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-md bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                </svg>
                                <span x-show="!sidebarCollapsed">Sair</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </aside>

        <!-- Conteúdo Principal -->
        <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'"
             class="flex-1 flex flex-col min-w-0 transition-all duration-200">
            <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-slate-200 bg-white/95 px-3 sm:px-6 backdrop-blur-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <!-- Botão Hamburger Mobile/Tablet (< lg) -->
                    <button type="button"
                            @click="mobileMenuOpen = true"
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
                            :title="sidebarCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'">
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
