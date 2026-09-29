<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\MensajeContacto;
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

    public function test_la_portada_muestra_los_sistemas_y_servicios(): void
    {
        Direccion::query()->create(['nombre' => 'Oficina central', 'direccion' => 'Zona 10', 'ciudad' => 'Guatemala', 'principal' => true, 'visible' => true]);
        Cliente::query()->create(['empresa' => 'Café Central', 'contacto' => 'Ana López', 'testimonio' => 'Excelente sistema.', 'mostrar_testimonio' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Solutions GT')
            ->assertSee('Sistema para Restaurantes')
            ->assertSee('AutoDMV Pro')
            ->assertSee('Desarrollo a la medida')
            ->assertSee('Excelente sistema.')
            ->assertSee('Oficina central')
            ->assertDontSee('polyfill.io');
    }

    public function test_la_pagina_de_un_sistema_cuenta_visitas(): void
    {
        $sistema = Sistema::query()->where('slug', 'sistema-para-restaurantes')->firstOrFail();

        $this->get('/sistemas/sistema-para-restaurantes')
            ->assertOk()
            ->assertSee('Factura electrónica FEL (Guatemala)')
            ->assertSee('También te puede interesar');

        $this->assertSame(1, $sistema->fresh()->visitas);
    }

    public function test_un_sistema_oculto_no_se_publica(): void
    {
        Sistema::query()->where('slug', 'autodmv-pro')->update(['visible' => false]);

        $this->get('/sistemas/autodmv-pro')->assertNotFound();
        $this->get('/')->assertDontSee('AutoDMV Pro');
    }

    public function test_el_formulario_de_contacto_guarda_el_mensaje(): void
    {
        $sistema = Sistema::query()->firstOrFail();

        $this->from('/')->post('/contacto', [
            'nombre' => 'Juan Pérez',
            'correo' => 'juan@ejemplo.com',
            'telefono' => '5555 5555',
            'sistema_id' => $sistema->id,
            'mensaje' => 'Quiero una demostración.',
        ])->assertRedirectContains('#contacto')->assertSessionHas('contacto_ok');

        $this->assertDatabaseHas('mensajes_contacto', ['correo' => 'juan@ejemplo.com', 'estado' => 'nuevo', 'sistema_id' => $sistema->id]);
    }

    public function test_el_formulario_valida_los_datos(): void
    {
        $this->from('/')->post('/contacto', ['nombre' => '', 'correo' => 'no-es-correo', 'mensaje' => ''])
            ->assertSessionHasErrors(['nombre', 'correo', 'mensaje']);

        $this->assertSame(0, MensajeContacto::query()->count());
    }

    public function test_los_robots_que_llenan_el_campo_trampa_no_guardan_nada(): void
    {
        $this->from('/')->post('/contacto', [
            'nombre' => 'Robot', 'correo' => 'robot@spam.com', 'mensaje' => 'spam', 'empresa_web' => 'http://spam',
        ])->assertRedirect();

        $this->assertSame(0, MensajeContacto::query()->count());
    }
}
