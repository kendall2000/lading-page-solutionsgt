<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\User;
use App\Support\Sitio;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErroresYPrivacidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_pagina_404_tiene_el_diseno_del_sitio(): void
    {
        $this->get('/no-existe')->assertNotFound()
            ->assertSee('No encontramos esta página')
            ->assertSee('404-illustration.png', false)
            ->assertSee('Ir al inicio')
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_las_demas_paginas_de_error_se_arman(): void
    {
        foreach ([403 => 'Acceso restringido', 419 => 'La página expiró', 429 => 'Demasiados intentos', 500 => 'Algo salió mal', 503 => 'Estamos en mantenimiento'] as $codigo => $titulo) {
            $html = view("errors.{$codigo}")->render();
            $this->assertStringContainsString($titulo, $html, "Error {$codigo}");
        }
        $this->assertStringNotContainsString('Ir al inicio', view('errors.503')->render());
    }

    public function test_la_politica_de_privacidad_existe_fuera_del_menu_y_se_enlaza(): void
    {
        $this->get('/privacidad')->assertOk()->assertSee('Política de privacidad')->assertSee('cookies necesarias');

        $this->get('/contactenos')->assertOk()
            ->assertSee('href="'.url('/privacidad').'"', false)
            ->assertSee('Al enviar aceptas nuestra');
        $this->assertFalse(Sitio::menu()->pluck('slug')->contains('privacidad'));
        $this->get('/sitemap.xml')->assertSee(url('/privacidad'));
    }

    public function test_si_se_oculta_la_privacidad_desaparecen_los_enlaces(): void
    {
        Pagina::query()->where('slug', 'privacidad')->update(['visible' => false]);

        $this->get('/contactenos')->assertOk()->assertDontSee('Al enviar aceptas nuestra')->assertDontSee(url('/privacidad'), false);
    }

    public function test_el_seeder_no_duplica_ni_repone_lo_editado(): void
    {
        Pagina::query()->where('slug', 'privacidad')->update(['titulo' => 'Aviso de privacidad']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Pagina::query()->where('slug', 'privacidad')->count());
        $this->assertSame('Aviso de privacidad', Pagina::query()->where('slug', 'privacidad')->value('titulo'));
        $this->assertTrue(Pagina::query()->where('slug', 'nosotros')->exists());
    }

    public function test_el_bloque_texto_se_puede_alinear_a_la_izquierda_desde_el_panel(): void
    {
        $seccion = Pagina::query()->where('slug', 'privacidad')->firstOrFail()->secciones()->firstOrFail();

        $this->actingAs(User::query()->firstOrFail())->get(route('admin.secciones.edit', $seccion))
            ->assertOk()->assertSee('A la izquierda (textos largos)');
        $this->put(route('admin.secciones.update', $seccion), [
            'contenido' => $seccion->contenido, 'visible' => 1, 'opciones' => ['alineacion' => 'centro'],
        ])->assertSessionHasNoErrors();
        $this->assertSame('centro', $seccion->fresh()->opcion('alineacion'));
        $this->put(route('admin.secciones.update', $seccion), ['visible' => 1, 'opciones' => ['alineacion' => 'derecha']])
            ->assertSessionHasErrors('opciones.alineacion');
    }
}
