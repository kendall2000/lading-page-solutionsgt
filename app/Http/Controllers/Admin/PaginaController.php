<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use App\Support\Imagenes;
use App\Support\Orden;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Páginas del sitio (menú): Inicio, Nosotros, Servicios, Software, Manuales, Contáctenos y las que se agreguen. */
class PaginaController extends Controller
{
    public function index(): View
    {
        $paginas = Pagina::query()->withCount('secciones')->orderByDesc('es_inicio')->orderBy('orden')->orderBy('id')->get();

        return view('admin.paginas.index', [
            'principales' => $paginas->whereNull('padre_id')->values(),
            'hijas' => $paginas->whereNotNull('padre_id')->groupBy('padre_id'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.paginas.form', [
            'pagina' => new Pagina(['visible' => true, 'en_menu' => true, 'padre_id' => $request->integer('padre') ?: null]),
            'padres' => $this->padres(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['orden'] = Orden::siguiente(Pagina::query()->where('padre_id', $datos['padre_id']));
        $pagina = Pagina::query()->create($datos);
        Imagenes::guardarCampos($request, $pagina, ['imagen_encabezado'], 'paginas');
        Sitio::olvidar();

        return redirect()->route('admin.paginas.edit', $pagina)->with('status', 'Página creada. Ahora agrégale secciones.');
    }

    public function edit(Pagina $pagina): View
    {
        return view('admin.paginas.form', [
            'pagina' => $pagina->load(['secciones' => fn ($q) => $q->withCount('elementos')]),
            'padres' => $this->padres($pagina),
        ]);
    }

    public function update(Request $request, Pagina $pagina): RedirectResponse
    {
        $pagina->update($this->validar($request, $pagina));
        Imagenes::guardarCampos($request, $pagina, ['imagen_encabezado'], 'paginas');
        Sitio::olvidar();

        return back()->with('status', 'Página guardada.');
    }

    public function destroy(Pagina $pagina): RedirectResponse
    {
        if ($pagina->es_inicio) {
            return back()->withErrors(['pagina' => 'La página de inicio no se puede borrar (puedes editar sus secciones).']);
        }
        $imagenes = $pagina->secciones()->with('elementos')->get()
            ->flatMap(fn ($s) => $s->elementos->pluck('imagen')->push($s->imagen, $s->imagen_oscura))
            ->push($pagina->imagen_encabezado);
        $pagina->hijas()->update(['padre_id' => null]);
        $pagina->delete();
        $imagenes->filter()->each(fn ($r) => Imagenes::borrar($r));
        Sitio::olvidar();

        return redirect()->route('admin.paginas.index')->with('status', 'Página eliminada.');
    }

    public function mover(Pagina $pagina, string $direccion): RedirectResponse
    {
        Orden::mover($pagina, $direccion, Pagina::query()->where('padre_id', $pagina->padre_id)->where('es_inicio', false));
        Sitio::olvidar();

        return back();
    }

    private function padres(?Pagina $excepto = null)
    {
        return Pagina::query()->whereNull('padre_id')->where('es_inicio', false)
            ->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id))->orderBy('orden')->pluck('titulo', 'id');
    }

    private function validar(Request $request, ?Pagina $pagina = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('titulo'))]);

        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'titulo_menu' => ['nullable', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:140', Rule::notIn(Pagina::RESERVADAS), Rule::unique('paginas', 'slug')->ignore($pagina?->id)],
            'subtitulo' => ['nullable', 'string', 'max:300'],
            'meta_descripcion' => ['nullable', 'string', 'max:300'],
            'padre_id' => ['nullable', 'integer', Rule::exists('paginas', 'id')->whereNull('padre_id')],
            'imagen_encabezado' => Imagenes::regla(),
        ], [
            'slug.unique' => 'Ya hay otra página con esa dirección.',
            'slug.not_in' => 'Esa dirección la usa el sistema; elige otra.',
        ] + Imagenes::MENSAJES);

        // La página de inicio no va dentro de otra, y una página con submenú no puede volverse hija.
        $padre = $pagina?->es_inicio || $pagina?->hijas()->exists() ? null : ($datos['padre_id'] ?? null);

        return [
            ...collect($datos)->except('imagen_encabezado')->all(),
            'padre_id' => $padre,
            'visible' => $pagina?->es_inicio ? true : $request->boolean('visible'),
            'en_menu' => $request->boolean('en_menu'),
        ];
    }
}
