<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Elemento;
use App\Models\Seccion;
use App\Support\Bloques;
use App\Support\Imagenes;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Elementos repetibles de una sección: fotos del carrusel, tarjetas, tecnologías, cifras, preguntas… */
class ElementoController extends Controller
{
    public function store(Request $request, Seccion $seccion): RedirectResponse
    {
        abort_unless(Bloques::tieneElementos($seccion->tipo), 404);
        $obligatoria = Bloques::TIPOS[$seccion->tipo]['elementos']['imagen_obligatoria'] ?? false;
        if ($obligatoria && ! $request->hasFile('imagen')) {
            throw ValidationException::withMessages(['imagen' => 'Elige la foto.']);
        }
        $elemento = $seccion->elementos()->create($this->validar($request) + ['orden' => Orden::siguiente($seccion->elementos()->getQuery())]);
        Imagenes::guardarCampos($request, $elemento, ['imagen'], 'secciones/'.$seccion->id);

        return redirect()->to(route('admin.secciones.edit', $seccion).'#elementos')->with('status', 'Agregado.');
    }

    public function update(Request $request, Elemento $elemento): RedirectResponse
    {
        $elemento->update($this->validar($request) + ['visible' => $request->boolean('visible')]);
        Imagenes::guardarCampos($request, $elemento, ['imagen'], 'secciones/'.$elemento->seccion_id);

        return redirect()->to(route('admin.secciones.edit', $elemento->seccion_id).'#e'.$elemento->id)->with('status', 'Guardado.');
    }

    public function destroy(Elemento $elemento): RedirectResponse
    {
        $elemento->delete();
        Imagenes::borrar($elemento->imagen);

        return redirect()->to(route('admin.secciones.edit', $elemento->seccion_id).'#elementos')->with('status', 'Eliminado.');
    }

    public function mover(Elemento $elemento, string $direccion): RedirectResponse
    {
        Orden::mover($elemento, $direccion, Elemento::query()->where('seccion_id', $elemento->seccion_id));

        return redirect()->to(route('admin.secciones.edit', $elemento->seccion_id).'#e'.$elemento->id);
    }

    private function validar(Request $request): array
    {
        return collect($request->validate([
            'grupo' => ['nullable', 'string', 'max:80'],
            'titulo' => ['nullable', 'string', 'max:200'],
            'subtitulo' => ['nullable', 'string', 'max:200'],
            'texto' => ['nullable', 'string', 'max:5000'],
            'icono' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9 \-]+$/'],
            'enlace' => Bloques::REGLA_ENLACE,
            'enlace_texto' => ['nullable', 'string', 'max:60'],
            'valor' => ['nullable', 'string', 'max:30'],
            'imagen' => Imagenes::regla(),
        ], [
            'enlace.regex' => Bloques::MENSAJE_ENLACE,
            'icono.regex' => 'El ícono debe ser una clase de Font Awesome, p. ej. «fa-solid fa-code».',
        ] + Imagenes::MENSAJES))->except('imagen')->all();
    }
}
