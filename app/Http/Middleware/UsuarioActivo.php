<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Si desactivan al usuario mientras tiene la sesión abierta, lo saca en su siguiente petición. */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        // Siempre el usuario del panel («web»): las cuentas de clientes tienen su propio control (CuentaVerificada).
        $user = Auth::guard('web')->user();
        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu usuario está desactivado.']);
        }

        return $next($request);
    }
}
