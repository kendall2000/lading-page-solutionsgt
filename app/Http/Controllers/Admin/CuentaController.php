<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Seguridad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Seguridad de la propia cuenta: contraseña, verificación en dos pasos y sesiones abiertas. */
class CuentaController extends Controller
{
    public function show(Request $request): View
    {
        $actual = $request->session()->getId();
        $sesiones = DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get()
            ->map(fn ($s) => [
                'dispositivo' => $this->dispositivo((string) $s->user_agent),
                'ip' => $s->ip_address,
                'actividad' => Carbon::createFromTimestamp($s->last_activity)->timezone(config('app.timezone'))->diffForHumans(),
                'actual' => $s->id === $actual,
            ]);

        return view('admin.cuenta', ['user' => $request->user(), 'sesiones' => $sesiones]);
    }

    public function cerrarSesiones(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], ['password.current_password' => 'La contraseña no es correcta.']);
        $n = Seguridad::cerrarSesiones($request->user()->id, $request->session()->getId());

        return back()->with('status', $n === 1 ? 'Se cerró 1 sesión en otro dispositivo.' : "Se cerraron {$n} sesiones en otros dispositivos.");
    }

    private function dispositivo(string $agente): string
    {
        $navegador = match (true) {
            str_contains($agente, 'Edg') => 'Edge',
            str_contains($agente, 'OPR') => 'Opera',
            str_contains($agente, 'Chrome') => 'Chrome',
            str_contains($agente, 'Firefox') => 'Firefox',
            str_contains($agente, 'Safari') => 'Safari',
            default => 'Navegador',
        };
        $sistema = match (true) {
            str_contains($agente, 'Android') => 'Android',
            str_contains($agente, 'iPhone') || str_contains($agente, 'iPad') => 'iOS',
            str_contains($agente, 'Windows') => 'Windows',
            str_contains($agente, 'Mac OS') => 'macOS',
            str_contains($agente, 'Linux') => 'Linux',
            default => 'desconocido',
        };

        return "{$navegador} en {$sistema}";
    }
}
