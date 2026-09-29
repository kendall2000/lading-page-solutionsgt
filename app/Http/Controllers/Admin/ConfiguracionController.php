<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSitio;
use App\Support\Imagenes;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Textos, contacto, redes, colores e imágenes del sitio público. */
class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        return view('admin.configuracion', ['cfg' => ConfiguracionSitio::query()->firstOrCreate([])]);
    }

    public function update(Request $request): RedirectResponse
    {
        $imagenes = array_keys(ConfiguracionSitio::IMAGENES);
        $url = ['nullable', 'url', 'max:255'];

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'eslogan' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'horario' => ['nullable', 'string', 'max:150'],
            'facebook' => $url, 'instagram' => $url, 'linkedin' => $url, 'tiktok' => $url, 'youtube' => $url, 'github' => $url,
            'color_primario' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'meta_descripcion' => ['nullable', 'string', 'max:300'],
            'chat_titulo' => ['nullable', 'string', 'max:80'],
            'chat_bienvenida' => ['nullable', 'string', 'max:300'],
            ...collect($imagenes)->mapWithKeys(fn ($c) => [$c => Imagenes::regla($c === 'favicon' ? 512 : 4096, $c === 'favicon' ? 'png,ico,jpg,jpeg,webp' : 'png,jpg,jpeg,webp')])->all(),
        ], ['color_primario.regex' => 'El color debe ser como #3874ff.'] + Imagenes::MENSAJES);

        $cfg = ConfiguracionSitio::query()->firstOrCreate([]);
        $cfg->fill(collect($datos)->except($imagenes)->all() + ['chat_activo' => $request->boolean('chat_activo')])->save();

        try {
            Imagenes::guardarCampos($request, $cfg, $imagenes, 'sitio');
        } finally {
            Sitio::olvidar();
        }

        return back()->with('status', 'Datos del sitio guardados.');
    }
}
