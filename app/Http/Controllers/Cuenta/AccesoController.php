<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Rules\PocosEnlaces;
use App\Services\Cuentas;
use App\Support\Antispam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Acceso al portal «Mi cuenta»: correo y contraseña, o enlace de un solo uso por correo; registro con
 * confirmación del correo e invitaciones desde el panel. Los mensajes no dicen si un correo tiene cuenta.
 */
class AccesoController extends Controller
{
    public const AVISO_ENLACE = 'Si ese correo tiene una cuenta, te enviamos un enlace para entrar. Revisa tu bandeja (y la de spam).';

    public function __construct(private readonly Cuentas $cuentas) {}

    public function entrar(): View
    {
        return view('publico.cuenta.entrar');
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate(['correo' => ['required', 'email', 'max:150'], 'password' => ['required', 'string', 'max:200']]);
        $clave = 'cuenta-login:'.Str::transliterate(Str::lower($datos['correo'])).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages(['correo' => 'Demasiados intentos. Espera un minuto o entra con un enlace por correo.']);
        }

        if (! Auth::guard('cliente')->attempt(['correo' => Str::lower($datos['correo']), 'password' => $datos['password'], 'activa' => true], $request->boolean('recordar'))) {
            RateLimiter::hit($clave, 60);
            throw ValidationException::withMessages(['correo' => 'Correo o contraseña incorrectos. ¿No tienes contraseña? Entra con un enlace por correo.']);
        }
        RateLimiter::clear($clave);

