<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MensajeContacto;
use App\Services\Correos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Bandeja de lo que llega por los formularios: mensajes, solicitudes de demostración y de prueba. */
class MensajeController extends Controller
{
    public function index(Request $request): View
    {
        $estado = array_key_exists((string) $request->query('estado'), MensajeContacto::ESTADOS) ? $request->query('estado') : null;
        $tipo = array_key_exists((string) $request->query('tipo'), MensajeContacto::TIPOS) ? $request->query('tipo') : null;

        return view('admin.mensajes.index', [
            'mensajes' => MensajeContacto::query()->with('sistema')
                ->when($estado, fn ($q) => $q->where('estado', $estado))
                ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
                ->latest()->paginate(20)->withQueryString(),
            'estado' => $estado,
            'tipo' => $tipo,
            'conteo' => MensajeContacto::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado'),
            'conteoTipos' => MensajeContacto::query()->selectRaw('tipo, COUNT(*) as total')->groupBy('tipo')->pluck('total', 'tipo'),
        ]);
    }

    public function show(MensajeContacto $mensaje): View
    {
        return view('admin.mensajes.show', ['mensaje' => $mensaje->load('sistema')]);
    }

    public function update(Request $request, MensajeContacto $mensaje): RedirectResponse
    {
        $mensaje->update($request->validate([
            'estado' => ['required', Rule::in(array_keys(MensajeContacto::ESTADOS))],
            'notas' => ['nullable', 'string', 'max:3000'],
        ]));

        return back()->with('status', 'Mensaje actualizado.');
    }

    /**
     * Guarda el acceso de prueba (creado a mano en el sistema correspondiente) y se lo
     * envía al visitante con la plantilla «credenciales_prueba».
     */
    public function credenciales(Request $request, MensajeContacto $mensaje, Correos $correos): RedirectResponse
    {
        $datos = $request->validate([
            'url_acceso' => ['required', 'url', 'max:255'],
            'usuario_prueba' => ['required', 'string', 'max:150'],
            'clave_prueba' => [$mensaje->clave_prueba ? 'nullable' : 'required', 'string', 'max:150'],
            'dias' => ['required', 'integer', 'min:1', 'max:365'],
        ], [], ['url_acceso' => 'dirección de acceso', 'usuario_prueba' => 'usuario', 'clave_prueba' => 'contraseña', 'dias' => 'días']);

        $mensaje->fill([
            'url_acceso' => $datos['url_acceso'],
            'usuario_prueba' => $datos['usuario_prueba'],
            'vence_el' => now()->addDays((int) $datos['dias'])->toDateString(),
        ]);
        if (filled($datos['clave_prueba'] ?? null)) {
            $mensaje->clave_prueba = $datos['clave_prueba'];
        }
        $mensaje->save();

        if (! $request->boolean('enviar')) {
            return back()->with('status', 'Datos de prueba guardados (no se envió correo).');
        }

        $enviado = $correos->enviar('credenciales_prueba', $mensaje->correo, [
            'nombre' => $mensaje->nombre,
            'sistema_nombre' => $mensaje->sistema?->nombre ?? 'el sistema',
            'url_acceso' => $mensaje->url_acceso,
            'usuario' => $mensaje->usuario_prueba,
            'clave' => $mensaje->clave_prueba,
            'dias' => (string) $datos['dias'],
            'vence' => $mensaje->vence_el->format('d/m/Y'),
        ], $request->user()->id);

        if (! $enviado) {
            return back()->with('aviso', 'Los datos se guardaron, pero el correo no se envió. Revisa Correos → Bitácora (¿servidor apagado?).');
        }
        $mensaje->update(['credenciales_enviadas_en' => now(), 'estado' => $mensaje->estado === 'nuevo' ? 'atendido' : $mensaje->estado]);

        return back()->with('status', "Credenciales enviadas a {$mensaje->correo}.");
    }

    public function destroy(MensajeContacto $mensaje): RedirectResponse
    {
        $mensaje->delete();

        return redirect()->route('admin.mensajes.index')->with('status', 'Mensaje eliminado.');
    }
}
