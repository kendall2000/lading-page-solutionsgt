<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use App\Services\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bandeja del chat en vivo: conversaciones con visitantes, lectura y cierre en tiempo real. */
class ChatController extends Controller
{
    public function __construct(private readonly Chat $chat) {}

    public function index(Request $request): View
    {
        $estado = $request->query('estado') === 'cerrada' ? 'cerrada' : 'abierta';
        $conversaciones = Conversacion::query()->with('ultimoMensaje')->where('estado', $estado)
            ->orderByDesc('ultimo_mensaje_en')->limit(100)->get();
        $actual = $request->integer('c') ? Conversacion::query()->find($request->integer('c')) : $conversaciones->first();
        if ($actual) {
            // Abrirla es leerla: el visitante ve ✓✓ Visto al instante.
            $this->chat->marcarLeidos($actual, 'admin');
        }

        return view('admin.chat.index', [
            'conversaciones' => $conversaciones,
            'actual' => $actual,
            'mensajes' => $actual ? $this->chat->historial($actual, 0, 500) : [],
            'leidoHasta' => $actual ? $this->chat->leidoHasta($actual, 'admin') : 0,
            'estado' => $estado,
            'abiertas' => Conversacion::query()->where('estado', 'abierta')->count(),
            'cerradas' => Conversacion::query()->where('estado', 'cerrada')->count(),
        ]);
    }

    /**
     * Mensajes nuevos y hasta dónde leyó el visitante. Con «leer=1» (la pestaña del
     * navegador está a la vista) marca como leídos los del visitante.
     */
    public function mensajes(Request $request, Conversacion $conversacion): JsonResponse
    {
        if ($request->boolean('leer')) {
            $this->chat->marcarLeidos($conversacion, 'admin');
        }

        return response()->json([
            'mensajes' => $this->chat->historial($conversacion, $request->integer('despues')),
            'leido_hasta' => $this->chat->leidoHasta($conversacion, 'admin'),
            'estado' => $conversacion->estado,
        ]);
    }

    public function responder(Request $request, Conversacion $conversacion): JsonResponse
    {
        if ($conversacion->estado === 'cerrada') {
            return response()->json(['message' => 'La conversación está cerrada: el visitante ya no la ve.'], 409);
        }
        $datos = $request->validate(['cuerpo' => ['required', 'string', 'max:'.Chat::MAXIMO]], [], ['cuerpo' => 'mensaje']);
        $this->chat->marcarLeidos($conversacion, 'admin'); // si respondes, ya leíste
        $mensaje = $this->chat->enviar($conversacion, 'admin', $datos['cuerpo'], $request->user());

        return response()->json(['mensaje' => $mensaje->paraCliente()], 201);
    }

    /** Total sin leer y conversaciones con mensajes nuevos (para el aviso del panel sin tiempo real). */
    public function resumen(): JsonResponse
    {
        $pendientes = Conversacion::query()->where('no_leidos_admin', '>', 0)->orderByDesc('ultimo_mensaje_en')
            ->limit(10)->get(['id', 'nombre', 'no_leidos_admin', 'ultimo_mensaje_en']);

        return response()->json(['total' => (int) $pendientes->sum('no_leidos_admin'), 'conversaciones' => $pendientes]);
    }

    /** Cierra la conversación y le avisa al visitante (con opción de copia por correo). */
    public function cerrar(Request $request, Conversacion $conversacion): RedirectResponse
    {
        $this->chat->cerrar($conversacion, $request->user());

        return redirect()->route('admin.chat.index')
            ->with('status', "Conversación con {$conversacion->nombre} cerrada. Se le avisó al visitante; el historial queda aquí en «Cerradas».");
    }

    public function destroy(Conversacion $conversacion): RedirectResponse
    {
        $conversacion->delete();

        return redirect()->route('admin.chat.index')->with('status', 'Conversación eliminada.');
    }
}
