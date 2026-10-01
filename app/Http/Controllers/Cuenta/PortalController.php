<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use App\Models\Cuenta;
use App\Models\Manual;
use App\Rules\PocosEnlaces;
use App\Services\Chat;
use App\Services\Correos;
use App\Support\Sitio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Portal «Mi cuenta» del cliente: resumen, sistemas, solicitudes y pruebas, conversaciones, manuales y perfil. */
class PortalController extends Controller
{
    public function __construct(private readonly Chat $chat) {}

    private function cuenta(): Cuenta
    {
        return Auth::guard('cliente')->user();
    }

    public function inicio(): View
    {
        $cuenta = $this->cuenta();

        return view('publico.cuenta.inicio', [
            'cuenta' => $cuenta,
            'contratos' => $cuenta->contratosVigentes(),
            'pruebas' => $this->pruebasActivas($cuenta),
            'solicitudes' => $cuenta->solicitudes()->with('sistema')->limit(5)->get(),
            'conversaciones' => $cuenta->conversaciones()->with('ultimoMensaje')->limit(4)->get(),
            'manuales' => $this->manuales($cuenta)->count(),
        ] + self::contadores($cuenta));
    }

    public function sistemas(): View
    {
        $cuenta = $this->cuenta();
        $contratos = $cuenta->contratos()->get();
        $manuales = Manual::query()->publicos()->whereIn('sistema_id', $contratos->pluck('sistema_id'))->get()
            ->filter(fn (Manual $m) => $cuenta->puedeVerManual($m))->groupBy('sistema_id');

        return view('publico.cuenta.sistemas', ['cuenta' => $cuenta, 'contratos' => $contratos, 'manuales' => $manuales] + self::contadores($cuenta));
    }

    public function solicitudes(): View
    {
        $cuenta = $this->cuenta();

        return view('publico.cuenta.solicitudes', [
            'cuenta' => $cuenta,
            'pruebas' => $this->pruebasActivas($cuenta),
            'solicitudes' => $cuenta->solicitudes()->with('sistema')->paginate(15),
        ] + self::contadores($cuenta));
    }

    public function conversaciones(): View
    {
        $cuenta = $this->cuenta();

        return view('publico.cuenta.conversaciones', [
            'cuenta' => $cuenta,
            'conversaciones' => $cuenta->conversaciones()->with('ultimoMensaje')->withCount('mensajes')->paginate(15),
            'chatActivo' => (bool) Sitio::config()->chat_activo,
        ] + self::contadores($cuenta));
    }

    public function conversacion(Conversacion $conversacion): View
    {
        $cuenta = $this->cuenta();
        abort_unless($conversacion->cuenta_id === $cuenta->id, 404);
        $this->chat->marcarLeidos($conversacion, 'visitante');

        return view('publico.cuenta.conversacion', [
            'cuenta' => $cuenta,
            'conversacion' => $conversacion,
            'mensajes' => $this->chat->historial($conversacion),
        ] + self::contadores($cuenta));
    }

    /** Mensajes nuevos (el portal consulta cada pocos segundos). leer=1: la página está a la vista. */
    public function mensajes(Request $request, Conversacion $conversacion): JsonResponse
    {
        abort_unless($conversacion->cuenta_id === $this->cuenta()->id, 404);
        if ($request->boolean('leer')) {
            $this->chat->marcarLeidos($conversacion, 'visitante');
        }

        return response()->json([
            'mensajes' => $this->chat->historial($conversacion, (int) $request->query('despues', 0)),
            'estado' => $conversacion->fresh()->estado,
            'leidoHasta' => $this->chat->leidoHasta($conversacion, 'visitante'),
        ]);
    }

    public function responder(Request $request, Conversacion $conversacion): JsonResponse
    {
        abort_unless($conversacion->cuenta_id === $this->cuenta()->id, 404);
        if ($conversacion->estado === 'cerrada') {
            return response()->json(['message' => 'Esta conversación ya se cerró. Empieza una nueva.'], 409);
        }
        $datos = $request->validate(['cuerpo' => ['required', 'string', 'max:'.Chat::MAXIMO, new PocosEnlaces]], [], ['cuerpo' => 'mensaje']);

        return response()->json(['mensaje' => $this->chat->enviar($conversacion, 'visitante', $datos['cuerpo'])->paraCliente()], 201);
    }

