<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Services\Chat;
use App\Services\Correos;
use App\Support\Sitio;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Throwable;

/**
 * Chat en vivo del lado del VISITANTE (widget del sitio). El visitante no tiene
 * usuario: se identifica con un token aleatorio en una cookie cifrada y en la base
 * solo se guarda su hash. Todo responde JSON.
 */
class ChatController extends Controller
{
    public const COOKIE = 'chat_visitante';

    public function __construct(private readonly Chat $chat) {}

    /** Conversación actual del visitante (si tiene) con su historial. */
    public function estado(Request $request): JsonResponse
    {
        $conversacion = $this->conversacion($request);
        if ($conversacion && $conversacion->no_leidos_visitante > 0) {
            $conversacion->update(['no_leidos_visitante' => 0]);
        }

        return response()->json($this->respuesta($conversacion));
    }

    /** Primer mensaje: crea la conversación y entrega la cookie del visitante. */
    public function iniciar(Request $request, Correos $correos): JsonResponse
    {
        abort_unless(Sitio::config()->chat_activo, 404);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'mensaje' => ['required', 'string', 'max:'.Chat::MAXIMO],
            'pagina' => ['nullable', 'string', 'max:255'],
            'empresa_web' => ['prohibited'], // campo trampa para robots
        ], [], ['nombre' => 'nombre', 'correo' => 'correo', 'mensaje' => 'mensaje']);

        $token = Str::random(48);
        $conversacion = Conversacion::query()->create([
            'token_hash' => Conversacion::hashToken($token),
            'nombre' => $datos['nombre'],
            'correo' => Str::lower($datos['correo']),
            'telefono' => $datos['telefono'] ?? null,
            'pagina' => $datos['pagina'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
        $this->chat->enviar($conversacion, 'visitante', $datos['mensaje']);

        // Aviso por correo (Correos → «Avisos a»): por si nadie está conectado al panel.
        $correos->avisar('nuevo_chat', [
            'nombre' => $conversacion->nombre, 'correo' => $conversacion->correo, 'pagina' => $conversacion->pagina ?: '—',
            'mensaje' => $datos['mensaje'], 'enlace' => route('admin.chat.index', ['c' => $conversacion->id]),
        ], $conversacion->correo);

        // Cookie cifrada, solo HTTP (JS no la lee), un año.
        return response()->json($this->respuesta($conversacion->fresh()))
            ->cookie(self::COOKIE, $token, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax');
    }

    public function enviar(Request $request): JsonResponse
    {
        $conversacion = $this->conversacion($request);
        abort_unless($conversacion, 404, 'No hay una conversación abierta.');
        $datos = $request->validate(['cuerpo' => ['required', 'string', 'max:'.Chat::MAXIMO]], [], ['cuerpo' => 'mensaje']);

        $mensaje = $this->chat->enviar($conversacion, 'visitante', $datos['cuerpo']);

        return response()->json(['mensaje' => $mensaje->paraCliente()], 201);
    }

    /** Mensajes nuevos (respaldo por si el tiempo real no está disponible). */
    public function mensajes(Request $request): JsonResponse
    {
        $conversacion = $this->conversacion($request);
        abort_unless($conversacion, 404);
        if ($conversacion->no_leidos_visitante > 0) {
            $conversacion->update(['no_leidos_visitante' => 0]);
        }

        return response()->json(['mensajes' => $this->chat->historial($conversacion, $request->integer('despues'))]);
    }

    /**
     * Autoriza el canal privado del visitante en Reverb: solo el de SU conversación
     * (la de su cookie). Responde la firma que Pusher/Echo esperan.
     */
    public function autorizar(Request $request, BroadcastManager $broadcast): Response|JsonResponse
    {
        $datos = $request->validate([
            'socket_id' => ['required', 'string', 'max:100', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string', 'max:120'],
        ]);
        $conversacion = $this->conversacion($request);
        abort_unless($conversacion && $datos['channel_name'] === 'private-'.$conversacion->canal(), 403);

        try {
            $firma = $broadcast->connection('reverb')->getPusher()->authorizeChannel($datos['channel_name'], $datos['socket_id']);
        } catch (Throwable $e) {
            report($e);
            abort(503);
        }

        return response($firma, 200, ['Content-Type' => 'application/json']);
    }

    private function conversacion(Request $request): ?Conversacion
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && strlen($token) === 48
            ? Conversacion::query()->where('token_hash', Conversacion::hashToken($token))->first()
            : null;
    }

    private function respuesta(?Conversacion $conversacion): array
    {
        return [
            'conversacion' => $conversacion ? [
                'id' => $conversacion->id,
                'nombre' => $conversacion->nombre,
                'canal' => $conversacion->canal(),
                'estado' => $conversacion->estado,
            ] : null,
            'mensajes' => $conversacion ? $this->chat->historial($conversacion) : [],
        ];
    }
}
