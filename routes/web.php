<?php

declare(strict_types=1);

use App\Http\Controllers\AudioPainelController;
use App\Livewire\AgendaOperacional;
use App\Livewire\AjudaSobre;
use App\Livewire\ConfiguracoesSistema;
use App\Livewire\DashboardInicial;
use App\Livewire\FaturamentoBpai;
use App\Livewire\PainelChamadas;
use App\Livewire\ProntuarioPaciente;
use App\Livewire\RelatoriosGerenciais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/painel-chamadas', PainelChamadas::class)
    ->name('painel.chamadas');

Route::get('/painel-chamadas/audio/fala', [AudioPainelController::class, 'sintetizar'])
    ->name('painel.audio.fala');

Route::get('/painel-chamadas/audio/status', [AudioPainelController::class, 'status'])
    ->name('painel.audio.status');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/dashboard', DashboardInicial::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('dashboard');

    Route::get('/agenda', AgendaOperacional::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('agenda.operacional');

    Route::get('/relatorios', RelatoriosGerenciais::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('relatorios.index');

    // Redirecionamentos autenticados para consolidação da Agenda Operacional
    Route::get('/grade', function (Request $request) {
        $params = $request->query();
        $params['aba'] = 'grade';

        return redirect()->route('agenda.operacional', $params);
    })->middleware('role:recepcao|terapeuta|faturamento|coordenacao')->name('grade.semanal');

    Route::get('/agenda/mensal', function (Request $request) {
        $params = $request->query();
        $params['aba'] = 'mensal';

        return redirect()->route('agenda.operacional', $params);
    })->middleware('role:recepcao|terapeuta|faturamento|coordenacao')->name('agenda.mensal');

    Route::get('/recepcao', function (Request $request) {
        $params = $request->query();
        $params['aba'] = 'dia';

        return redirect()->route('agenda.operacional', $params);
    })->middleware('role:recepcao|terapeuta|coordenacao')->name('recepcao.checkin');

    Route::get('/prontuario/{paciente?}', ProntuarioPaciente::class)
        ->middleware(['role:terapeuta|coordenacao', 'audit.access:ACESSO_PEP'])
        ->name('prontuario.index');

    Route::get('/faturamento', FaturamentoBpai::class)
        ->middleware(['role:faturamento|coordenacao'])
        ->name('faturamento.bpai');

    Route::get('/configuracoes', ConfiguracoesSistema::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('configuracoes.index');

    Route::get('/configuracoes/{secao}', ConfiguracoesSistema::class)
        ->whereIn('secao', ['pacientes', 'salas', 'profissionais', 'cbos', 'ubs', 'sigtap', 'auditoria', 'estabelecimento', 'esus', 'chamadas', 'perfil'])
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('configuracoes.secao');

    Route::get('/ajuda', AjudaSobre::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('ajuda.sobre');
});
