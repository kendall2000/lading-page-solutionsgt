<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\MensajeContacto;
use App\Models\Servicio;
use App\Models\Sistema;
use App\Services\Correos;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sitio público: portada, detalle de cada sistema y formulario de contacto. */
class SitioController extends Controller
{
    public function inicio(): View
    {
        $sistemas = Sistema::query()->publicos()->with('imagenes')->get();
        $clientes = Cliente::query()->orderBy('orden')->orderBy('empresa')->get();

        return view('publico.inicio', [
            'cfg' => Sitio::config(),
            'sistemas' => $sistemas,
            'servicios' => Servicio::query()->publicos()->get(),
            'logos' => $clientes->where('mostrar_logo', true)->filter(fn ($c) => $c->logo),
            'testimonios' => $clientes->where('mostrar_testimonio', true)->filter(fn ($c) => filled($c->testimonio))->values(),
            'direcciones' => Direccion::query()->publicas()->get(),
            'totalClientes' => $clientes->count(),
        ]);
    }

    public function sistema(Sistema $sistema): View
    {
        abort_unless($sistema->visible, 404);
        $sistema->increment('visitas');

        return view('publico.sistema', [
            'cfg' => Sitio::config(),
            'sistema' => $sistema->load('imagenes'),
            'clientes' => $sistema->clientes()->where('mostrar_testimonio', true)->whereNotNull('testimonio')->get(),
            'otros' => Sistema::query()->publicos()->whereKeyNot($sistema->id)->limit(3)->get(),
        ]);
    }

    public function contacto(Request $request, Correos $correos): RedirectResponse
    {
        // Campo trampa: los robots lo llenan, las personas no lo ven.
        if (filled($request->input('empresa_web'))) {
            return redirect()->to(url()->previous().'#contacto')->with('contacto_ok', true);
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'empresa' => ['nullable', 'string', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sistema_id' => ['nullable', 'exists:sistemas,id'],
            'mensaje' => ['required', 'string', 'max:3000'],
        ], [], [
            'nombre' => 'nombre', 'correo' => 'correo', 'mensaje' => 'mensaje', 'telefono' => 'teléfono',
        ]);

        $mensaje = MensajeContacto::query()->create($datos + ['ip' => $request->ip()]);

        // Correos con plantilla (Panel → Correos): aviso a «Avisos a» y confirmación al visitante.
        // Si el servidor está apagado o falla, el mensaje igual queda en el panel y el intento en la bitácora.
        $correos->avisar('nuevo_mensaje', [
            'nombre' => $mensaje->nombre, 'empresa' => $mensaje->empresa ?: '—', 'correo' => $mensaje->correo,
            'telefono' => $mensaje->telefono ?: '—', 'sistema_interes' => $mensaje->sistema?->nombre ?? '—',
            'mensaje' => $mensaje->mensaje, 'enlace' => route('admin.mensajes.show', $mensaje),
        ], $mensaje->correo);
        $correos->enviar('confirmacion_contacto', $mensaje->correo, [
            'nombre' => $mensaje->nombre, 'mensaje' => $mensaje->mensaje, 'enlace' => route('inicio').'#sistemas',
        ]);

        return redirect()->to(url()->previous().'#contacto')->with('contacto_ok', true);
    }
}
