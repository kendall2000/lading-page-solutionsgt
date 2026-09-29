<?php

use App\Http\Middleware\CuentaVerificada;
use App\Http\Middleware\EncabezadosSeguridad;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RegistrarVisita;
use App\Http\Middleware\UsuarioActivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Detrás del proxy del servidor: IP real del visitante.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [EncabezadosSeguridad::class, UsuarioActivo::class]);
        $middleware->alias([
            'sin-cache' => PreventBackHistory::class, 'visita' => RegistrarVisita::class, 'cuenta.verificada' => CuentaVerificada::class,
        ]);
        // El portal de clientes (/mi-cuenta) tiene su propio acceso, separado del panel.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('mi-cuenta', 'mi-cuenta/*') ? route('cuenta.entrar') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('mi-cuenta', 'mi-cuenta/*') ? route('cuenta.inicio') : route('admin.inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
