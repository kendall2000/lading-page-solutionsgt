<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSitio;
use App\Models\Pagina;
use App\Models\Seccion;
use App\Support\Imagenes;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Configuración del sistema (pestañas como el módulo del restaurante): empresa y contacto,
 * imágenes e íconos, fotos de inicio, colores, redes, chat e inicio de sesión.
 */
class ConfiguracionController extends Controller
{
    public const PESTANAS = [
        'empresa' => ['briefcase', 'Empresa y contacto'],
        'imagenes' => ['image', 'Logo e íconos'],
        'inicio' => ['camera', 'Fotos de inicio'],
        'colores' => ['droplet', 'Colores'],
        'redes' => ['share-2', 'Redes sociales'],
        'chat' => ['message-circle', 'Chat en vivo'],
        'acceso' => ['log-in', 'Inicio de sesión'],
    ];

    public function edit(): View
    {
        return view('admin.configuracion', [
            'cfg' => ConfiguracionSitio::query()->firstOrCreate([]),
            'carrusel' => $this->carrusel()?->load('elementos'),
            'portada' => $this->portada(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $imagenes = array_keys(ConfiguracionSitio::IMAGENES);
        $url = ['nullable', 'url', 'max:255'];
        $color = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'eslogan' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'horario' => ['nullable', 'string', 'max:150'],
            'facebook' => $url, 'instagram' => $url, 'linkedin' => $url, 'tiktok' => $url, 'youtube' => $url, 'github' => $url,
            'color_primario' => $color,
            'color_secundario' => $color,
            'meta_descripcion' => ['nullable', 'string', 'max:300'],
            'pie_texto' => ['nullable', 'string', 'max:200'],
            'chat_titulo' => ['nullable', 'string', 'max:80'],
            'chat_bienvenida' => ['nullable', 'string', 'max:300'],
            'login_titulo' => ['nullable', 'string', 'max:100'],
            'login_subtitulo' => ['nullable', 'string', 'max:200'],
            ...collect($imagenes)->mapWithKeys(fn ($c) => [$c => Imagenes::regla($c === 'favicon' ? 512 : 4096, $c === 'favicon' ? 'png,ico,jpg,jpeg,webp' : 'png,jpg,jpeg,webp')])->all(),
            // Fotos de inicio (carrusel) y portada.
            'fotos_inicio' => ['nullable', 'array', 'max:10'],
            'fotos_inicio.*' => ['file', 'max:6144', 'mimes:png,jpg,jpeg,webp'],
            'quitar_fotos' => ['nullable', 'array'],
            'quitar_fotos.*' => ['integer'],
            'portada_imagen' => Imagenes::regla(),
            'portada_imagen_oscura' => Imagenes::regla(),
        ], [
            'color_primario.regex' => 'El color debe ser como #3874ff.',
            'color_secundario.regex' => 'El color debe ser como #38abff.',
            'fotos_inicio.*.mimes' => 'Las fotos deben ser PNG, JPG o WEBP.',
            'fotos_inicio.*.max' => 'Cada foto puede pesar hasta 6 MB.',
        ] + Imagenes::MENSAJES);

        $cfg = ConfiguracionSitio::query()->firstOrCreate([]);
        $cfg->fill(collect($datos)->except([...$imagenes, 'fotos_inicio', 'quitar_fotos', 'portada_imagen', 'portada_imagen_oscura'])->all()
            + ['chat_activo' => $request->boolean('chat_activo')])->save();

        try {
            Imagenes::guardarCampos($request, $cfg, $imagenes, 'sitio');
            $this->guardarFotosInicio($request);
            $this->guardarPortada($request);
        } finally {
            Sitio::olvidar();
        }

        return redirect()->route('admin.sitio.edit', ['pestana' => $request->input('pestana')])->with('status', 'Configuración guardada.');
    }

    /** Sección «Carrusel de fotos» de la página de inicio (se crea arriba si no existe y suben fotos). */
    private function carrusel(bool $crear = false): ?Seccion
    {
        $inicio = Pagina::query()->where('es_inicio', true)->first();
        $seccion = $inicio?->secciones()->where('tipo', 'carrusel')->first();
        if (! $seccion && $crear && $inicio) {
            Seccion::query()->where('pagina_id', $inicio->id)->increment('orden');
            $seccion = $inicio->secciones()->create(['tipo' => 'carrusel', 'orden' => 1, 'opciones' => ['altura' => 'alta']]);
        }

        return $seccion;
    }

    private function portada(): ?Seccion
    {
        return Pagina::query()->where('es_inicio', true)->first()?->secciones()->where('tipo', 'portada')->first();
    }

    private function guardarFotosInicio(Request $request): void
    {
        $quitar = array_map('intval', $request->input('quitar_fotos', []));
        if ($quitar && ($carrusel = $this->carrusel())) {
            $carrusel->elementos()->whereIn('id', $quitar)->get()->each(function ($e) {
                $e->delete();
                Imagenes::borrar($e->imagen);
            });
        }
        if (! $request->hasFile('fotos_inicio')) {
            return;
        }
        $carrusel = $this->carrusel(crear: true);
        $orden = (int) $carrusel->elementos()->max('orden');
        try {
            foreach ($request->file('fotos_inicio') as $archivo) {
                $carrusel->elementos()->create(['imagen' => Imagenes::subir($archivo, 'secciones/'.$carrusel->id), 'orden' => ++$orden]);
            }
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['fotos_inicio' => 'Alguna foto no se pudo subir a Contabo. Intenta de nuevo.']);
        }
        $carrusel->update(['visible' => true]);
    }

    /** Imagen de la portada del inicio (campos «imagen» e «imagen_oscura» de esa sección). */
    private function guardarPortada(Request $request): void
    {
        $portada = $this->portada();
        if (! $portada) {
            return;
        }
        try {
            foreach (['portada_imagen' => 'imagen', 'portada_imagen_oscura' => 'imagen_oscura'] as $campo => $columna) {
                $anterior = $portada->{$columna};
                if ($request->hasFile($campo)) {
                    $portada->update([$columna => Imagenes::subir($request->file($campo), 'secciones/'.$portada->id)]);
                } elseif ($request->boolean("quitar_{$campo}")) {
                    $portada->update([$columna => null]);
                } else {
                    continue;
                }
                Imagenes::borrar($anterior);
            }
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['portada_imagen' => 'La imagen de la portada no se pudo subir a Contabo. Intenta de nuevo.']);
        }
    }
}
