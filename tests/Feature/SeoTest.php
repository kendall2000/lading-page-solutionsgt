<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\Pagina;
use App\Models\Sistema;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_el_mapa_del_sitio_lista_solo_lo_publicado(): void
    {
        $sistema = Sistema::query()->where('visible', true)->firstOrFail();
        Manual::query()->create(['sistema_id' => $sistema->id, 'titulo' => 'Cómo abrir caja', 'slug' => 'como-abrir-caja', 'visible' => true]);
        Manual::query()->create(['titulo' => 'Borrador', 'slug' => 'borrador', 'visible' => false]);
        Pagina::query()->where('slug', 'manuales')->update(['visible' => false]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
            ->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<loc>'.url('/nosotros').'</loc>', false)
            ->assertSee('<loc>'.route('sistema', $sistema->slug).'</loc>', false)
            ->assertSee('<loc>'.url('/manuales/como-abrir-caja').'</loc>', false)
            ->assertDontSee(url('/manuales/borrador'), false)
            ->assertDontSee('<loc>'.url('/manuales').'</loc>', false)
            ->assertDontSee('<loc>'.url('/inicio').'</loc>', false);
    }

    public function test_robots_bloquea_todo_fuera_de_produccion(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false)->assertDontSee('Sitemap:');
    }

    public function test_robots_en_produccion_oculta_el_panel_y_anuncia_el_mapa(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertDontSee("Disallow: /\n", false);
    }

    public function test_las_paginas_llevan_canonica_y_datos_de_la_empresa(): void
    {
        $this->get('/nosotros?utm_source=facebook')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/nosotros').'">', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('<meta property="og:locale" content="es_GT">', false);
    }

    public function test_la_ficha_de_un_sistema_y_de_un_manual_para_google(): void
    {
        $sistema = Sistema::query()->where('visible', true)->firstOrFail();
        $sistema->update(['modalidad' => 'gratis', 'resumen' => 'Resumen con </script> dentro']);
        Manual::query()->create(['sistema_id' => $sistema->id, 'titulo' => 'Cómo abrir caja', 'slug' => 'como-abrir-caja', 'visible' => true]);

        $this->get(route('sistema', $sistema->slug))
            ->assertOk()
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('"price":"0"', false)
            // Un texto del panel no puede cerrar la etiqueta <script>.
            ->assertDontSee('Resumen con </script>', false);

        $this->get('/manuales/como-abrir-caja')
            ->assertOk()
            ->assertSee('"@type":"TechArticle"', false)
            ->assertSee('"headline":"Cómo abrir caja"', false);
    }
}
