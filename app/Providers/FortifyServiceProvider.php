<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        Fortify::loginView(fn () => view('auth.login'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            /** @var ?User $user */
            $user = User::query()
                ->where('email', (string) $request->input('email'))
                ->first();

            if ($user !== null && $user->ativo && Hash::check((string) $request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });
    }
}
