<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatController;
use App\Mail\CorreoPlantilla;
use App\Models\ConfiguracionCorreo;
use App\Models\ConfiguracionSitio;
use App\Models\Conversacion;
use App\Models\MensajeContacto;
use App\Models\Pagina;
use App\Models\Sistema;
use App\Models\User;
use App\Support\Sitio;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactoSinFriccionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
        ConfiguracionCorreo::actual()->update([
            'host' => 'smtp.ejemplo.com',
            'puerto' => 587,
            'remitente_correo' => 'no@ejemplo.com',
            'avisos_a' => 'dueno@ejemplo.com',
            'is_active' => true,
        ]);
    }

    public function test_contacto_solo_con_telefono_guarda_avisa_con_whatsapp_y_no_envia_confirmacion(): void
    {
        Mail::fake();

        $this->from('/contactenos')->post('/contacto', $this->antispam() + [
            'nombre' => 'Carlos Méndez',
            'telefono' => '5511-2233',
            'mensaje' => 'Quiero cotizar el sistema.',
        ])->assertRedirectContains('#contacto')->assertSessionHas('contacto_ok', 'contacto');

        $m = MensajeContacto::query()->firstOrFail();
        $this->assertNull($m->correo);
        $this->assertSame('5511-2233', $m->telefono);

        Mail::assertSentCount(1);
        Mail::assertSent(CorreoPlantilla::class, fn (CorreoPlantilla $mail) => $mail->hasTo('dueno@ejemplo.com')
            && str_contains($mail->cuerpo, 'https://wa.me/50255112233'));
    }

    public function test_contacto_solo_con_correo_envia_aviso_y_confirmacion(): void
    {
        Mail::fake();

        $this->from('/contactenos')->post('/contacto', $this->antispam() + [
            'nombre' => 'Lucía Gómez',
            'correo' => 'lucia@ejemplo.com',
            'mensaje' => 'Necesito información.',
        ])->assertRedirectContains('#contacto')->assertSessionHas('contacto_ok', 'contacto');

        Mail::assertSentCount(2);
        Mail::assertSent(CorreoPlantilla::class, fn (CorreoPlantilla $mail) => $mail->hasTo('dueno@ejemplo.com') && $mail->hasReplyTo('lucia@ejemplo.com'));
        Mail::assertSent(CorreoPlantilla::class, fn (CorreoPlantilla $mail) => $mail->hasTo('lucia@ejemplo.com'));
    }

    public function test_contacto_sin_correo_ni_telefono_falla_con_mensaje_en_espanol(): void
    {
        $this->from('/contactenos')->post('/contacto', $this->antispam() + [
            'nombre' => 'Pedro',
            'mensaje' => 'Hola',
        ])->assertSessionHasErrors([
            'correo' => 'Escribe tu WhatsApp o tu correo para poder responderte.',
            'telefono' => 'Escribe tu WhatsApp o tu correo para poder responderte.',
        ]);

        $this->assertSame(0, MensajeContacto::query()->count());
    }

    public function test_duplicado_por_telefono_en_10_minutos_no_se_repite(): void
    {
        Mail::fake();
        $payload = $this->antispam() + [
            'nombre' => 'Carlos',
            'telefono' => '55112233',
            'mensaje' => 'Quiero una demo.',
        ];

        $this->from('/contactenos')->post('/contacto', $payload)->assertSessionHas('contacto_ok', 'contacto');
        $this->from('/contactenos')->post('/contacto', $payload)->assertSessionHas('contacto_ok', 'contacto');

        $this->assertSame(1, MensajeContacto::query()->count());
        Mail::assertSentCount(1);
    }

    public function test_chat_iniciado_con_whatsapp_guarda_telefono_y_correo_null(): void
    {
        Mail::fake();

        $r = $this->postJson('/chat/iniciar', $this->antispam() + [
            'nombre' => 'Mario',
            'contacto' => '+502 5544-3322',
            'mensaje' => 'Hola, ¿tienen demo?',
            'pagina' => '/software',
        ])->assertOk()->assertJsonPath('conversacion.tiene_correo', false);

        $c = Conversacion::query()->firstOrFail();
        $this->assertNull($c->correo);
        $this->assertSame('+502 5544-3322', $c->telefono);
        $this->assertNotNull($r->getCookie(ChatController::COOKIE));

        Mail::assertSent(CorreoPlantilla::class, fn (CorreoPlantilla $mail) => $mail->hasTo('dueno@ejemplo.com')
            && str_contains($mail->cuerpo, '+502 5544-3322'));
    }

    public function test_chat_iniciado_con_correo_en_campo_contacto_funciona_y_rechaza_invalido(): void
    {
        $this->postJson('/chat/iniciar', $this->antispam() + [
            'nombre' => 'Mario',
            'contacto' => 'no-valido',
            'mensaje' => 'Hola',
        ])->assertJsonValidationErrors(['contacto']);

        $this->postJson('/chat/iniciar', $this->antispam() + [
            'nombre' => 'Sofía',
            'contacto' => 'SOFIA@EJEMPLO.COM',
            'mensaje' => 'Hola',
        ])->assertOk()->assertJsonPath('conversacion.tiene_correo', true);

        $c = Conversacion::query()->firstOrFail();
        $this->assertSame('sofia@ejemplo.com', $c->correo);
        $this->assertNull($c->telefono);
    }

    public function test_cierre_de_chat_sin_correo_no_permite_copia_por_correo(): void
    {
        Mail::fake();

        $r = $this->postJson('/chat/iniciar', $this->antispam() + [
            'nombre' => 'Luis',
            'contacto' => '55889900',
            'mensaje' => 'Consulta rápida',
        ])->assertOk();
        $token = $r->getCookie(ChatController::COOKIE)->getValue();
        $c = Conversacion::query()->firstOrFail();

        $this->actingAs($this->admin)->post("/admin/chat/{$c->id}/cerrar")->assertRedirect('/admin/chat');

        $this->withCredentials()->withCookie(ChatController::COOKIE, $token)
            ->postJson('/chat/copia')
            ->assertStatus(422);

        // Solo el aviso inicial nuevo_chat, nunca copia_chat.
        Mail::assertSentCount(1);
    }

    public function test_panel_lista_detalle_chat_y_credenciales_toleran_contacto_sin_correo(): void
    {
        Mail::fake();
        $sistema = Sistema::query()->firstOrFail();
        $mensaje = MensajeContacto::query()->create([
            'tipo' => 'prueba',
            'nombre' => 'Elena Sin Correo',
            'correo' => null,
            'telefono' => '5599-8877',
            'sistema_id' => $sistema->id,
            'mensaje' => 'Quiero probarlo.',
        ]);

        $this->actingAs($this->admin)->get('/admin/mensajes')
            ->assertOk()
            ->assertSee('Elena Sin Correo')
            ->assertSee('https://wa.me/50255998877', false);

        $this->actingAs($this->admin)->get("/admin/mensajes/{$mensaje->id}")
            ->assertOk()
            ->assertSee('Este contacto no dejó correo, escríbele por WhatsApp.')
            ->assertSee('Responder por WhatsApp')
            ->assertSee('https://wa.me/50255998877', false);

        // Intentar enviar credenciales por correo queda bloqueado y no envía correo.
        $this->actingAs($this->admin)->post("/admin/mensajes/{$mensaje->id}/credenciales", [
            'url_acceso' => 'https://demo.ejemplo.com/login',
            'usuario_prueba' => 'elena',
            'clave_prueba' => 'secreta123',
            'dias' => 10,
            'enviar' => '1',
        ])->assertRedirect()->assertSessionHas('aviso', 'Este contacto no dejó correo, escríbele por WhatsApp.');

        Mail::assertNothingSent();
        $this->assertNull($mensaje->fresh()->credenciales_enviadas_en);

        // Exportar CSV tampoco falla sin correo.
        $this->actingAs($this->admin)->get('/admin/mensajes/exportar')->assertOk();

        // Bandeja de chat con conversación sin correo muestra teléfono y botón de WhatsApp.
        $conv = Conversacion::query()->create([
            'token_hash' => Conversacion::hashToken(str_repeat('a', 48)),
            'nombre' => 'Visitante WA',
            'correo' => null,
            'telefono' => '4433-2211',
            'ultimo_mensaje_en' => now(),
        ]);
        $this->actingAs($this->admin)->get("/admin/chat?c={$conv->id}")
            ->assertOk()
            ->assertSee('4433-2211')
            ->assertSee('https://wa.me/50244332211', false);
    }

    public function test_botones_whatsapp_y_bloque_contacto_version_corta(): void
    {
        ConfiguracionSitio::query()->first()->update(['whatsapp' => '5500-1122']);
        Sitio::olvidar();
        $sistema = Sistema::query()->where('slug', 'sistema-para-restaurantes')->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertSee('¿Dudas? Escríbeme')
            ->assertSee('Demo por WhatsApp');

        $this->get("/sistemas/{$sistema->slug}")
            ->assertOk()
            ->assertSee('Demo por WhatsApp')
            ->assertSee('https://wa.me/50255001122', false);

        // Bloque contacto en versión corta en inicio.
        $inicio = Pagina::query()->where('es_inicio', true)->firstOrFail();
        $inicio->secciones()->create([
            'tipo' => 'contacto',
            'titulo' => 'Escríbenos rápido',
            'orden' => 99,
            'visible' => true,
            'opciones' => ['corta' => true, 'mapa' => false],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Escríbenos rápido')
            ->assertSee('name="corto" value="1"', false);

        $this->from('/')->post('/contacto', $this->antispam() + [
            'corto' => '1',
            'nombre' => 'Cliente Rápido',
            'telefono' => '55667788',
        ])->assertRedirectContains('#contacto')->assertSessionHas('contacto_ok', 'contacto');

        $this->assertDatabaseHas('mensajes_contacto', ['nombre' => 'Cliente Rápido', 'telefono' => '55667788', 'correo' => null]);
    }
}