        return $this->iniciar($request, Auth::guard('cliente')->user());
    }

    /** Pide el enlace para entrar sin contraseña (también sirve si la olvidó). */
    public function pedirEnlace(Request $request): RedirectResponse
    {
        $datos = $request->validate(['correo' => ['required', 'email', 'max:150']]);
        $cuenta = Cuenta::query()->where('correo', Str::lower($datos['correo']))->where('activa', true)->first();
        if ($cuenta) {
            $this->cuentas->enviarEnlace($cuenta);
        }

        return back()->with('status', self::AVISO_ENLACE);
    }

    /**
     * El enlace del correo abre una página con un botón (no entra solo): los antivirus del correo
     * abren los enlaces para revisarlos y gastarían el de un solo uso.
     */
    public function acceso(string $token): View
    {
        return view('publico.cuenta.acceso', ['valido' => (bool) $this->cuentas->buscarToken($token, 'enlace'), 'token' => $token]);
    }

    public function usarAcceso(Request $request, string $token): RedirectResponse
    {
        $cuenta = $this->cuentas->buscarToken($token, 'enlace');
        if (! $cuenta) {
            return redirect()->route('cuenta.entrar')->withErrors(['correo' => 'El enlace ya se usó o venció. Pide uno nuevo.']);
        }
        $this->cuentas->gastarToken($cuenta);
        // Si llegó el correo, el correo es suyo.
        $this->cuentas->confirmar($cuenta);
        Auth::guard('cliente')->login($cuenta);
        // Entró sin contraseña: puede crear o cambiarla sin escribir la actual.
        $request->session()->put('cuenta_por_enlace', true);

        return $this->iniciar($request, $cuenta);
    }

    public function registro(): View
    {
        return view('publico.cuenta.registro');
    }

    public function registrar(Request $request): RedirectResponse
    {
        if (filled($request->input('empresa_web'))) {
            return redirect()->route('cuenta.registro')->with('registrado', true);
        }
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120', new PocosEnlaces(0)],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'empresa' => ['nullable', 'string', 'max:150', new PocosEnlaces(0)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['nombre' => 'nombre', 'correo' => 'correo', 'password' => 'contraseña']);
        if (Antispam::muyRapido($request->input('llegada'))) {
            throw ValidationException::withMessages(['formulario' => Antispam::MENSAJE_RAPIDO]);
        }

        $correo = Str::lower($datos['correo']);
        $existente = Cuenta::query()->where('correo', $correo)->first();
        if ($existente) {
            // Ya tiene cuenta: no se dice aquí (sería revelar quién es cliente); le llega un enlace para entrar.
            if ($existente->activa) {
                $this->cuentas->enviarEnlace($existente);
            }
        } else {
            $cuenta = new Cuenta(['nombre' => $datos['nombre'], 'correo' => $correo, 'telefono' => $datos['telefono'] ?? null, 'empresa' => $datos['empresa'] ?? null]);
            $cuenta->password = $datos['password'];
            $cuenta->save();
            $this->cuentas->enviarConfirmacion($cuenta);
        }

        return redirect()->route('cuenta.registro')->with('registrado', true);
    }

    /** Enlace del correo de confirmación (firmado, 24 horas). No inicia sesión: solo confirma. */
    public function verificar(Request $request, Cuenta $cuenta, string $hash): RedirectResponse
    {
        abort_unless(hash_equals(sha1($cuenta->correo), $hash), 403);
        $this->cuentas->confirmar($cuenta, avisar: true);

        return Auth::guard('cliente')->id() === $cuenta->id
            ? redirect()->route('cuenta.inicio')->with('status', '¡Listo! Tu correo quedó confirmado.')
            : redirect()->route('cuenta.entrar')->with('status', '¡Listo! Tu correo quedó confirmado. Ya puedes entrar.');
    }

    public function pendiente(Request $request): View|RedirectResponse
    {
        $cuenta = Auth::guard('cliente')->user();

        return $cuenta->verificada() ? redirect()->route('cuenta.inicio') : view('publico.cuenta.pendiente', ['cuenta' => $cuenta]);
    }

    public function reenviar(Request $request): RedirectResponse
    {
        $cuenta = Auth::guard('cliente')->user();
        if (! $cuenta->verificada()) {
            $this->cuentas->enviarConfirmacion($cuenta);
        }

        return back()->with('status', 'Te enviamos otra vez el correo de confirmación.');
    }

    public function invitacion(string $token): View
    {
        return view('publico.cuenta.invitacion', ['cuenta' => $this->cuentas->buscarToken($token, 'invitacion'), 'token' => $token]);
    }

    public function aceptarInvitacion(Request $request, string $token): RedirectResponse
    {
        $cuenta = $this->cuentas->buscarToken($token, 'invitacion');
        if (! $cuenta) {
            return redirect()->route('cuenta.entrar')->withErrors(['correo' => 'La invitación ya se usó o venció. Entra con un enlace por correo o pídenos otra.']);
        }
        $datos = $request->validate(['password' => ['required', 'confirmed', Password::defaults()]], [], ['password' => 'contraseña']);

        $this->cuentas->gastarToken($cuenta);
        $cuenta->password = $datos['password'];
        $cuenta->save();
        $this->cuentas->confirmar($cuenta);
        Auth::guard('cliente')->login($cuenta);

        return $this->iniciar($request, $cuenta, '¡Bienvenido! Tu cuenta está lista.');
    }

    public function salir(Request $request): RedirectResponse
    {
        // Solo la sesión del cliente: si en este navegador también está abierto el panel, sigue abierto.
        Auth::guard('cliente')->logout();
        $request->session()->forget('cuenta_por_enlace');
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }

    private function iniciar(Request $request, Cuenta $cuenta, ?string $mensaje = null): RedirectResponse
    {
        $request->session()->regenerate();
        $cuenta->forceFill(['ultimo_acceso' => now()])->saveQuietly();

        // «Volver a donde iba» solo dentro del portal o de un manual (la dirección guardada puede ser del panel).
        $destino = (string) $request->session()->pull('url.intended', '');
        $valido = collect([url('/mi-cuenta'), url('/manuales/')])->contains(fn ($base) => str_starts_with($destino, $base));

        return redirect()->to($valido ? $destino : route('cuenta.inicio'))->with('status', $mensaje);
    }
}