    /** Conversación nueva desde el portal (ya identificado). También queda abierta en el widget del sitio. */
    public function nueva(Request $request, Correos $correos): RedirectResponse
    {
        abort_unless(Sitio::config()->chat_activo, 404);
        $cuenta = $this->cuenta();
        $datos = $request->validate(['mensaje' => ['required', 'string', 'max:'.Chat::MAXIMO, new PocosEnlaces]], [], ['mensaje' => 'mensaje']);

        $token = Str::random(48);
        $conversacion = Conversacion::query()->create([
            'cuenta_id' => $cuenta->id, 'token_hash' => Conversacion::hashToken($token), 'nombre' => $cuenta->nombre,
            'correo' => $cuenta->correo, 'telefono' => $cuenta->telefono, 'pagina' => '/mi-cuenta',
            'ip' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
        $this->chat->enviar($conversacion, 'visitante', $datos['mensaje']);
        $correos->avisar('nuevo_chat', [
            'nombre' => $cuenta->nombre, 'correo' => $cuenta->correo, 'pagina' => 'Mi cuenta', 'mensaje' => $datos['mensaje'],
            'enlace' => route('admin.chat.index', ['c' => $conversacion->id]),
        ], $cuenta->correo);

        return redirect()->route('cuenta.conversacion', $conversacion)
            ->cookie(ChatController::COOKIE, $token, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax');
    }

    public function manualesVista(): View
    {
        $cuenta = $this->cuenta();

        return view('publico.cuenta.manuales', ['cuenta' => $cuenta, 'manuales' => $this->manuales($cuenta)->groupBy(fn ($m) => $m->sistema?->nombre ?? 'Generales')] + self::contadores($cuenta));
    }

    public function perfil(Request $request): View
    {
        $cuenta = $this->cuenta();

        return view('publico.cuenta.perfil', [
            'cuenta' => $cuenta,
            'pideActual' => $this->pideClaveActual($request, $cuenta),
        ] + self::contadores($cuenta));
    }

    public function actualizarPerfil(Request $request): RedirectResponse
    {
        $this->cuenta()->update($request->validate([
            'nombre' => ['required', 'string', 'max:120', new PocosEnlaces(0)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'empresa' => ['nullable', 'string', 'max:150', new PocosEnlaces(0)],
        ]));

        return back()->with('status', 'Tus datos quedaron guardados.');
    }

    public function cambiarClave(Request $request): RedirectResponse
    {
        $cuenta = $this->cuenta();
        $pideActual = $this->pideClaveActual($request, $cuenta);
        $datos = $request->validate([
            'actual' => [$pideActual ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['actual' => 'contraseña actual', 'password' => 'contraseña nueva']);
        if ($pideActual && ! Hash::check($datos['actual'], (string) $cuenta->password)) {
            throw ValidationException::withMessages(['actual' => 'La contraseña actual no es correcta.']);
        }

        $cuenta->password = $datos['password'];
        $cuenta->save();
        $request->session()->forget('cuenta_por_enlace');

        return back()->with('status', 'Tu contraseña quedó guardada.');
    }

    /** Sin contraseña todavía, o entró con enlace por correo en esta sesión: no se le pide la actual. */
    private function pideClaveActual(Request $request, Cuenta $cuenta): bool
    {
        return $cuenta->password !== null && ! $request->session()->get('cuenta_por_enlace');
    }

    /** Accesos de prueba con credenciales enviadas y todavía vigentes. */
    private function pruebasActivas(Cuenta $cuenta)
    {
        return $cuenta->solicitudes()->with('sistema')->where('tipo', 'prueba')->whereNotNull('credenciales_enviadas_en')
            ->where(fn ($q) => $q->whereNull('vence_el')->orWhere('vence_el', '>=', now()->toDateString()))->get();
    }

    /** Manuales que puede ver: los públicos de sus sistemas y los privados de los que tiene vigentes. */
    private function manuales(Cuenta $cuenta)
    {
        $sistemas = $cuenta->contratosVigentes()->pluck('sistema_id');

        return Manual::query()->publicos()->with('sistema')
            ->where(fn ($q) => $q->whereIn('sistema_id', $sistemas)->orWhere('solo_clientes', true))->get()
            ->filter(fn (Manual $m) => $cuenta->puedeVerManual($m))->values();
    }

    /** Números del encabezado del portal (pestañas). */
    public static function contadores(Cuenta $cuenta): array
    {
        return ['conteo' => [
            'sistemas' => $cuenta->contratosVigentes()->count(),
            'solicitudes' => $cuenta->solicitudes()->count(),
            'conversaciones' => $cuenta->conversaciones()->count(),
            'sinLeer' => (int) $cuenta->conversaciones()->sum('no_leidos_visitante'),
        ]];
    }
}
