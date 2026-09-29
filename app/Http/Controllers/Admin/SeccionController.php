<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaSistema;
use App\Models\Pagina;
use App\Models\Seccion;
use App\Models\Sistema;
use App\Support\Bloques;
use App\Support\Imagenes;
use App\Support\Orden;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Secciones (bloques) de una página: se agregan, editan, ordenan, ocultan o borran. */
class SeccionController extends Controller
{
    public function store(Request $request, Pagina $pagina): RedirectResponse
    {
        $request->validate(['tipo' => ['required', Rule::in(array_keys(Bloques::TIPOS))]]);
        $tipo = $request->input('tipo');

        $seccion = $pagina->secciones()->create([
            'tipo' => $tipo,
            'titulo' => in_array($tipo, ['carrusel'], true) ? null : Bloques::TIPOS[$tipo]['nombre'],
            'orden' => Orden::siguiente($pagina->secciones()->getQuery()),
            'opciones' => match ($tipo) {
                'tarjetas' => ['columnas' => '3'],
                'sistemas' => ['estilo' => 'tarjetas', 'filtros' => true],
                'contacto' => ['mapa' => true],
                'llamado' => ['whatsapp' => true],
                default => [],
            },
        ]);
        Sitio::olvidar();

        return redirect()->route('admin.secciones.edit', $seccion)->with('status', 'Sección agregada: complétala y guarda.');
    }

    public function edit(Seccion $seccion): View
    {
        return view('admin.secciones.form', [
            'seccion' => $seccion->load(['pagina', 'elementos']),
            'tipo' => Bloques::TIPOS[$seccion->tipo],
            'categorias' => CategoriaSistema::query()->orderBy('orden')->orderBy('nombre')->pluck('nombre', 'id'),
            'sistemas' => Sistema::query()->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function update(Request $request, Seccion $seccion): RedirectResponse
    {
        $datos = $request->validate([
            'etiqueta' => ['nullable', 'string', 'max:80'],
            'titulo' => ['nullable', 'string', 'max:200'],
            'contenido' => ['nullable', 'string', 'max:20000'],
            'boton_texto' => ['nullable', 'string', 'max:60'],
            'boton_enlace' => Bloques::REGLA_ENLACE,
            'boton2_texto' => ['nullable', 'string', 'max:60'],
            'boton2_enlace' => Bloques::REGLA_ENLACE,
            'fondo' => ['nullable', Rule::in(array_keys(Bloques::FONDOS))],
            'opciones' => ['nullable', 'array'],
            'opciones.resaltado' => ['nullable', 'string', 'max:60'],
            'opciones.altura' => ['nullable', Rule::in(['normal', 'alta', 'pantalla'])],
            'opciones.lado' => ['nullable', Rule::in(['derecha', 'izquierda'])],
            'opciones.alineacion' => ['nullable', Rule::in(['centro', 'izquierda'])],
            'opciones.columnas' => ['nullable', Rule::in(['2', '3', '4'])],
            'opciones.estilo' => ['nullable', Rule::in(['tarjetas', 'filas'])],
            'opciones.categoria_id' => ['nullable', 'integer', 'exists:categorias_sistema,id'],
            'opciones.sistema_id' => ['nullable', 'integer', 'exists:sistemas,id'],
            'opciones.limite' => ['nullable', 'integer', 'min:0', 'max:100'],
            'imagen' => Imagenes::regla(),
            'imagen_oscura' => Imagenes::regla(),
        ], [
            'boton_enlace.regex' => Bloques::MENSAJE_ENLACE,
            'boton2_enlace.regex' => Bloques::MENSAJE_ENLACE,
        ] + Imagenes::MENSAJES);

        $opciones = array_filter($datos['opciones'] ?? [], fn ($v) => $v !== null && $v !== '');
        foreach (['solo_destacados', 'filtros', 'mapa', 'whatsapp'] as $check) {
            if (in_array($check, Bloques::TIPOS[$seccion->tipo]['opciones'], true)) {
                $opciones[$check] = $request->boolean("opciones.{$check}");
            }
        }

        $seccion->update([
            ...collect($datos)->except(['opciones', 'imagen', 'imagen_oscura'])->all(),
            'fondo' => $datos['fondo'] ?? 'claro',
            'opciones' => $opciones,
            'visible' => $request->boolean('visible'),
        ]);
        Imagenes::guardarCampos($request, $seccion, ['imagen', 'imagen_oscura'], 'secciones');
        Sitio::olvidar();

        return back()->with('status', 'Sección guardada.');
    }

    public function destroy(Seccion $seccion): RedirectResponse
    {
        $pagina = $seccion->pagina;
        $imagenes = $seccion->elementos()->pluck('imagen')->push($seccion->imagen, $seccion->imagen_oscura);
        $seccion->delete();
        $imagenes->filter()->each(fn ($r) => Imagenes::borrar($r));
        Sitio::olvidar();

        return redirect()->route('admin.paginas.edit', $pagina)->with('status', 'Sección eliminada.');
    }

    public function mover(Seccion $seccion, string $direccion): RedirectResponse
    {
        Orden::mover($seccion, $direccion, Seccion::query()->where('pagina_id', $seccion->pagina_id));

        return back();
    }

    /** Mostrar u ocultar sin entrar a editarla. */
    public function alternar(Seccion $seccion): RedirectResponse
    {
        $seccion->update(['visible' => ! $seccion->visible]);
        Sitio::olvidar();

        return back()->with('status', $seccion->visible ? 'Sección visible.' : 'Sección oculta.');
    }
}
