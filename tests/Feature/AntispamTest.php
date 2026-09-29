<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatController;
use App\Models\Conversacion;
use App\Models\MensajeContacto;
use App\Models\MensajeChat;
use App\Support\Antispam;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntispamTest extends TestCase
{
    use RefreshDatabase;

    private const DATOS = ['nombre' => 'Ana López', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Quiero información.'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_los_formularios_llevan_la_marca_de_llegada(): void
    {
        $this->get('/contactenos')->assertOk()->assertSee('name="llegada"', false);
    }

    public function test_el_formulario_enviado_muy_rapido_o_sin_pasar_por_la_pagina_no_se_guarda(): void
    {
        // Sin marca (envío directo), marca alterada y marca de hace un segundo.
        foreach ([[], ['llegada' => 'inventada'], ['llegada' => Antispam::marca(time() - 1)]] as $marca) {
            $this->from('/contactenos')->post('/contacto', $marca + self::DATOS)
                ->assertSessionHasErrors(['formulario' => Antispam::MENSAJE_RAPIDO]);
        }

        $this->assertSame(0, MensajeContacto::query()->count());
    }

    public function test_el_mismo_mensaje_repetido_se_guarda_una_sola_vez(): void
    {
        $this->from('/contactenos')->post('/contacto', $this->antispam() + self::DATOS)->assertSessionHas('contacto_ok');
        $this->from('/contactenos')->post('/contacto', $this->antispam() + self::DATOS)->assertSessionHas('contacto_ok');
        $this->from('/contactenos')->post('/contacto', $this->antispam() + ['mensaje' => 'Otra consulta.'] + self::DATOS);

        $this->assertSame(2, MensajeContacto::query()->count());
    }

    public function test_no_se_aceptan_mensajes_llenos_de_enlaces_ni_enlaces_en_el_nombre(): void
    {
        $spam = 'Oferta https://a.com https://b.com www.c.com http://d.com';
        $this->from('/')->post('/contacto', $this->antispam() + ['mensaje' => $spam] + self::DATOS)->assertSessionHasErrors('mensaje');
        $this->from('/')->post('/contacto', $this->antispam() + ['nombre' => 'Gana dinero www.spam.com'] + self::DATOS)->assertSessionHasErrors('nombre');
        $this->assertSame(0, MensajeContacto::query()->count());

        // Un par de enlaces sí se permite.
        $this->from('/')->post('/contacto', $this->antispam() + ['mensaje' => 'Mi sitio es https://mi.com y www.otro.com'] + self::DATOS)
            ->assertSessionHasNoErrors();
    }

    public function test_el_chat_tambien_filtra_robots(): void
    {
        $this->postJson('/chat/iniciar', self::DATOS)->assertJsonValidationErrors(['llegada' => Antispam::MENSAJE_RAPIDO]);
        $this->postJson('/chat/iniciar', ['llegada' => Antispam::marca()] + self::DATOS)->assertJsonValidationErrors('llegada');
        $this->postJson('/chat/iniciar', $this->antispam() + ['nombre' => 'https://spam.com'] + self::DATOS)->assertJsonValidationErrors('nombre');
        $this->assertSame(0, Conversacion::query()->count());

        $token = $this->postJson('/chat/iniciar', $this->antispam() + self::DATOS)->assertOk()->getCookie(ChatController::COOKIE)->getValue();
        $this->withCredentials()->withCookie(ChatController::COOKIE, $token)->postJson('/chat/mensajes', ['cuerpo' => 'http://a.com http://b.com http://c.com http://d.com'])
            ->assertJsonValidationErrors('cuerpo');
        $this->assertSame(1, MensajeChat::query()->count());
    }
}
