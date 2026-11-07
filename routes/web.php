<?php

declare(strict_types=1);

use App\Livewire\AjudaSobre;
use App\Livewire\ConfiguracoesSistema;
use App\Livewire\FaturamentoBpai;
use App\Livewire\GradeSemanal;
use App\Livewire\ProntuarioPaciente;
use App\Livewire\RecepcaoCheckin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/grade');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/grade', GradeSemanal::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('grade.semanal');

    Route::get('/recepcao', RecepcaoCheckin::class)
        ->middleware('role:recepcao|terapeuta|coordenacao')
        ->name('recepcao.checkin');

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
        ->whereIn('secao', ['pacientes', 'salas', 'profissionais', 'sigtap', 'auditoria', 'perfil'])
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('configuracoes.secao');

    Route::get('/ajuda', AjudaSobre::class)
        ->middleware('role:recepcao|terapeuta|faturamento|coordenacao')
        ->name('ajuda.sobre');
});
