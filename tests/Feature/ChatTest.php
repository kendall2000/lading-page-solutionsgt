<?php

namespace Tests\Feature;

use App\Events\MensajeEnviado;
use App\Http\Controllers\ChatController;
use App\Mail\CorreoPlantilla;
use App\Models\ConfiguracionCorreo;
use App\Models\ConfiguracionSitio;
use App\Models\Conversacion;
use App\Models\User;
use App\Support\Sitio;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
        config([
            'broadcasting.connections.reverb.key' => 'llave-prueba',
            'broadcasting.connections.reverb.secret' => 'secreto-prueba',
            'broadcasting.connections.reverb.app_id' => '123',
        ]);
    }

    /** Quita las cookies de peticiones anteriores (simula un visitante sin cookie). */
    private function sinCookies(): static
    {
        $this->defaultCookies = [];

        return $this;
    }

    /** Inicia un chat y devuelve el token de la cookie del visitante. */
    private function iniciar(string $correo = 'ana@ejemplo.com'): string
    {
        $r = $this->postJson('/chat/iniciar', ['nombre' => 'Ana López', 'correo' => $correo, 'mensaje' => 'Hola, quiero info', 'pagina' => '/software'])
            ->assertOk()->assertJsonPath('mensajes.0.cuerpo', 'Hola, quiero info');

        return $r->getCookie(ChatController::COOKIE)->getValue();
    }

    public function test_el_widget_aparece_en_el_sitio_y_se_puede_apagar(): void
    {
        $this->get('/')->assertSee('id="sgtChat"', false)->assertSee('echo.iife.js', false)->assertDontSee('secreto-prueba');

        ConfiguracionSitio::query()->first()->update(['chat_activo' => false]);
        Sitio::olvidar();
        $this->get('/')->assertDontSee('id="sgtChat"', false);
        $this->postJson('/chat/iniciar', ['nombre' => 'A', 'correo' => 'a@a.com', 'mensaje' => 'x'])->assertNotFound();
    }

    public function test_el_visitante_inicia_chat_y_se_guarda_solo_el_hash_del_token(): void
    {
        $token = $this->iniciar();

        $c = Conversacion::query()->firstOrFail();
        $this->assertSame(hash('sha256', $token), $c->token_hash);
        $this->assertSame(1, $c->no_leidos_admin);
        $this->assertSame('/software', $c->pagina);

        // Con su cookie recupera la conversación.
        $this->withCredentials()->withCookie(ChatController::COOKIE, $token)->getJson('/chat/estado')
            ->assertJsonPath('conversacion.id', $c->id)->assertJsonPath('conversacion.canal', 'chat.conversacion.'.$c->id)
            ->assertJsonCount(1, 'mensajes');
        // Sin cookie, nada.
        $this->sinCookies()->getJson('/chat/estado')->assertJsonPath('conversacion', null);
    }

    public function test_conversacion_completa_visitante_y_panel(): void
    {
        $token = $this->iniciar();
        $c = Conversacion::query()->firstOrFail();

        $this->withCredentials()->withCookie(ChatController::COOKIE, $token)->postJson('/chat/mensajes', ['cuerpo' => '¿Tienen prueba gratis?'])->assertCreated();
        $this->assertSame(2, $c->fresh()->no_leidos_admin);

        // El panel ve la conversación, la abre (queda leída) y responde.
        $this->actingAs($this->admin)->get('/admin/chat')->assertOk()->assertSee('Ana López')->assertSee('¿Tienen prueba gratis?');
        $this->assertSame(0, $c->fresh()->no_leidos_admin);
        $this->actingAs($this->admin)->postJson("/admin/chat/{$c->id}/mensajes", ['cuerpo' => 'Sí, 15 días'])
            ->assertCreated()->assertJsonPath('mensaje.autor', 'admin');
        $this->assertSame($this->admin->id, $c->fresh()->atendida_por);

        // El visitante recibe la respuesta (consulta de respaldo) y queda leída para él.
        $this->withCredentials()->withCookie(ChatController::COOKIE, $token)->getJson('/chat/mensajes?despues=2')
            ->assertJsonCount(1, 'mensajes')->assertJsonPath('mensajes.0.cuerpo', 'Sí, 15 días');
        $this->assertSame(0, $c->fresh()->no_leidos_visitante);
    }

    public function test_un_visitante_no_puede_ver_ni_escuchar_la_conversacion_de_otro(): void
    {
        $tokenA = $this->iniciar('a@ejemplo.com');
        $this->iniciar('b@ejemplo.com');
        [$a, $b] = Conversacion::query()->orderBy('id')->get();

        $this->withCredentials()->withCookie(ChatController::COOKIE, $tokenA)->getJson('/chat/estado')->assertJsonPath('conversacion.id', $a->id);

        // Autoriza su propio canal privado…
        $this->withCredentials()->withCookie(ChatController::COOKIE, $tokenA)
            ->postJson('/chat/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.conversacion.'.$a->id])
            ->assertOk()->assertJsonStructure(['auth']);
        // …pero no el de otro visitante ni el del panel.
        $this->withCredentials()->withCookie(ChatController::COOKIE, $tokenA)
            ->postJson('/chat/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.conversacion.'.$b->id])->assertForbidden();
        $this->withCredentials()->withCookie(ChatController::COOKIE, $tokenA)
            ->postJson('/chat/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.panel'])->assertForbidden();
        $this->sinCookies()->postJson('/chat/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.conversacion.'.$a->id])->assertForbidden();
        // Sin cookie no puede escribir.
        $this->postJson('/chat/mensajes', ['cuerpo' => 'hola'])->assertNotFound();
    }

    public function test_el_evento_va_por_canales_privados(): void
    {
        $this->iniciar();
        $mensaje = Conversacion::query()->firstOrFail()->mensajes()->first();

        $evento = new MensajeEnviado($mensaje);
        $canales = collect($evento->broadcastOn())->map(fn (PrivateChannel $c) => $c->name)->all();
        $this->assertSame(['private-chat.conversacion.'.$mensaje->conversacion_id, 'private-chat.panel'], $canales);
        $this->assertSame('mensaje.enviado', $evento->broadcastAs());
        $this->assertSame('Hola, quiero info', $evento->broadcastWith()['mensaje']['cuerpo']);

        // Las respuestas del panel no van al canal del panel (solo al del visitante).
        $respuesta = Conversacion::query()->first()->mensajes()->create(['autor' => 'admin', 'user_id' => $this->admin->id, 'cuerpo' => 'ok']);
        $this->assertCount(1, (new MensajeEnviado($respuesta))->broadcastOn());
    }

    public function test_el_panel_autoriza_sus_canales_solo_con_sesion(): void
    {
        $this->iniciar();
        $c = Conversacion::query()->firstOrFail();
        // En pruebas el driver es «null»: se activa Reverb y se registran sus canales como en producción.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');

        $this->postJson('/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => 'private-chat.panel'])->assertForbidden();
        $this->actingAs($this->admin)->postJson('/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => 'private-chat.panel'])
            ->assertOk()->assertJsonStructure(['auth']);
        $this->actingAs($this->admin)->postJson('/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => 'private-chat.conversacion.'.$c->id])
            ->assertOk();
    }

    public function test_aviso_por_correo_de_chat_nuevo(): void
    {
        Mail::fake();
        ConfiguracionCorreo::actual()->update(['host' => 'smtp.ejemplo.com', 'puerto' => 587, 'remitente_correo' => 'no@ejemplo.com', 'avisos_a' => 'yo@ejemplo.com', 'is_active' => true]);

        $this->iniciar();

        Mail::assertSent(CorreoPlantilla::class, fn ($m) => $m->hasTo('yo@ejemplo.com') && $m->hasReplyTo('ana@ejemplo.com') && str_contains($m->cuerpo, 'Hola, quiero info'));
    }

    public function test_validacion_y_campo_trampa(): void
    {
        $this->postJson('/chat/iniciar', ['nombre' => '', 'correo' => 'x', 'mensaje' => ''])->assertJsonValidationErrors(['nombre', 'correo', 'mensaje']);
        $this->postJson('/chat/iniciar', ['nombre' => 'Bot', 'correo' => 'b@b.com', 'mensaje' => 'spam', 'empresa_web' => 'x'])->assertJsonValidationErrors(['empresa_web']);
        $this->assertSame(0, Conversacion::query()->count());
    }

    public function test_cerrar_y_reabrir_al_escribir(): void
    {
        $token = $this->iniciar();
        $c = Conversacion::query()->firstOrFail();

        $this->actingAs($this->admin)->post("/admin/chat/{$c->id}/cerrar")->assertRedirect();
        $this->assertSame('cerrada', $c->fresh()->estado);
        $this->actingAs($this->admin)->get('/admin/chat?estado=cerrada')->assertOk()->assertSee('Ana López');

        $this->withCredentials()->withCookie(ChatController::COOKIE, $token)->postJson('/chat/mensajes', ['cuerpo' => 'Sigo aquí'])->assertCreated();
        $this->assertSame('abierta', $c->fresh()->estado);

        $this->actingAs($this->admin)->getJson('/admin/chat/resumen')->assertJsonPath('total', 1); // al cerrar se marcó como leída
    }
}
