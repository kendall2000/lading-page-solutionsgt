<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sistema;
use App\Models\SistemaImagen;
use App\Support\Imagenes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/** Los sistemas que se muestran en el sitio, con sus capturas. */
class SistemaController extends Controller
{
    public function index(): View
    {
        return view('admin.sistemas.index', [
            'sistemas' => Sistema::query()->withCount(['imagenes', 'clientes'])->orderBy('orden')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sistemas.form', ['sistema' => new Sistema(['visible' => true, 'icono' => 'fa-solid fa-laptop-code', 'orden' => Sistema::query()->max('orden') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sistema = Sistema::query()->create($this->validar($request));
        Imagenes::guardarCampos($request, $sistema, ['imagen', 'imagen_oscura'], 'sistemas');

        return redirect()->route('admin.sistemas.edit', $sistema)->with('status', 'Sistema creado. Ahora puedes agregar capturas de pantalla.');
    }

    public function edit(Sistema $sistema): View
    {
        return view('admin.sistemas.form', ['sistema' => $sistema->load('imagenes')]);
    }

    public function update(Request $request, Sistema $sistema): RedirectResponse
    {
        $sistema->update($this->validar($request, $sistema));
        Imagenes::guardarCampos($request, $sistema, ['imagen', 'imagen_oscura'], 'sistemas');

        return back()->with('status', 'Sistema guardado.');
    }

    public function destroy(Sistema $sistema): RedirectResponse
    {
        $rutas = $sistema->imagenes->pluck('ruta')->push($sistema->imagen, $sistema->imagen_oscura);
        $sistema->delete();
        $rutas->each(fn ($r) => Imagenes::borrar($r));

        return redirect()->route('admin.sistemas.index')->with('status', 'Sistema eliminado.');
    }

    /** Agrega una o varias capturas a la galería del sistema. */
    public function subirImagenes(Request $request, Sistema $sistema): RedirectResponse
    {
        $request->validate([
            'capturas' => ['required', 'array', 'max:12'],
            'capturas.*' => ['file', 'max:4096', 'mimes:png,jpg,jpeg,webp'],
        ], ['capturas.required' => 'Elige al menos una imagen.', 'capturas.*.mimes' => Imagenes::MENSAJES['mimes'], 'capturas.*.max' => Imagenes::MENSAJES['max']]);

        $orden = (int) $sistema->imagenes()->max('orden');
        try {
            foreach ($request->file('capturas') as $archivo) {
                $sistema->imagenes()->create([
                    'ruta' => Imagenes::subir($archivo, 'sistemas/'.$sistema->id),
                    'titulo' => Str::limit(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME), 140, ''),
                    'orden' => ++$orden,
                ]);
            }
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['capturas' => 'Alguna imagen no se pudo subir a Contabo. Intenta de nuevo.']);
        }

        return back()->with('status', 'Capturas agregadas.');
    }

    public function actualizarImagen(Request $request, Sistema $sistema, SistemaImagen $imagen): RedirectResponse
    {
        abort_unless($imagen->sistema_id === $sistema->id, 404);
        $imagen->update($request->validate([
            'titulo' => ['nullable', 'string', 'max:150'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]));

        return back()->with('status', 'Captura actualizada.');
    }

    public function borrarImagen(Sistema $sistema, SistemaImagen $imagen): RedirectResponse
    {
        abort_unless($imagen->sistema_id === $sistema->id, 404);
        $imagen->delete();
        Imagenes::borrar($imagen->ruta);

        return back()->with('status', 'Captura eliminada.');
    }

    private function validar(Request $request, ?Sistema $sistema = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('nombre'))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique('sistemas', 'slug')->ignore($sistema?->id)],
            'resumen' => ['required', 'string', 'max:300'],
            'descripcion' => ['nullable', 'string', 'max:10000'],
            'icono' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9 \-]+$/'],
            'caracteristicas' => ['nullable', 'string', 'max:5000'],
            'tecnologias' => ['nullable', 'string', 'max:300'],
            'url_demo' => ['nullable', 'url', 'max:255'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'imagen' => Imagenes::regla(),
            'imagen_oscura' => Imagenes::regla(),
        ], ['slug.unique' => 'Ya hay otro sistema con esa dirección web.', 'icono.regex' => 'El ícono debe ser una clase de Font Awesome, p. ej. «fa-solid fa-utensils».'] + Imagenes::MENSAJES);

        return array_merge(collect($datos)->except(['imagen', 'imagen_oscura'])->all(), [
            'icono' => ($datos['icono'] ?? null) ?: 'fa-solid fa-laptop-code',
            'orden' => (int) ($datos['orden'] ?? 0),
            'visible' => $request->boolean('visible'),
            'destacado' => $request->boolean('destacado'),
        ]);
    }
}
