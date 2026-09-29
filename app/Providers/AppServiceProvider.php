<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
