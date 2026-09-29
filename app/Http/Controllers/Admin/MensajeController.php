<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MensajeContacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Bandeja de lo que llega por el formulario de contacto. */
class MensajeController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $estado = array_key_exists((string) $estado, MensajeContacto::ESTADOS) ? $estado : null;

        return view('admin.mensajes.index', [
            'mensajes' => MensajeContacto::query()->with('sistema')
                ->when($estado, fn ($q) => $q->where('estado', $estado))
                ->latest()->paginate(20)->withQueryString(),
            'estado' => $estado,
            'conteo' => MensajeContacto::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado'),
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

    public function destroy(MensajeContacto $mensaje): RedirectResponse
    {
        $mensaje->delete();

        return redirect()->route('admin.mensajes.index')->with('status', 'Mensaje eliminado.');
    }
}
