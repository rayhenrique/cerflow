<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso ao Sistema — CER Digital</title>
    <meta name="description" content="Ambiente de autenticação segura do CER Digital para colaboradores e profissionais de saúde.">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-stone-100 flex flex-col justify-center">

    @php
        $configuracaoEstabelecimento = \App\Models\ConfiguracaoEstabelecimento::atual();
        $versaoSistema = app(\App\Services\VersionService::class)->versaoAtual();
    @endphp

    <div class="min-h-full flex flex-col lg:flex-row">
        
        <!-- ================================================================= -->
        <!-- LADO ESQUERDO: ÁREA INSTITUCIONAL (DESKTOP)                       -->
        <!-- ================================================================= -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-5/12 bg-linear-to-br from-slate-950 via-slate-900 to-teal-950 text-white p-12 xl:p-16 flex-col justify-between relative overflow-hidden border-r border-slate-800">
            <!-- Padrão Geométrico Decorativo Discreto -->
            <div class="pointer-events-none absolute inset-0 -z-0 opacity-10">
                <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-teal-500 blur-3xl"></div>
                <div class="absolute -bottom-24 -right-24 h-96 w-96 rounded-full bg-emerald-500 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(#14b8a6_1px,transparent_1px)] [background-size:24px_24px]"></div>
            </div>

            <!-- Topo Institucional -->
            <div class="relative z-10 space-y-4">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center rounded-full bg-teal-500/20 px-3 py-1 text-xs font-semibold text-teal-300 ring-1 ring-inset ring-teal-500/30">
                        SUS / SIA • BPA-i
                    </span>
                    <span class="font-mono text-xs text-slate-400">v{{ $versaoSistema }}</span>
                </div>

                <div class="pt-2">
                    @if ($configuracaoEstabelecimento->logo_path)
                        <img src="{{ $configuracaoEstabelecimento->logoUrl() }}" 
                             alt="Logo do estabelecimento" 
                             class="max-h-20 max-w-xs object-contain object-left filter brightness-110 drop-shadow-sm">
                    @else
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-600 text-white font-bold text-lg shadow-sm">
                                CD
                            </div>
                            <div>
                                <h1 class="text-2xl font-extrabold tracking-tight text-white leading-none">CER Digital</h1>
                                <span class="text-xs text-teal-300 font-medium">Reabilitação Multiprofissional</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Centro: Proposta de Valor e Cuidado -->
            <div class="relative z-10 space-y-6 my-auto py-8">
                <div class="space-y-3">
                    <h2 class="text-2xl xl:text-3xl font-extrabold text-white tracking-tight leading-snug">
                        Tecnologia integrada para o cuidado multiprofissional.
                    </h2>
                    <p class="text-sm text-slate-300 leading-relaxed max-w-md">
                        Plataforma unificada para recepção, atendimento clínico e conformidade no faturamento ambulatorial SUS.
                    </p>
                </div>

                <!-- 3 Destaques Funcionais Reais -->
                <div class="space-y-3 pt-2 text-xs">
                    <div class="flex items-start gap-3 rounded-xl bg-slate-900/60 p-3.5 border border-slate-800">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-300 font-bold">✓</span>
                        <div>
                            <strong class="text-white block font-semibold">Grade Semanal & Alocação de Salas</strong>
                            <span class="text-slate-400">Sem sobreposição de horários ou conflito de ginásios.</span>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-xl bg-slate-900/60 p-3.5 border border-slate-800">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-300 font-bold">✓</span>
                        <div>
                            <strong class="text-white block font-semibold">Prontuário com Assinatura Eletrônica</strong>
                            <span class="text-slate-400">Autenticação com PIN exclusivo e hash SHA-256 inviolável.</span>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-xl bg-slate-900/60 p-3.5 border border-slate-800">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-teal-500/20 text-teal-300 font-bold">✓</span>
                        <div>
                            <strong class="text-white block font-semibold">Pré-Auditoria Preventiva BPA-i</strong>
                            <span class="text-slate-400">Validação automática de CNS e SIGTAP antes da remessa.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rodapé Institucional da Coluna -->
            <div class="relative z-10 pt-4 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                <span>Centro Especializado em Reabilitação</span>
                <span>KL Tecnologia</span>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- LADO DIREITO: FORMULÁRIO DE AUTENTICAÇÃO (DESKTOP & MOBILE)       -->
        <!-- ================================================================= -->
        <div class="flex-1 flex flex-col justify-center px-4 py-8 sm:px-6 lg:px-12 xl:px-20 bg-stone-50">
            <div class="w-full max-w-md mx-auto space-y-6">
                
                <!-- Link de Retorno à Landing Page -->
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-teal-700 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        <span>Voltar à página inicial</span>
                    </a>

                    <a href="{{ route('painel.chamadas') }}" target="_blank"
                       class="text-xs font-medium text-teal-700 hover:text-teal-800 transition">
                        Painel TV &rarr;
                    </a>
                </div>

                <!-- Card de Login Principal -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                    
                    <!-- Cabeçalho do Card (Mobile Brand + Título) -->
                    <div class="space-y-3">
                        <!-- Logo visível em telas menores (< lg) -->
                        <div class="lg:hidden pb-2 border-b border-slate-100">
                            @if ($configuracaoEstabelecimento->logo_path)
                                <img src="{{ $configuracaoEstabelecimento->logoUrl() }}" 
                                     alt="Logo do estabelecimento" 
                                     class="max-h-14 max-w-full object-contain mb-2">
                            @else
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-700 text-white font-bold text-sm">
                                        CD
                                    </div>
                                    <div>
                                        <h1 class="text-lg font-bold text-slate-900 leading-none">CER Digital</h1>
                                        <span class="text-[11px] text-slate-500 font-medium">Reabilitação & SUS</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                                Bem-vindo ao CER Digital
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                                Digite suas credenciais institucionais para acessar o painel de atendimento.
                            </p>
                        </div>
                    </div>

                    <!-- Alertas de Erro de Validação/Autenticação -->
                    @if ($errors->any())
                        <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 flex items-start gap-3 text-xs text-rose-800">
                            <svg class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            <div class="space-y-1">
                                <span class="font-bold block">Falha na autenticação:</span>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Formulário de Login -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ showPass: false, submitting: false }" @submit="submitting = true">
                        @csrf

                        <!-- E-mail Institucional -->
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                E-mail institucional
                            </label>
                            <div class="relative">
                                <input id="email"
                                       type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required
                                       autofocus
                                       autocomplete="username"
                                       inputmode="email"
                                       placeholder="seu.nome@cer.saude.gov.br"
                                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 shadow-2xs focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 transition">
                            </div>
                        </div>

                        <!-- Senha com Toggle de Visualização -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Senha de acesso
                                </label>
                            </div>
                            <div class="relative">
                                <input id="password"
                                       :type="showPass ? 'text' : 'password'"
                                       name="password"
                                       required
                                       autocomplete="current-password"
                                       placeholder="••••••••••••"
                                       class="block w-full rounded-xl border border-slate-300 pl-3.5 pr-11 py-3 text-base sm:text-sm text-slate-900 placeholder:text-slate-400 shadow-2xs focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 transition">
                                
                                <button type="button"
                                        @click="showPass = !showPass"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer touch-target"
                                        aria-label="Alternar visibilidade da senha"
                                        tabindex="-1">
                                    <svg x-show="!showPass" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <svg x-show="showPass" style="display: none;" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Opção "Manter conectado" -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex items-center gap-2.5 text-xs text-slate-600 font-medium cursor-pointer">
                                <input type="checkbox"
                                       name="remember"
                                       class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                                <span>Manter conectado neste dispositivo</span>
                            </label>
                        </div>

                        <!-- Botão Entrar -->
                        <div class="pt-2">
                            <button type="submit"
                                    :disabled="submitting"
                                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 transition cursor-pointer disabled:opacity-75 touch-target">
                                <span x-show="!submitting">Entrar no CER Digital</span>
                                <span x-show="submitting" style="display: none;" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Verificando credenciais...</span>
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Aviso de Segurança e Acesso Restrito -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-[11px] text-slate-400 text-center">
                        <svg class="h-3.5 w-3.5 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        <span>Ambiente monitorado e protegido sob as diretrizes da LGPD</span>
                    </div>
                </div>

                <div class="text-center text-[11px] text-slate-400">
                    <p>CER Digital • Sistema de Gestão Especializada em Reabilitação</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
