<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaSistema;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Categorías del catálogo de software (Restaurantes, Inventario, ONG…). Se administran desde «Software». */
class CategoriaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        CategoriaSistema::query()->create($datos + ['orden' => Orden::siguiente(CategoriaSistema::query())]);

        return redirect()->to(route('admin.sistemas.index').'#categorias')->with('status', 'Categoría creada.');
    }

    public function update(Request $request, CategoriaSistema $categoria): RedirectResponse
    {
        $categoria->update($this->validar($request, $categoria));

        return redirect()->to(route('admin.sistemas.index').'#categorias')->with('status', 'Categoría guardada.');
    }

    public function destroy(CategoriaSistema $categoria): RedirectResponse
    {
        $categoria->delete(); // los sistemas quedan sin categoría

        return redirect()->to(route('admin.sistemas.index').'#categorias')->with('status', 'Categoría eliminada.');
    }

    private function validar(Request $request, ?CategoriaSistema $categoria = null): array
    {
        $request->merge(['slug' => Str::slug((string) $request->input('nombre'))]);

        return collect($request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'slug' => ['required', Rule::unique('categorias_sistema', 'slug')->ignore($categoria?->id)],
            'icono' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9 \-]+$/'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], ['slug.unique' => 'Ya existe una categoría con ese nombre.']))
            ->map(fn ($v, $k) => $k === 'orden' ? (int) $v : $v)->filter(fn ($v, $k) => $k !== 'orden' || $v > 0)->all();
    }
}
