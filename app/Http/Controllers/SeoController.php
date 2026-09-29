<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use App\Models\Pagina;
use App\Models\Sistema;
use Illuminate\Http\Response;

/** Archivos para buscadores: mapa del sitio y robots.txt (se arman solos con lo publicado). */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $inicio = Pagina::query()->publicas()->where('es_inicio', true)->first();
        $enlaces = collect([['loc' => route('inicio'), 'lastmod' => $inicio?->updated_at, 'priority' => '1.0']])
            ->concat(Pagina::query()->publicas()->where('es_inicio', false)->orderBy('orden')->get()
                ->map(fn (Pagina $p) => ['loc' => $p->enlace(), 'lastmod' => $p->updated_at, 'priority' => '0.8']))
            ->concat(Sistema::query()->publicos()->get()
                ->map(fn (Sistema $s) => ['loc' => route('sistema', $s->slug), 'lastmod' => $s->updated_at, 'priority' => '0.7']))
            // Los manuales «solo clientes» no van al mapa (piden entrar con una cuenta).
            ->concat(Manual::query()->publicos()->where('solo_clientes', false)->get()
                ->map(fn (Manual $m) => ['loc' => route('manual', $m->slug), 'lastmod' => $m->updated_at, 'priority' => '0.5']));

        return response()->view('seo.sitemap', ['enlaces' => $enlaces], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        // Fuera de producción (pruebas, copia local) no se deja indexar nada.
        $lineas = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /login', 'Disallow: /forgot-password', 'Disallow: /reset-password',
                'Disallow: /two-factor-challenge', 'Disallow: /chat/', 'Disallow: /broadcasting/', 'Disallow: /mi-cuenta', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lineas)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
