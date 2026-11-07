<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agendamento;
use App\Models\EvolucaoClinica;
use App\Models\Paciente;
use App\Policies\AgendamentoPolicy;
use App\Policies\EvolucaoPolicy;
use App\Policies\PacientePolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(EvolucaoClinica::class, EvolucaoPolicy::class);
        Gate::policy(Agendamento::class, AgendamentoPolicy::class);
        Gate::policy(Paciente::class, PacientePolicy::class);

        Event::listen(Login::class, function (): void {
            session()->put('exibir_modal_versao', true);
        });
    }
}
