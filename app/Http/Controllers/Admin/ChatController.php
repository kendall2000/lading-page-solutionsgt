<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use App\Services\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bandeja del chat en vivo: conversaciones con visitantes y respuestas en tiempo real. */
class ChatController extends Controller
{
    public function __construct(private readonly Chat $chat) {}

    public function index(Request $request): View
    {
        $estado = $request->query('estado') === 'cerrada' ? 'cerrada' : 'abierta';
        $conversaciones = Conversacion::query()->with('ultimoMensaje')->where('estado', $estado)
            ->orderByDesc('ultimo_mensaje_en')->limit(100)->get();
        $actual = $request->integer('c') ? Conversacion::query()->find($request->integer('c')) : $conversaciones->first();
        if ($actual?->no_leidos_admin) {
            $actual->update(['no_leidos_admin' => 0]);
        }

        return view('admin.chat.index', [
            'conversaciones' => $conversaciones,
            'actual' => $actual,
            'mensajes' => $actual ? $this->chat->historial($actual, 0, 500) : [],
            'estado' => $estado,
            'abiertas' => Conversacion::query()->where('estado', 'abierta')->count(),
            'cerradas' => Conversacion::query()->where('estado', 'cerrada')->count(),
        ]);
    }

    /** Mensajes nuevos de una conversación (y la marca como leída). */
    public function mensajes(Request $request, Conversacion $conversacion): JsonResponse
    {
        if ($conversacion->no_leidos_admin) {
            $conversacion->update(['no_leidos_admin' => 0]);
        }

        return response()->json(['mensajes' => $this->chat->historial($conversacion, $request->integer('despues'))]);
    }

    public function responder(Request $request, Conversacion $conversacion): JsonResponse
    {
        $datos = $request->validate(['cuerpo' => ['required', 'string', 'max:'.Chat::MAXIMO]], [], ['cuerpo' => 'mensaje']);
        $mensaje = $this->chat->enviar($conversacion, 'admin', $datos['cuerpo'], $request->user());
        $conversacion->update(['no_leidos_admin' => 0]);

        return response()->json(['mensaje' => $mensaje->paraCliente()], 201);
    }

    /** Total sin leer y conversaciones con mensajes nuevos (para el aviso del panel sin tiempo real). */
    public function resumen(): JsonResponse
    {
        $pendientes = Conversacion::query()->where('no_leidos_admin', '>', 0)->orderByDesc('ultimo_mensaje_en')
            ->limit(10)->get(['id', 'nombre', 'no_leidos_admin', 'ultimo_mensaje_en']);

        return response()->json(['total' => (int) $pendientes->sum('no_leidos_admin'), 'conversaciones' => $pendientes]);
    }

    public function cerrar(Conversacion $conversacion): RedirectResponse
    {
        $conversacion->update(['estado' => $conversacion->estado === 'cerrada' ? 'abierta' : 'cerrada', 'no_leidos_admin' => 0]);

        return redirect()->route('admin.chat.index', ['c' => $conversacion->id, 'estado' => $conversacion->estado])
            ->with('status', $conversacion->estado === 'cerrada' ? 'Conversación cerrada. Si el visitante vuelve a escribir, se reabre sola.' : 'Conversación reabierta.');
    }

    public function destroy(Conversacion $conversacion): RedirectResponse
    {
        $conversacion->delete();

        return redirect()->route('admin.chat.index')->with('status', 'Conversación eliminada.');
    }
}
