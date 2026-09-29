<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Bitacora;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paginación con el estilo de Bootstrap (la plantilla Phoenix).
        Paginator::useBootstrapFive();

        // Contraseñas del panel: mínimo 10 caracteres, con letras y números.
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        // Entradas y salidas del panel en la bitácora de cambios.
        Event::listen(Login::class, fn (Login $e) => $e->user instanceof User
            && app(Bitacora::class)->anotar('entrar', 'Acceso', 'Inició sesión', $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User
            && app(Bitacora::class)->anotar('salir', 'Acceso', 'Cerró sesión', $e->user));
        Event::listen(Failed::class, fn (Failed $e) => $e->guard === 'web' && app(Bitacora::class)->anotar(
            'fallido', 'Acceso', 'Contraseña incorrecta, usuario inexistente o desactivado',
            // Si el correo es de un usuario, se liga a él (para ver intentos contra cuentas reales).
            $e->user instanceof User ? $e->user : User::query()->where('email', mb_strtolower((string) ($e->credentials['email'] ?? '')))->first(),
            nombre: mb_substr((string) ($e->credentials['email'] ?? '—'), 0, 145),
        ));
    }
}
