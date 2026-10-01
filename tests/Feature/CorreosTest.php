<?php

namespace Tests\Feature;

use App\Mail\CorreoPlantilla;
use App\Models\ConfiguracionCorreo;
use App\Models\PlantillaCorreo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CorreosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
    }

    private function activarServidor(): void
    {
        ConfiguracionCorreo::actual()->update([
            'host' => 'smtp.ejemplo.com', 'puerto' => 587, 'cifrado' => 'tls', 'usuario' => 'yo@ejemplo.com', 'clave' => 'secreta',
            'remitente_correo' => 'no-responder@ejemplo.com', 'avisos_a' => 'yo@ejemplo.com, socio@ejemplo.com', 'is_active' => true,
        ]);
    }

    public function test_las_pantallas_de_correos_abren(): void
    {
        $plantilla = PlantillaCorreo::query()->where('codigo', 'nuevo_mensaje')->firstOrFail();

        $this->actingAs($this->admin)->get('/admin/correos')->assertOk()->assertSee('Servidor SMTP')->assertSee('Aviso de mensaje nuevo');
        $this->actingAs($this->admin)->get("/admin/correos/plantillas/{$plantilla->id}")->assertOk();
        $this->actingAs($this->admin)->get("/admin/correos/plantillas/{$plantilla->id}/vista-previa")->assertOk()->assertSee('Café Central');
        $this->actingAs($this->admin)->get('/admin/correos/plantillas/nueva')->assertOk();
    }

    public function test_el_servidor_se_guarda_en_la_base_con_la_clave_cifrada(): void
    {
        $this->actingAs($this->admin)->post('/admin/correos/servidor', [
            'host' => 'smtp.gmail.com', 'puerto' => 587, 'cifrado' => 'tls', 'usuario' => 'yo@gmail.com', 'clave' => 'clave-app',
            'remitente_correo' => 'yo@gmail.com', 'avisos_a' => 'yo@gmail.com', 'is_active' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cfg = ConfiguracionCorreo::actual();
        $this->assertSame('clave-app', $cfg->clave);
        $this->assertNotSame('clave-app', $cfg->getRawOriginal('clave'));

        // Dejar la contraseña vacía conserva la guardada.
        $this->actingAs($this->admin)->post('/admin/correos/servidor', [
            'host' => 'smtp.office365.com', 'puerto' => 587, 'remitente_correo' => 'yo@gmail.com', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('clave-app', ConfiguracionCorreo::actual()->clave);
        $this->assertSame('smtp.office365.com', ConfiguracionCorreo::actual()->host);
    }

    public function test_sin_remitente_se_usa_el_correo_del_usuario(): void
    {
        $this->actingAs($this->admin)->post('/admin/correos/servidor', [
            'host' => 'smtp.gmail.com', 'puerto' => 587, 'cifrado' => 'tls', 'usuario' => 'yo@gmail.com', 'clave' => 'clave-app', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('yo@gmail.com', ConfiguracionCorreo::actual()->remitente_correo);

        // Si el usuario no es un correo, el remitente sigue siendo obligatorio.
        $this->actingAs($this->admin)->post('/admin/correos/servidor', [
            'host' => 'mail.ejemplo.com', 'puerto' => 587, 'usuario' => 'usuario123', 'remitente_correo' => '', 'is_active' => '1',
        ])->assertSessionHasErrors('remitente_correo');
    }

    public function test_la_prueba_pide_activar_el_servidor_antes(): void
    {
        $this->actingAs($this->admin)->post('/admin/correos/probar', ['destinatario' => 'yo@ejemplo.com'])->assertSessionHas('aviso');
        $this->assertDatabaseMissing('bitacora_correos', ['plantilla' => '_prueba']);
    }

    public function test_avisos_a_valida_cada_correo(): void
    {
        $this->actingAs($this->admin)->post('/admin/correos/servidor', ['avisos_a' => 'bien@ejemplo.com, malo'])
            ->assertSessionHasErrors('avisos_a');
    }

    public function test_con_el_servidor_apagado_el_contacto_se_guarda_y_queda_en_la_bitacora(): void
    {
        Mail::fake();
        ConfiguracionCorreo::actual()->update(['avisos_a' => 'yo@ejemplo.com']);

        $this->from('/')->post('/contacto', $this->antispam() + ['nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Hola'])->assertSessionHas('contacto_ok');

        Mail::assertNothingSent();
        $this->assertDatabaseHas('mensajes_contacto', ['correo' => 'ana@ejemplo.com']);
        $this->assertSame(2, DB::table('bitacora_correos')->where('estado', 'desactivado')->count());
    }

    public function test_con_el_servidor_activo_avisa_y_confirma_al_visitante(): void
    {
        Mail::fake();
        $this->activarServidor();

        $this->from('/')->post('/contacto', $this->antispam() + ['nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Quiero una demo'])->assertSessionHas('contacto_ok');

        Mail::assertSent(CorreoPlantilla::class, 3);
        Mail::assertSent(CorreoPlantilla::class, fn ($m) => $m->hasTo('socio@ejemplo.com') && $m->hasReplyTo('ana@ejemplo.com') && str_contains($m->asunto, 'Ana'));
        Mail::assertSent(CorreoPlantilla::class, fn ($m) => $m->hasTo('ana@ejemplo.com') && str_contains($m->cuerpo, 'Gracias por escribir'));
        $this->assertSame(3, DB::table('bitacora_correos')->where('estado', 'enviado')->count());
    }

    public function test_recuperar_contrasena_usa_la_plantilla(): void
    {
        Mail::fake();
        $this->activarServidor();

        $this->post('/forgot-password', ['email' => $this->admin->email])->assertSessionHasNoErrors();

        Mail::assertSent(CorreoPlantilla::class, fn ($m) => $m->hasTo($this->admin->email) && str_contains($m->cuerpo, 'reset-password'));
    }

    public function test_las_plantillas_del_sitio_no_se_borran(): void
    {
        $plantilla = PlantillaCorreo::query()->where('codigo', 'recuperar_contrasena')->firstOrFail();

        $this->actingAs($this->admin)->delete("/admin/correos/plantillas/{$plantilla->id}")->assertStatus(422);
        $this->assertModelExists($plantilla);
    }
}
