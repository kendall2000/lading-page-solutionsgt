<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Portal «Mi cuenta»: la cuenta debe estar activa y con el correo confirmado. */
class CuentaVerificada
{
    public function handle(Request $request, Closure $next): Response
    {
        $cuenta = Auth::guard('cliente')->user();
        if ($cuenta && ! $cuenta->activa) {
            // Solo se cierra la sesión del cliente (en ese navegador puede haber una del panel).
            Auth::guard('cliente')->logout();
            $request->session()->regenerateToken();

            return redirect()->route('cuenta.entrar')->withErrors(['correo' => 'Tu cuenta está desactivada. Escríbenos si crees que es un error.']);
        }
        if ($cuenta && ! $cuenta->verificada()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Confirma tu correo para continuar.'], 403)
                : redirect()->route('cuenta.pendiente');
        }

        return $next($request);
    }
}
