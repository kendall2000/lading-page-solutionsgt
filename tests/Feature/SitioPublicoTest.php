<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\Manual;
use App\Models\MensajeContacto;
use App\Models\Pagina;
use App\Models\Sistema;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitioPublicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_portada_se_arma_con_sus_secciones_y_el_menu_con_las_paginas(): void
    {
        Cliente::query()->create(['empresa' => 'Café Central', 'contacto' => 'Ana López', 'testimonio' => 'Excelente sistema.', 'mostrar_testimonio' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('que ordenan tu negocio')
            ->assertSee('Sistema para Restaurantes')
            ->assertSee('Excelente sistema.')
            // Menú dinámico
            ->assertSee(['Nosotros', 'Servicios', 'Software', 'Manuales', 'Contáctenos'])
            ->assertSee(url('/contactenos'), false)
            ->assertDontSee('polyfill.io');
    }

    public function test_todas_las_paginas_iniciales_abren(): void
    {
        $this->get('/nosotros')->assertOk()->assertSee('Quiénes somos')->assertSee('Laravel')->assertSee('Bases de datos');
        $this->get('/servicios')->assertOk()->assertSee('Diseño Web')->assertSee('E-commerce Responsive')->assertSee('Desarrollo a la medida');
        $this->get('/software')->assertOk()->assertSee('AutoDMV Pro')->assertSee('Restaurantes')->assertSee('Pedir demo');
        $this->get('/manuales')->assertOk()->assertSee('Todavía no hay manuales');
        $this->get('/contactenos')->assertOk()->assertSee('Envíanos un mensaje');
        $this->get('/inicio')->assertRedirect('/');
        $this->get('/no-existe')->assertNotFound();
    }

    public function test_una_pagina_oculta_no_se_ve_ni_aparece_en_el_menu(): void
    {
        Pagina::query()->where('slug', 'manuales')->update(['visible' => false]);

        $this->get('/manuales')->assertNotFound();
        $this->get('/')->assertDontSee(url('/manuales'), false);
    }

    public function test_el_carrusel_muestra_las_fotos_de_inicio(): void
    {
        $carrusel = Pagina::query()->where('es_inicio', true)->firstOrFail()->secciones()->where('tipo', 'carrusel')->firstOrFail();
        $carrusel->elementos()->create(['imagen' => 'assets/img/bg/bg-34.png', 'titulo' => 'Bienvenidos a Solutions GT', 'enlace_texto' => 'Ver más', 'enlace' => '/software']);

        $this->get('/')->assertSee('Bienvenidos a Solutions GT')->assertSee('carousel-item', false);
    }

    public function test_la_pagina_de_un_sistema_cuenta_visitas_y_ofrece_demo_y_prueba(): void
    {
        $sistema = Sistema::query()->where('slug', 'sistema-para-restaurantes')->firstOrFail();
        $sistema->update(['acepta_prueba' => true, 'dias_prueba' => 15]);

        $this->get('/sistemas/sistema-para-restaurantes')
            ->assertOk()
            ->assertSee('Factura electrónica FEL (Guatemala)')
            ->assertSee('Probar 15 días gratis')
            ->assertSee('id="solicitar"', false);

        $this->assertSame(1, $sistema->fresh()->visitas);
    }

    public function test_un_sistema_oculto_no_se_publica(): void
    {
        Sistema::query()->where('slug', 'autodmv-pro')->update(['visible' => false]);

        $this->get('/sistemas/autodmv-pro')->assertNotFound();
        $this->get('/software')->assertDontSee('AutoDMV Pro');
    }

    public function test_el_formulario_de_contacto_guarda_el_mensaje(): void
    {
        Direccion::query()->create(['nombre' => 'Oficina central', 'direccion' => 'Zona 10', 'ciudad' => 'Guatemala', 'principal' => true, 'visible' => true]);
        $this->get('/contactenos')->assertSee('Oficina central')->assertSee('maps.google.com', false);

        $this->from('/contactenos')->post('/contacto', [
            'nombre' => 'Juan Pérez', 'correo' => 'juan@ejemplo.com', 'telefono' => '5555 5555', 'mensaje' => 'Quiero información.',
        ])->assertRedirectContains('#contacto')->assertSessionHas('contacto_ok', 'contacto');

        $this->assertDatabaseHas('mensajes_contacto', ['correo' => 'juan@ejemplo.com', 'estado' => 'nuevo', 'tipo' => 'contacto']);
    }

    public function test_solicitar_prueba_de_un_sistema(): void
    {
        $sistema = Sistema::query()->firstOrFail();
        $sistema->update(['acepta_prueba' => true, 'dias_prueba' => 10]);

        $this->from("/sistemas/{$sistema->slug}")->post('/contacto', [
            'tipo' => 'prueba', 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'sistema_id' => $sistema->id, 'ancla' => 'solicitar',
        ])->assertRedirectContains('#solicitar')->assertSessionHas('contacto_ok', 'prueba');

        $m = MensajeContacto::query()->firstOrFail();
        $this->assertSame('prueba', $m->tipo);
        $this->assertStringContainsString('10 días', $m->mensaje);
    }

    public function test_si_el_sistema_no_ofrece_prueba_queda_como_demostracion(): void
    {
        $sistema = Sistema::query()->firstOrFail();
        $sistema->update(['acepta_prueba' => false]);

        $this->from('/')->post('/contacto', ['tipo' => 'prueba', 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'sistema_id' => $sistema->id]);

        $this->assertSame('demo', MensajeContacto::query()->firstOrFail()->tipo);
    }

    public function test_el_formulario_valida_los_datos(): void
    {
        $this->from('/')->post('/contacto', ['nombre' => '', 'correo' => 'no-es-correo', 'mensaje' => ''])
            ->assertSessionHasErrors(['nombre', 'correo', 'mensaje']);
        $this->from('/')->post('/contacto', ['tipo' => 'demo', 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com'])
            ->assertSessionHasErrors(['sistema_id']);

        $this->assertSame(0, MensajeContacto::query()->count());
    }

    public function test_los_robots_que_llenan_el_campo_trampa_no_guardan_nada(): void
    {
        $this->from('/')->post('/contacto', [
            'nombre' => 'Robot', 'correo' => 'robot@spam.com', 'mensaje' => 'spam', 'empresa_web' => 'http://spam',
        ])->assertRedirect();

        $this->assertSame(0, MensajeContacto::query()->count());
    }

    public function test_los_manuales_se_publican_con_su_guia_y_video(): void
    {
        $sistema = Sistema::query()->firstOrFail();
        Manual::query()->create([
            'sistema_id' => $sistema->id, 'titulo' => 'Cómo abrir caja', 'slug' => 'como-abrir-caja', 'visible' => true,
            'contenido' => "## Paso 1\n\nEntra a **Caja**.\n\n<script>alert(1)</script>", 'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);

        $this->get('/manuales')->assertSee('Cómo abrir caja');
        $this->get('/manuales/como-abrir-caja')->assertOk()
            ->assertSee('<h2>Paso 1</h2>', false)
            ->assertSee('<strong>Caja</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
        $this->get("/sistemas/{$sistema->slug}")->assertSee('Cómo abrir caja');
    }
}
