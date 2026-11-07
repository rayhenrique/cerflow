<!DOCTYPE html>
@php($configuracaoEstabelecimento = \\App\\Models\\ConfiguracaoEstabelecimento::atual())
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso ao Sistema — CER Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 font-sans text-slate-900 antialiased">
    <div class="w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-8">
        <div class="mb-6">
            <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 ring-1 ring-inset ring-teal-600/20">
                SUS / SIA - BPA-i
            </span>
            @if ($configuracaoEstabelecimento->logo_path)
                <img src="{{ \\Illuminate\\Support\\Facades\\Storage::disk('public')->url($configuracaoEstabelecimento->logo_path) }}" alt="Logo do estabelecimento" class="mb-4 max-h-24 max-w-full object-contain object-left">
            @else
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">CER Digital</h1>
            @endif
            <p class="mt-1 text-sm text-slate-500">Centro Especializado em Reabilitação — Prontuário e Grade</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">E-mail institucional</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-xs focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                <input id="password" type="password" name="password" required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-xs focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                    Manter conectado
                </label>
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">
                Entrar no CER Digital
            </button>
        </form>
    </div>
</body>
</html>

