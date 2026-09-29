<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Credenciales correctas + usuario activo.
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::query()->where('email', Str::lower((string) $request->input(Fortify::username())))->first();
            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }
            if (! $user->is_active) {
                throw ValidationException::withMessages([Fortify::username() => 'Tu usuario está desactivado.']);
            }
            $user->forceFill(['ultimo_acceso' => now()])->saveQuietly();

            return $user;
        });

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // 5 intentos por minuto por correo + IP.
        RateLimiter::for('login', function (Request $request) {
            $clave = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($clave)->response(fn (Request $request, array $headers) => back()
                ->withInput($request->only(Fortify::username()))
                ->withErrors([Fortify::username() => 'Demasiados intentos fallidos. Espera un minuto e intenta de nuevo.']));
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }
}
