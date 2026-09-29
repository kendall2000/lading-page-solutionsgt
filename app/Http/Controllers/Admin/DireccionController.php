<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Direccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Oficinas o puntos de atención que aparecen en «Contacto», con su mapa. */
class DireccionController extends Controller
{
    public function index(): View
    {
        return view('admin.direcciones.index', ['direcciones' => Direccion::query()->orderByDesc('principal')->orderBy('orden')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.direcciones.form', ['direccion' => new Direccion(['visible' => true, 'principal' => ! Direccion::query()->exists()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->marcarPrincipal(Direccion::query()->create($this->validar($request)));

        return redirect()->route('admin.direcciones.index')->with('status', 'Dirección creada.');
    }

    public function edit(Direccion $direccion): View
    {
        return view('admin.direcciones.form', ['direccion' => $direccion]);
    }

    public function update(Request $request, Direccion $direccion): RedirectResponse
    {
        $direccion->update($this->validar($request));
        $this->marcarPrincipal($direccion);

        return redirect()->route('admin.direcciones.index')->with('status', 'Dirección guardada.');
    }

    public function destroy(Direccion $direccion): RedirectResponse
    {
        $direccion->delete();

        return redirect()->route('admin.direcciones.index')->with('status', 'Dirección eliminada.');
    }

    /** Solo una dirección puede ser la principal (la del mapa grande). */
    private function marcarPrincipal(Direccion $direccion): void
    {
        if ($direccion->principal) {
            Direccion::query()->whereKeyNot($direccion->id)->update(['principal' => false]);
        }
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'direccion' => ['required', 'string', 'max:300'],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'horario' => ['nullable', 'string', 'max:150'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return array_merge($datos, [
            'orden' => (int) ($datos['orden'] ?? 0),
            'visible' => $request->boolean('visible'),
            'principal' => $request->boolean('principal'),
        ]);
    }
}
