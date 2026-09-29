<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Servicios o planes que se ofrecen en el sitio (sección «Servicios»). */
class ServicioController extends Controller
{
    public function index(): View
    {
        return view('admin.servicios.index', ['servicios' => Servicio::query()->orderBy('orden')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.servicios.form', ['servicio' => new Servicio(['visible' => true, 'orden' => Servicio::query()->max('orden') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Servicio::query()->create($this->validar($request));

        return redirect()->route('admin.servicios.index')->with('status', 'Servicio creado.');
    }

    public function edit(Servicio $servicio): View
    {
        return view('admin.servicios.form', ['servicio' => $servicio]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $servicio->update($this->validar($request));

        return redirect()->route('admin.servicios.index')->with('status', 'Servicio guardado.');
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        $servicio->delete();

        return redirect()->route('admin.servicios.index')->with('status', 'Servicio eliminado.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'precio' => ['nullable', 'string', 'max:60'],
            'periodo' => ['nullable', 'string', 'max:40'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'incluye' => ['nullable', 'string', 'max:3000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return array_merge($datos, [
            'orden' => (int) ($datos['orden'] ?? 0),
            'visible' => $request->boolean('visible'),
            'destacado' => $request->boolean('destacado'),
        ]);
    }
}
