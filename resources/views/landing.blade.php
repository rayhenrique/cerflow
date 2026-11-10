<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CER Digital — Tecnologia a Serviço do Cuidado | Gestão Clínica, PEP & BPA-i</title>
    <meta name="description" content="Plataforma especializada para Centros Especializados em Reabilitação (CER II, III e IV) do SUS. Agenda operacional, prontuário multiprofissional, chamada de pacientes e BPA-i.">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50/60 font-sans text-slate-900 antialiased selection:bg-teal-600 selection:text-white"
      x-data="{ mobileNav: false }">

    @php
        $versaoSistema = app(\App\Services\VersionService::class)->versaoAtual();
        $configEstabelecimento = \App\Models\ConfiguracaoEstabelecimento::atual();
    @endphp

    <!-- ===================================================================== -->
    <!-- CABEÇALHO INSTITUCIONAL / NAVEGAÇÃO                                   -->
    <!-- ===================================================================== -->
    <header class="sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/90 backdrop-blur-md transition-all">
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Logotipo e Identidade -->
            <a href="{{ url('/') }}" class="group flex items-center gap-3 transition">
                @if ($configEstabelecimento->logo_path)
                    <img src="{{ $configEstabelecimento->logoUrl() }}"
                         alt="Logo do estabelecimento"
                         class="h-10 max-w-[180px] object-contain">
                @else
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-700 text-white font-bold text-base shadow-xs shadow-teal-900/10 ring-1 ring-teal-600/30 group-hover:bg-teal-600 transition">
                        <span>CD</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-base font-bold tracking-tight text-slate-900 leading-none">CER Digital</span>
                        <span class="text-[11px] font-medium text-slate-500 mt-0.5 tracking-tight">Reabilitação & SUS</span>
                    </div>
                @endif
            </a>

            <!-- Navegação Desktop -->
            <nav class="hidden md:flex items-center gap-8 text-xs font-semibold text-slate-600 tracking-wide">
                <a href="#apresentacao" class="hover:text-teal-700 transition">O Sistema</a>
                <a href="#funcionalidades" class="hover:text-teal-700 transition">Funcionalidades</a>
                <a href="#fluxo" class="hover:text-teal-700 transition">Como Funciona</a>
                <a href="#seguranca" class="hover:text-teal-700 transition">Segurança & LGPD</a>
            </nav>

            <!-- Ações / Acesso ao Sistema -->
            <div class="hidden sm:flex items-center gap-3">
                <a href="{{ route('painel.chamadas') }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-400 transition"
                   title="Abrir o Painel de Chamadas para TV em nova aba">
                    <svg class="h-3.5 w-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Painel TV</span>
                </a>

                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 transition">
                        <span>Acessar o Painel</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 transition">
                        <svg class="h-3.5 w-3.5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                        </svg>
                        <span>Entrar no Sistema</span>
                    </a>
                @endauth
            </div>

            <!-- Botão Menu Mobile -->
            <div class="flex items-center gap-2 md:hidden">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-teal-700 px-3 py-1.5 text-xs font-semibold text-white">
                        Painel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">
                        Entrar
                    </a>
                @endauth

                <button type="button"
                        @click="mobileNav = !mobileNav"
                        class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 focus:outline-none touch-target"
                        aria-label="Abrir menu de navegação">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menu Drawer Mobile -->
        <div x-show="mobileNav"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             style="display: none;"
             class="border-b border-slate-200 bg-white px-4 pt-3 pb-6 md:hidden shadow-lg">
            <div class="space-y-3">
                <a href="#apresentacao" @click="mobileNav = false" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">O Sistema</a>
                <a href="#funcionalidades" @click="mobileNav = false" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Funcionalidades</a>
                <a href="#fluxo" @click="mobileNav = false" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Como Funciona</a>
                <a href="#seguranca" @click="mobileNav = false" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Segurança & LGPD</a>
                <a href="{{ route('painel.chamadas') }}" target="_blank" @click="mobileNav = false" class="block rounded-lg px-3 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">📺 Painel TV (Salas de Espera)</a>
                
                <div class="pt-3 border-t border-slate-100">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-bold text-white shadow-xs">
                            Acessar o Painel Interno &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-xs">
                            Entrar no CER Digital &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- ================================================================= -->
        <!-- HERO PRINCIPAL                                                    -->
        <!-- ================================================================= -->
        <section class="relative overflow-hidden pt-12 pb-16 sm:pt-20 sm:pb-24 lg:pt-24 lg:pb-32 bg-linear-to-b from-white via-stone-50/50 to-slate-100/70 border-b border-slate-200">
            <!-- Elementos Geométricos Sutis de Fundo -->
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div class="absolute -top-40 right-1/4 h-[500px] w-[500px] rounded-full bg-teal-100/40 blur-3xl"></div>
                <div class="absolute -bottom-20 left-10 h-72 w-72 rounded-full bg-emerald-100/40 blur-2xl"></div>
                <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(#0d9488_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.04]"></div>
            </div>

            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-center">
                    
                    <!-- Lado Esquerdo: Mensagem Institucional & CTAs -->
                    <div class="lg:col-span-6 space-y-6">
                        <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3.5 py-1 text-xs font-semibold text-teal-800 ring-1 ring-inset ring-teal-600/20">
                            <span class="flex h-1.5 w-1.5 rounded-full bg-teal-600"></span>
                            <span>SUS / SIA • Centros Especializados em Reabilitação</span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 leading-[1.15]">
                            Tecnologia a serviço do cuidado.
                        </h1>

                        <p class="text-base sm:text-lg text-slate-600 font-normal leading-relaxed max-w-xl">
                            Organize atendimentos, acompanhe pacientes e simplifique a rotina da reabilitação em uma plataforma integrada.
                        </p>

                        <!-- Indicadores de Linhas de Cuidado -->
                        <div class="pt-2 flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-slate-700 font-medium border border-slate-200 shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-600"></span>
                                Física
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-slate-700 font-medium border border-slate-200 shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Intelectual / TEA
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-slate-700 font-medium border border-slate-200 shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Auditiva
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-slate-700 font-medium border border-slate-200 shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-sky-600"></span>
                                Visual
                            </span>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 transition cursor-pointer">
                                    <span>Acessar o Painel</span>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 transition cursor-pointer">
                                    <span>Acessar o CER Digital</span>
                                    <svg class="h-4 w-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                </a>
                            @endauth

                            <a href="#apresentacao"
                               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-400 transition cursor-pointer">
                                <span>Conhecer o sistema</span>
                                <span class="text-xs text-slate-400">↓</span>
                            </a>
                        </div>

                        <!-- Garantias Institucionais Comprovadas -->
                        <div class="pt-6 border-t border-slate-200/80 grid grid-cols-3 gap-3 text-left">
                            <div>
                                <span class="block text-lg font-bold text-slate-900">100% SUS</span>
                                <span class="block text-xs text-slate-500">Regras BPA-i SIA</span>
                            </div>
                            <div>
                                <span class="block text-lg font-bold text-teal-700">SHA-256</span>
                                <span class="block text-xs text-slate-500">Assinatura Eletrônica</span>
                            </div>
                            <div>
                                <span class="block text-lg font-bold text-slate-900">Multi</span>
                                <span class="block text-xs text-slate-500">Prontuário Integrado</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lado Direito: Demonstração Estilizada da Interface Real -->
                    <div class="lg:col-span-6">
                        <div class="relative mx-auto max-w-lg lg:max-w-none">
                            <!-- Moldura de Janela do Sistema com Efeito Realista -->
                            <div class="overflow-hidden rounded-2xl border border-slate-800/80 bg-slate-900 text-slate-100 shadow-2xl shadow-teal-950/20">
                                
                                <!-- Barra Superior da Janela -->
                                <div class="flex items-center justify-between border-b border-slate-800 bg-slate-950/90 px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex gap-1.5">
                                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500/80"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500/80"></span>
                                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500/80"></span>
                                        </div>
                                        <span class="ml-2 font-mono text-[11px] text-slate-400">cer.saude.gov.br • Ambiente Seguro</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-teal-950 px-2 py-0.5 font-mono text-[10px] text-teal-300 border border-teal-800">
                                            v{{ $versaoSistema }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Conteúdo Interno da Demonstração -->
                                <div class="p-4 sm:p-5 space-y-3.5 bg-slate-900/95 font-sans">
                                    
                                    <!-- Componente 1: Banner de Chamada na TV Ativa -->
                                    <div class="rounded-xl border border-teal-500/40 bg-teal-950/60 p-3.5 flex items-center justify-between gap-3 shadow-sm">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500 text-slate-950 font-bold">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-400">Painel TV • Sala de Espera</span>
                                                    <span class="h-1.5 w-1.5 rounded-full bg-teal-400 animate-ping"></span>
                                                </div>
                                                <p class="text-xs sm:text-sm font-bold text-white truncate">LUCAS R. S. &rarr; Consultório 02</p>
                                            </div>
                                        </div>
                                        <span class="rounded bg-teal-900/80 px-2 py-1 text-[10px] font-mono text-teal-300 border border-teal-700/60 shrink-0">
                                            Voz Ativa 🔊
                                        </span>
                                    </div>

                                    <!-- Componente 2: Card da Grade Semanal e Presença -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        
                                        <!-- Slot de Atendimento -->
                                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-3 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="font-mono text-[11px] font-bold text-teal-300">08:00 - 08:30</span>
                                                <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-300 border border-emerald-500/30">
                                                    ✓ Presente
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-white truncate">Beatriz M. (8 anos)</p>
                                                <p class="text-[11px] text-slate-400">Sala de Estimulação Precoce</p>
                                            </div>
                                            <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-800">
                                                <span>Fisioterapia + Fono</span>
                                                <span class="text-teal-400">Compartilhado</span>
                                            </div>
                                        </div>

                                        <!-- Slot 2 -->
                                        <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-3 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="font-mono text-[11px] font-bold text-slate-400">08:30 - 09:00</span>
                                                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-300 border border-slate-700">
                                                    Agendado
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-200 truncate">Carlos Eduardo F.</p>
                                                <p class="text-[11px] text-slate-400">Ginásio de Adulto • Fisioterapia</p>
                                            </div>
                                            <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-800">
                                                <span>Dr. Rafael Alencar</span>
                                                <span class="text-slate-400">Individual</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Componente 3: Prontuário Multiprofissional com Assinatura SHA-256 -->
                                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-3.5 space-y-2 text-xs">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-bold">PEP</span>
                                                <span class="font-bold text-slate-200">Evolução Clínica Multiprofissional</span>
                                            </div>
                                            <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-[10px] text-slate-400 border border-slate-700">
                                                Trava legal 24h
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 leading-relaxed italic">
                                            "Paciente realizou treino de marcha com órtese suropodálica; boa tolerância ao exercício e evolução de equilíbrio estático..."
                                        </p>
                                        <div class="flex flex-wrap items-center justify-between gap-1 pt-1 border-t border-slate-800/80 text-[10px] text-slate-400">
                                            <span class="text-slate-300 font-medium">Dra. Mariana Costa • CBO 2239-05</span>
                                            <span class="font-mono text-teal-400">SHA-256: 8f4a2b1c...</span>
                                        </div>
                                    </div>

                                    <!-- Componente 4: Pré-Auditoria BPA-i -->
                                    <div class="rounded-lg bg-slate-950 px-3 py-2 border border-slate-800 flex items-center justify-between text-[11px]">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                            <span class="text-slate-300">Pré-auditoria BPA-i:</span>
                                            <span class="font-bold text-white">Pronto para exportação</span>
                                        </div>
                                        <span class="font-mono text-emerald-400 font-bold">100% íntegro</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- APRESENTAÇÃO DO SISTEMA & DESAFIOS OPERACIONAIS                   -->
        <!-- ================================================================= -->
        <section id="apresentacao" class="py-16 sm:py-24 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl space-y-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Cuidado Integral em Reabilitação</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Construído para a realidade complexa dos Centros Especializados do SUS.
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        Os Centros Especializados em Reabilitação operam de forma muito diferente de clínicas ambulatoriais comuns. Múltiplos terapeutas atendem os mesmos pacientes, planos terapêuticos exigem sessões simultâneas ou compartilhadas, faltas consecutivas desestruturam o processo de reabilitação e o faturamento do BPA-i exige conformidade rigorosa com a tabela SIGTAP.
                    </p>
                </div>

                <!-- 5 Pilares Fundamentais do CER Digital -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    
                    <!-- Pilar 1 -->
                    <div class="rounded-2xl border border-slate-200 bg-stone-50/50 p-6 space-y-3 hover:border-teal-500/40 hover:bg-white transition shadow-2xs">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-100 text-teal-800 font-bold">
                            📅
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Organização da rotina</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Gestão visual de grade por salas, consultórios e ginásios terapêuticos. Visualize turnos, horários de 30 minutos e espelhamento completo da equipe sem conflito de espaço.
                        </p>
                    </div>

                    <!-- Pilar 2 -->
                    <div class="rounded-2xl border border-slate-200 bg-stone-50/50 p-6 space-y-3 hover:border-teal-500/40 hover:bg-white transition shadow-2xs">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 font-bold">
                            ✅
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Integração recepção e consultório</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            A recepção confirma a chegada com 1 clique e os profissionais visualizam instantaneamente a fila de espera da sua sala, reduzindo tempo ocioso e filas nos corredores.
                        </p>
                    </div>

                    <!-- Pilar 3 -->
                    <div class="rounded-2xl border border-slate-200 bg-stone-50/50 p-6 space-y-3 hover:border-teal-500/40 hover:bg-white transition shadow-2xs">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-800 font-bold">
                            🩺
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Prontuário multiprofissional</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Histórico clínico unificado em linha do tempo. Fisioterapeutas, fonoaudiólogos, psicólogos e terapeutas ocupacionais compartilham o acompanhamento do mesmo paciente.
                        </p>
                    </div>

                    <!-- Pilar 4 -->
                    <div class="rounded-2xl border border-slate-200 bg-stone-50/50 p-6 space-y-3 hover:border-teal-500/40 hover:bg-white transition shadow-2xs">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800 font-bold">
                            📺
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Acompanhamento dos atendimentos</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Chamada de pacientes na televisão da sala de espera com sintetizador de voz acessível, controle de chamadas repetidas e privacidade configurável conforme a LGPD.
                        </p>
                    </div>

                    <!-- Pilar 5 -->
                    <div class="rounded-2xl border border-slate-200 bg-stone-50/50 p-6 space-y-3 hover:border-teal-500/40 hover:bg-white transition shadow-2xs md:col-span-2 lg:col-span-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-800 text-white font-bold">
                            📄
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Organização da produção SUS (BPA-i)</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Motor de pré-auditoria contínua que confere automaticamente o número do Cartão SUS (Módulo 11), CID-10 e vinculação CBO com a tabela SIGTAP, eliminando retrabalho no fechamento mensal da remessa magnética.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- FUNCIONALIDADES REAIS DO CER DIGITAL                              -->
        <!-- ================================================================= -->
        <section id="funcionalidades" class="py-16 sm:py-24 bg-stone-50/60 border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Recursos Efetivos da Plataforma</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Tudo que o CER necessita, do acolhimento ao faturamento.
                    </h2>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Funcionalidades desenvolvidas especificamente para o fluxo de trabalho de fisioterapia, fonoaudiologia, terapia ocupacional, psicologia, serviço social e faturamento ambulatorial.
                    </p>
                </div>

                <!-- Grid Visual Assimétrico de Módulos Reais -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- 1. Agenda Operacional -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-xs">
                        <div class="space-y-3">
                            <span class="inline-flex rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700 border border-teal-200">
                                Fluxo Diário
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">Agenda Operacional Consolidada</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Alternância intuitiva entre visualização diária operacional por turnos (Manhã, Tarde, Noite), grade semanal de salas e mapa mensal de ocupação da unidade.
                            </p>
                        </div>
                        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">✓ Alocação por sala e profissional</li>
                            <li class="flex items-center gap-2">✓ Atendimentos compartilhados e em grupo</li>
                            <li class="flex items-center gap-2">✓ Bloqueio preventivo de conflitos de horário</li>
                        </ul>
                    </div>

                    <!-- 2. Recepção & Check-in -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-xs">
                        <div class="space-y-3">
                            <span class="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                                Recepção Ágil
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">Recepção & Controle de Presença</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Registro imediato de presença de pacientes, controle de horários de chegada e monitoramento automático de faltas justificadas e absenteísmo.
                            </p>
                        </div>
                        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">✓ Check-in de chegada em 1 clique</li>
                            <li class="flex items-center gap-2">✓ Alerta automático: 3 faltas seguidas ou 5 no mês</li>
                            <li class="flex items-center gap-2">✓ Atualização em tempo real para os terapeutas</li>
                        </ul>
                    </div>

                    <!-- 3. Prontuário Multiprofissional -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-xs">
                        <div class="space-y-3">
                            <span class="inline-flex rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 border border-indigo-200">
                                PEP Multi
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">Prontuário com Assinatura Segura</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Registro clínico multiprofissional com preenchimento de queixa, conduta e intercorrências. Assinatura eletrônica com PIN individual e carimbo criptográfico.
                            </p>
                        </div>
                        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">✓ Assinatura eletrônica com hash SHA-256</li>
                            <li class="flex items-center gap-2">✓ Trava legal de imutabilidade após 24 horas</li>
                            <li class="flex items-center gap-2">✓ Linha do tempo integrada entre especialidades</li>
                        </ul>
                    </div>

                    <!-- 4. Chamada de Pacientes na TV -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-xs">
                        <div class="space-y-3">
                            <span class="inline-flex rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-200">
                                TV & Acessibilidade
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">Painel de TV com Pareamento Seguro</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Conexão de televisores das salas de espera através de código numérico de pareamento, sem necessidade de login ou URLs expostas na tela pública.
                            </p>
                        </div>
                        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">✓ Síntese de voz com pronúncia do nome e sala</li>
                            <li class="flex items-center gap-2">✓ Sinal sonoro configurável e aviso em tela cheia</li>
                            <li class="flex items-center gap-2">✓ Credencial HttpOnly exclusiva por aparelho</li>
                        </ul>
                    </div>

                    <!-- 5. Produção Ambulatorial BPA-i -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-xs">
                        <div class="space-y-3">
                            <span class="inline-flex rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-800 border border-teal-200">
                                SIA / SUS
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">Faturamento & Pré-Auditoria BPA-i</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Geração da produção a partir das evoluções assinadas com verificação preventiva antes do envio do arquivo magnético posicional.
                            </p>
                        </div>
                        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">✓ Validação Módulo 11 do Cartão SUS</li>
                            <li class="flex items-center gap-2">✓ Matriz de compatibilidade CBO x SIGTAP</li>
                            <li class="flex items-center gap-2">✓ Exportação em formato de remessa .TXT (#BPA#)</li>
                        </ul>
                    </div>

                    <!-- 6. Integração e-SUS APS PEC & Indicadores -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-xs">
                        <div class="space-y-3">
                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 border border-slate-300">
                                Interoperabilidade
                            </span>
                            <h3 class="text-lg font-bold text-slate-900">Integração e-SUS PEC & Gestão</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Conexão somente-leitura com a base de dados do e-SUS APS PEC para localização de dados cadastrais e painel com indicadores de ocupação e assiduidade.
                            </p>
                        </div>
                        <ul class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">✓ Sincronização segura de dados de pacientes</li>
                            <li class="flex items-center gap-2">✓ Indicadores de assiduidade e produção no mês</li>
                            <li class="flex items-center gap-2">✓ Ocupação por sala terapêutica e modalidade</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- FLUXO DE FUNCIONAMENTO (O CICLO DO CUIDADO NO CER)                -->
        <!-- ================================================================= -->
        <section id="fluxo" class="py-16 sm:py-24 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Etapas do Processo</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Como o CER Digital conecta cada setor da unidade.
                    </h2>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Uma cadeia contínua de cuidado que elimina duplicidade de anotações e garante conformidade em cada atendimento.
                    </p>
                </div>

                <!-- Sequência Visual dos 5 Passos -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-5 gap-4 relative">
                    
                    <!-- Passo 1 -->
                    <div class="rounded-xl border border-slate-200 bg-stone-50/60 p-5 space-y-2 relative">
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-white font-mono font-bold text-xs">1</span>
                            <span class="text-[10px] font-mono text-slate-400">Planejamento</span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm">Agendamento</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Registro do horário na grade semanal por sala, profissional e modalidade (individual ou compartilhada).
                        </p>
                    </div>

                    <!-- Passo 2 -->
                    <div class="rounded-xl border border-slate-200 bg-stone-50/60 p-5 space-y-2 relative">
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-700 text-white font-mono font-bold text-xs">2</span>
                            <span class="text-[10px] font-mono text-slate-400">Recepção</span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm">Check-in</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Confirmação da chegada do paciente na portaria/recepção, alimentando instantaneamente a fila do terapeuta.
                        </p>
                    </div>

                    <!-- Passo 3 -->
                    <div class="rounded-xl border border-slate-200 bg-stone-50/60 p-5 space-y-2 relative">
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-600 text-white font-mono font-bold text-xs">3</span>
                            <span class="text-[10px] font-mono text-slate-400">Consultório</span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm">Chamada TV</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            O profissional chama o paciente pelo sistema com aviso sonoro e visual na televisão da sala de espera.
                        </p>
                    </div>

                    <!-- Passo 4 -->
                    <div class="rounded-xl border border-slate-200 bg-stone-50/60 p-5 space-y-2 relative">
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-700 text-white font-mono font-bold text-xs">4</span>
                            <span class="text-[10px] font-mono text-slate-400">Clínica</span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm">Evolução & PIN</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Registro da conduta clínica no prontuário, com assinatura eletrônica por PIN individual e selo SHA-256.
                        </p>
                    </div>

                    <!-- Passo 5 -->
                    <div class="rounded-xl border border-slate-200 bg-stone-50/60 p-5 space-y-2 relative">
                        <div class="flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-700 text-white font-mono font-bold text-xs">5</span>
                            <span class="text-[10px] font-mono text-slate-400">Faturamento</span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-sm">Remessa BPA-i</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Pré-auditoria automática dos procedimentos realizados e exportação magnética para o faturamento SIA/SUS.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- SEGURANÇA, AUDITORIA & CONFORMIDADE LGPD                          -->
        <!-- ================================================================= -->
        <section id="seguranca" class="py-16 sm:py-24 bg-slate-900 text-slate-100 border-b border-slate-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    
                    <div class="lg:col-span-6 space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-teal-400">Segurança da Informação</span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            Privacidade, integridade dos registros e proteção de dados em saúde.
                        </h2>
                        <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                            O CER Digital foi projetado observando os princípios da Lei Geral de Proteção de Dados (LGPD) e as exigências éticas dos conselhos profissionais de saúde (CREFITO, CRFa, CRP, CRM e CRESS).
                        </p>

                        <div class="space-y-4 pt-2">
                            <div class="flex items-start gap-3">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-400 font-bold text-xs">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">Controle de acesso por perfil (RBAC)</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Permissões segregadas para Recepção, Terapeutas, Faturamento e Coordenação. Cada setor acessa apenas as informações indispensáveis ao seu trabalho.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-400 font-bold text-xs">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">Trilha de auditoria imutável</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Registro automático de data, horário, endereço IP e identificador do usuário em todas as consultas ao prontuário do paciente (PEP) e ações sensíveis.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-400 font-bold text-xs">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">Assinatura eletrônica e trava de 24 horas</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">As evoluções clínicas são assinadas com PIN exclusivo do profissional e recebem um selo criptográfico SHA-256 inviolável, tornando-se imutáveis após 24 horas.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-400 font-bold text-xs">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">Pareamento de TVs sem senhas expostas</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Aparelhos de Smart TV na sala de espera recebem autorização individual controlada pela coordenação por cookie seguro HttpOnly, sem armazenar logins de usuários.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Informativo Institucional -->
                    <div class="lg:col-span-6">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-6 sm:p-8 space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">Compromisso com a conformidade</h3>
                                    <p class="text-xs text-slate-400">Padrão institucional para saúde pública</p>
                                </div>
                            </div>

                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                                Todas as ferramentas operacionais do CER Digital foram desenhadas para que a unidade atue em estrita conformidade com os manuais do Ministério da Saúde, layout do BPA magnético (SIA/SUS) e resoluções vigentes de prontuário eletrônico.
                            </p>

                            <div class="rounded-xl bg-slate-900 p-4 border border-slate-800/80 space-y-2 text-xs">
                                <div class="flex items-center justify-between text-slate-400">
                                    <span>Algoritmo de validação de CNS:</span>
                                    <span class="font-mono text-teal-300 font-bold">Módulo 11 Oficial</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-400">
                                    <span>Carimbo de assinatura clínica:</span>
                                    <span class="font-mono text-teal-300 font-bold">SHA-256 + Timestamp</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-400">
                                    <span>Proteção de TV na recepção:</span>
                                    <span class="font-mono text-teal-300 font-bold">Ocultação opcional de sobrenome</span>
                                </div>
                            </div>

                            <div class="pt-2 flex items-center justify-between">
                                <span class="text-xs text-slate-400">Versão atual do sistema:</span>
                                <span class="rounded bg-slate-900 px-2 py-1 font-mono text-xs text-teal-400 border border-slate-800">
                                    v{{ $versaoSistema }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ===================================================================== -->
    <!-- RODAPÉ INSTITUCIONAL                                                  -->
    <!-- ===================================================================== -->
    <footer class="bg-slate-950 text-slate-400 text-xs border-t border-slate-800/80">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <!-- Coluna 1: Identidade -->
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-white font-bold text-xs shadow-xs">
                            CD
                        </div>
                        <span class="text-base font-bold text-white tracking-tight">CER Digital</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed max-w-md">
                        Sistema proprietário de Gestão Clínica, Grade Semanal, PEP Multiprofissional e Faturamento BPA-i (SIA/SUS) para Centros Especializados em Reabilitação.
                    </p>
                    <p class="text-slate-500 text-[11px]">
                        Desenvolvido pela <strong class="text-slate-300">KL Tecnologia</strong> (<a href="https://kltecnologia.com" target="_blank" class="hover:text-teal-400 underline">kltecnologia.com</a>).
                    </p>
                </div>

                <!-- Coluna 2: Navegação Rápida -->
                <div class="space-y-2">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-200">Navegação</h5>
                    <ul class="space-y-1.5 text-xs">
                        <li><a href="#apresentacao" class="hover:text-white transition">O Sistema</a></li>
                        <li><a href="#funcionalidades" class="hover:text-white transition">Funcionalidades</a></li>
                        <li><a href="#fluxo" class="hover:text-white transition">Como Funciona</a></li>
                        <li><a href="#seguranca" class="hover:text-white transition">Segurança & LGPD</a></li>
                    </ul>
                </div>

                <!-- Coluna 3: Acesso ao Sistema -->
                <div class="space-y-2">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-200">Acesso Operacional</h5>
                    <ul class="space-y-1.5 text-xs">
                        <li><a href="{{ route('login') }}" class="hover:text-teal-400 transition font-medium">Acesso de Colaboradores</a></li>
                        <li><a href="{{ route('painel.chamadas') }}" target="_blank" class="hover:text-teal-400 transition">Painel de TV (Recepção)</a></li>
                        <li><span class="text-slate-500">Versão: v{{ $versaoSistema }}</span></li>
                    </ul>
                </div>
            </div>

            <!-- Faixa Inferior de Copyright e Privacidade -->
            <div class="mt-8 pt-8 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-slate-500">
                <p>© {{ date('Y') }} CER Digital • Todos os direitos reservados. Sistema de uso exclusivo para instituições autorizadas.</p>
                <div class="flex items-center gap-4">
                    <span>Privacidade em Saúde (LGPD)</span>
                    <span>•</span>
                    <span>SIA/SUS BPA-i</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>

