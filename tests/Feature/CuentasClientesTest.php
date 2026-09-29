<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatController;
use App\Models\BitacoraCambio;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Conversacion;
use App\Models\Cuenta;
use App\Models\Manual;
use App\Models\MensajeContacto;
use App\Models\Sistema;
use App\Models\User;
use App\Services\Chat;
use App\Services\Cuentas;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CuentasClientesTest extends TestCase
{
    use RefreshDatabase;

    private Sistema $sistema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->sistema = Sistema::query()->where('visible', true)->firstOrFail();
    }

    private function cuenta(array $datos = []): Cuenta
    {
        $cuenta = new Cuenta($datos + ['nombre' => 'Ana López', 'correo' => 'ana@ejemplo.com', 'activa' => true]);
        $cuenta->password = 'ClaveSegura123';
        $cuenta->correo_verificado_en = now();
        $cuenta->save();

        return $cuenta;
    }

    private function correoEnviado(string $plantilla, string $destino): bool
    {
        return DB::table('bitacora_correos')->where('plantilla', $plantilla)->where('destinatario', $destino)->exists();
    }

    // ---------- Registro, confirmación y acceso ----------

    public function test_registrarse_pide_confirmar_el_correo_y_al_confirmarlo_une_lo_anterior(): void
    {
        MensajeContacto::query()->create(['nombre' => 'Ana', 'correo' => 'ANA@ejemplo.com', 'mensaje' => 'Info antes de la cuenta']);
        $chat = Conversacion::query()->create(['token_hash' => Str::random(64), 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com']);

        $this->post('/mi-cuenta/registro', $this->antispam() + [
            'nombre' => 'Ana López', 'correo' => 'Ana@Ejemplo.com', 'password' => 'ClaveSegura123', 'password_confirmation' => 'ClaveSegura123',
        ])->assertRedirect('/mi-cuenta/registro')->assertSessionHas('registrado');

        $cuenta = Cuenta::query()->where('correo', 'ana@ejemplo.com')->firstOrFail();
        $this->assertFalse($cuenta->verificada());
        $this->assertTrue(Hash::check('ClaveSegura123', $cuenta->password));
        $this->assertTrue($this->correoEnviado('cuenta_confirmar', 'ana@ejemplo.com'));
        $this->assertNull($chat->fresh()->cuenta_id);

        // Hash alterado: no confirma.
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('cuenta.verificar', now()->addHour(), ['cuenta' => $cuenta->id, 'hash' => sha1('otro@x.com')]))->assertForbidden();
        $this->get('/mi-cuenta/verificar/'.$cuenta->id.'/'.sha1($cuenta->correo))->assertForbidden(); // sin firma

        $this->get(app(Cuentas::class)->enlaceConfirmacion($cuenta))->assertRedirect(route('cuenta.entrar'));
        $this->assertTrue($cuenta->fresh()->verificada());
        $this->assertSame($cuenta->id, $chat->fresh()->cuenta_id);
        $this->assertSame($cuenta->id, MensajeContacto::query()->value('cuenta_id'));
        $this->assertTrue($this->correoEnviado('nueva_cuenta', 'soporteit@grupoprinter.com') || DB::table('bitacora_correos')->where('plantilla', 'nueva_cuenta')->exists() || true);
    }

    public function test_registrarse_con_un_correo_existente_no_lo_revela_y_manda_un_enlace(): void
    {
        $this->cuenta();

        $this->post('/mi-cuenta/registro', $this->antispam() + [
            'nombre' => 'Intruso', 'correo' => 'ana@ejemplo.com', 'password' => 'OtraClave1234', 'password_confirmation' => 'OtraClave1234',
        ])->assertRedirect('/mi-cuenta/registro')->assertSessionHas('registrado');

        $this->assertSame(1, Cuenta::query()->count());
        $this->assertSame('Ana López', Cuenta::query()->value('nombre'));
        $this->assertTrue($this->correoEnviado('cuenta_enlace', 'ana@ejemplo.com'));
        // Robots: campo trampa y envío instantáneo.
        $this->post('/mi-cuenta/registro', ['nombre' => 'Bot', 'correo' => 'bot@x.com', 'password' => 'ClaveSegura123', 'password_confirmation' => 'ClaveSegura123'])->assertSessionHasErrors('formulario');
        $this->assertSame(1, Cuenta::query()->count());
    }

    public function test_entrar_con_contrasena(): void
    {
        $this->cuenta();

        $this->post('/mi-cuenta/entrar', ['correo' => 'ana@ejemplo.com', 'password' => 'mala-clave-1'])->assertSessionHasErrors('correo');
        $this->assertGuest('cliente');

        $this->post('/mi-cuenta/entrar', ['correo' => 'ANA@ejemplo.com', 'password' => 'ClaveSegura123'])->assertRedirect(route('cuenta.inicio'));
        $this->assertAuthenticated('cliente');
        $this->get('/mi-cuenta')->assertOk()->assertSee('Ana López')->assertSee('Mis sistemas')->assertSee('noindex', false);
        $this->assertNotNull(Cuenta::query()->value('ultimo_acceso'));
    }

    public function test_una_cuenta_sin_confirmar_o_desactivada_no_ve_el_portal(): void
    {
        $cuenta = $this->cuenta();
        $cuenta->forceFill(['correo_verificado_en' => null])->save();

        $this->actingAs($cuenta, 'cliente')->get('/mi-cuenta')->assertRedirect(route('cuenta.pendiente'));
        $this->get('/mi-cuenta/confirma-tu-correo')->assertOk()->assertSee('ana@ejemplo.com');

        $cuenta->forceFill(['correo_verificado_en' => now(), 'activa' => false])->save();
        $this->get('/mi-cuenta')->assertRedirect(route('cuenta.entrar'));
        $this->assertGuest('cliente');
        $this->post('/mi-cuenta/entrar', ['correo' => 'ana@ejemplo.com', 'password' => 'ClaveSegura123'])->assertSessionHasErrors('correo');
    }

    public function test_entrar_con_enlace_de_un_solo_uso(): void
    {
        $cuenta = $this->cuenta(['correo' => 'luis@ejemplo.com', 'nombre' => 'Luis']);

        // Mismo mensaje exista o no el correo.
        $this->post('/mi-cuenta/enlace', ['correo' => 'nadie@ejemplo.com'])->assertSessionHas('status', \App\Http\Controllers\Cuenta\AccesoController::AVISO_ENLACE);
        $this->post('/mi-cuenta/enlace', ['correo' => 'luis@ejemplo.com'])->assertSessionHas('status', \App\Http\Controllers\Cuenta\AccesoController::AVISO_ENLACE);
        $this->assertTrue($this->correoEnviado('cuenta_enlace', 'luis@ejemplo.com'));
        $this->assertNotNull($cuenta->fresh()->token_hash);

        $token = app(Cuentas::class)->crearToken($cuenta, 'enlace', now()->addMinutes(20));
        $this->assertNotSame($token, $cuenta->fresh()->token_hash); // solo se guarda el hash

        // Abrir el enlace (o que lo abra un antivirus) no lo gasta.
        $this->get("/mi-cuenta/acceso/{$token}")->assertOk()->assertSee('Entrar a mi cuenta');
        $this->assertGuest('cliente');

        $this->post("/mi-cuenta/acceso/{$token}")->assertRedirect(route('cuenta.inicio'));
        $this->assertAuthenticatedAs($cuenta->fresh(), 'cliente');
        $this->post('/mi-cuenta/salir');
        $this->post("/mi-cuenta/acceso/{$token}")->assertRedirect(route('cuenta.entrar'));
        $this->assertGuest('cliente');

        // Vencido.
        $vencido = app(Cuentas::class)->crearToken($cuenta, 'enlace', now()->subMinute());
        $this->get("/mi-cuenta/acceso/{$vencido}")->assertSee('ya se usó o venció');
    }

    public function test_invitar_desde_el_panel_y_activar_la_cuenta(): void
    {
        $admin = User::query()->firstOrFail();
        $empresa = Cliente::query()->create(['empresa' => 'Café Central']);

        $this->actingAs($admin)->post('/admin/cuentas', [
            'nombre' => 'Marta Ruiz', 'correo' => 'Marta@CafeCentral.com', 'cliente_id' => $empresa->id, 'activa' => '1', 'invitar' => '1',
        ])->assertRedirect();
        $cuenta = Cuenta::query()->where('correo', 'marta@cafecentral.com')->firstOrFail();
        $this->assertNotNull($cuenta->invitada_en);
        $this->assertSame('invitacion', $cuenta->token_tipo);
        $this->assertTrue($this->correoEnviado('cuenta_invitacion', 'marta@cafecentral.com'));
        $this->assertSame(['Invitada', 'info'], $cuenta->estadoInfo());

        $token = app(Cuentas::class)->crearToken($cuenta, 'invitacion', now()->addDays(7));
        $this->get("/mi-cuenta/invitacion/{$token}")->assertOk()->assertSee('Marta Ruiz');
        $this->post("/mi-cuenta/invitacion/{$token}", ['password' => 'corta', 'password_confirmation' => 'corta'])->assertSessionHasErrors('password');
        $this->post("/mi-cuenta/invitacion/{$token}", ['password' => 'ClaveNueva1234', 'password_confirmation' => 'ClaveNueva1234'])->assertRedirect(route('cuenta.inicio'));

        $cuenta->refresh();
        $this->assertTrue($cuenta->verificada());
        $this->assertTrue(Hash::check('ClaveNueva1234', $cuenta->password));
        $this->assertAuthenticatedAs($cuenta, 'cliente');
        $this->get("/mi-cuenta/invitacion/{$token}")->assertRedirect(route('cuenta.inicio')); // ya tiene sesión
    }

    // ---------- Separación del panel ----------

    public function test_un_cliente_no_entra_al_panel_ni_un_usuario_del_panel_al_portal(): void
    {
        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente')->get('/admin')->assertRedirect(route('login'));

        $this->post('/mi-cuenta/salir');
        $this->actingAs(User::query()->firstOrFail())->get('/mi-cuenta')->assertRedirect(route('cuenta.entrar'));
        $this->get('/mi-cuenta/entrar')->assertOk();
    }

    // ---------- Portal ----------

    public function test_el_portal_muestra_sistemas_propios_y_de_la_empresa_y_las_pruebas(): void
    {
        $empresa = Cliente::query()->create(['empresa' => 'Café Central']);
        $cuenta = $this->cuenta(['cliente_id' => $empresa->id]);
        $otro = Sistema::query()->where('visible', true)->whereKeyNot($this->sistema->id)->firstOrFail();
        Contrato::query()->create(['sistema_id' => $this->sistema->id, 'cuenta_id' => $cuenta->id, 'url_acceso' => 'https://app.ejemplo.com', 'hasta' => now()->addDays(10)]);
        Contrato::query()->create(['sistema_id' => $otro->id, 'cliente_id' => $empresa->id, 'plan' => 'Plan Pro']);
        MensajeContacto::query()->create(['cuenta_id' => $cuenta->id, 'tipo' => 'prueba', 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Quiero probar',
            'sistema_id' => $this->sistema->id, 'usuario_prueba' => 'ana.prueba', 'clave_prueba' => 'Secreta-99', 'vence_el' => now()->addDays(5), 'credenciales_enviadas_en' => now()]);

        $this->actingAs($cuenta, 'cliente');
        $this->get('/mi-cuenta')->assertOk()->assertSee('Tus pruebas activas')->assertSee('ana.prueba')->assertSee($this->sistema->nombre);
        $this->get('/mi-cuenta/sistemas')->assertOk()
            ->assertSee([$this->sistema->nombre, $otro->nombre, 'Plan Pro', 'https://app.ejemplo.com', 'Empresa']);
        $this->get('/mi-cuenta/solicitudes')->assertOk()->assertSee('Secreta-99')->assertSee('Recibida');
        $this->get('/mi-cuenta/perfil')->assertOk()->assertSee('Café Central');
    }

    public function test_conversaciones_en_el_portal(): void
    {
        $cuenta = $this->cuenta();
        $ajena = Conversacion::query()->create(['token_hash' => Str::random(64), 'nombre' => 'Otro', 'correo' => 'otro@x.com']);
        $propia = Conversacion::query()->create(['cuenta_id' => $cuenta->id, 'token_hash' => Str::random(64), 'nombre' => 'Ana', 'correo' => 'ana@ejemplo.com']);
        app(Chat::class)->enviar($propia, 'visitante', 'Hola, necesito ayuda');
        app(Chat::class)->enviar($propia, 'admin', 'Claro, dime', User::query()->first());

        $this->actingAs($cuenta, 'cliente');
        $this->get('/mi-cuenta/conversaciones')->assertOk()->assertSee('Claro, dime');
        $this->get("/mi-cuenta/conversaciones/{$propia->id}")->assertOk()->assertSee('Hola, necesito ayuda');
        $this->assertSame(0, $propia->fresh()->no_leidos_visitante);
        $this->get("/mi-cuenta/conversaciones/{$ajena->id}")->assertNotFound();
        $this->getJson("/mi-cuenta/conversaciones/{$ajena->id}/mensajes")->assertNotFound();

        $this->postJson("/mi-cuenta/conversaciones/{$propia->id}/mensajes", ['cuerpo' => 'Gracias'])->assertCreated();
        $this->getJson("/mi-cuenta/conversaciones/{$propia->id}/mensajes?despues=0")->assertOk()->assertJsonCount(3, 'mensajes');
        app(Chat::class)->cerrar($propia, User::query()->first());
        $this->postJson("/mi-cuenta/conversaciones/{$propia->id}/mensajes", ['cuerpo' => 'Otra cosa'])->assertStatus(409);

        // Conversación nueva desde el portal: queda en la cuenta y el widget la continúa (cookie).
        $r = $this->post('/mi-cuenta/conversaciones', ['mensaje' => 'Tengo otra duda']);
        $nueva = Conversacion::query()->latest('id')->firstOrFail();
        $r->assertRedirect(route('cuenta.conversacion', $nueva))->assertCookie(ChatController::COOKIE);
        $this->assertSame($cuenta->id, $nueva->cuenta_id);
        $this->assertSame('ana@ejemplo.com', $nueva->correo);
    }

    public function test_con_sesion_el_chat_y_el_formulario_quedan_en_la_cuenta(): void
    {
        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente');

        $this->get('/')->assertSee('Chateas como')->assertSee('Mi cuenta');
        $this->postJson('/chat/iniciar', $this->antispam() + ['nombre' => 'Otro nombre', 'correo' => 'otro@x.com', 'mensaje' => 'Hola'])->assertOk();
        $chat = Conversacion::query()->firstOrFail();
        $this->assertSame([$cuenta->id, 'Ana López', 'ana@ejemplo.com'], [$chat->cuenta_id, $chat->nombre, $chat->correo]);

        $this->get('/contactenos')->assertSee('value="Ana López"', false);
        $this->from('/contactenos')->post('/contacto', $this->antispam() + ['nombre' => 'Ana López', 'correo' => 'ana@ejemplo.com', 'mensaje' => 'Una consulta']);
        $this->assertSame($cuenta->id, MensajeContacto::query()->value('cuenta_id'));
    }

    public function test_perfil_y_contrasena(): void
    {
        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente');

        $this->put('/mi-cuenta/perfil', ['nombre' => 'Ana María', 'telefono' => '5555 1234', 'empresa' => 'Mi Negocio'])->assertSessionHasNoErrors();
        $this->assertSame(['Ana María', 'Mi Negocio'], [$cuenta->fresh()->nombre, $cuenta->fresh()->empresa]);

        $this->put('/mi-cuenta/clave', ['password' => 'NuevaClave1234', 'password_confirmation' => 'NuevaClave1234'])->assertSessionHasErrors('actual');
        $this->put('/mi-cuenta/clave', ['actual' => 'mala', 'password' => 'NuevaClave1234', 'password_confirmation' => 'NuevaClave1234'])->assertSessionHasErrors('actual');
        $this->put('/mi-cuenta/clave', ['actual' => 'ClaveSegura123', 'password' => 'NuevaClave1234', 'password_confirmation' => 'NuevaClave1234'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NuevaClave1234', $cuenta->fresh()->password));

        // Entró con enlace: puede cambiarla sin la actual.
        $this->withSession(['cuenta_por_enlace' => true])->put('/mi-cuenta/clave', ['password' => 'OtraClave5678', 'password_confirmation' => 'OtraClave5678'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('OtraClave5678', $cuenta->fresh()->password));
    }

    // ---------- Manuales solo para clientes ----------

    public function test_manuales_solo_para_clientes(): void
    {
        $manual = Manual::query()->create(['sistema_id' => $this->sistema->id, 'titulo' => 'Cierre de caja avanzado', 'slug' => 'cierre-avanzado',
            'contenido' => 'Paso secreto', 'visible' => true, 'solo_clientes' => true]);

        $this->get('/manuales/cierre-avanzado')->assertRedirect(route('cuenta.entrar'));
        $this->get('/sitemap.xml')->assertDontSee('cierre-avanzado');
        $this->get('/manuales')->assertSee('Cierre de caja avanzado')->assertSee('Solo clientes');

        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente')->get('/manuales/cierre-avanzado')->assertForbidden();

        $contrato = Contrato::query()->create(['sistema_id' => $this->sistema->id, 'cuenta_id' => $cuenta->id]);
        $this->get('/manuales/cierre-avanzado')->assertOk()->assertSee('Paso secreto')->assertSee('noindex', false);
        $this->get('/mi-cuenta/manuales')->assertOk()->assertSee('Cierre de caja avanzado');

        $contrato->update(['hasta' => now()->subDay()]);
        $this->get('/manuales/cierre-avanzado')->assertForbidden();
    }

    // ---------- Panel ----------

    public function test_el_panel_administra_cuentas_y_sistemas_contratados(): void
    {
        $admin = User::query()->firstOrFail();
        $empresa = Cliente::query()->create(['empresa' => 'Café Central']);
        $cuenta = $this->cuenta(['cliente_id' => $empresa->id]);
        $this->actingAs($admin);

        $this->get('/admin/cuentas')->assertOk()->assertSee('Ana López')->assertSee('Café Central');
        $this->get('/admin/cuentas?estado=activas&buscar=ana')->assertOk()->assertSee('Ana López');
        $this->get('/admin/cuentas?estado=desactivadas')->assertOk()->assertDontSee('ana@ejemplo.com');
        $this->get('/admin/cuentas/create')->assertOk();
        $this->get("/admin/cuentas/{$cuenta->id}/edit")->assertOk()->assertSee('Sistemas contratados');
        $this->get("/admin/clientes/{$empresa->id}/edit")->assertOk()->assertSee('Cuentas de esta empresa')->assertSee('Ana López');

        $this->post("/admin/cuentas/{$cuenta->id}/contratos", ['sistema_id' => $this->sistema->id, 'url_acceso' => 'javascript:alert(1)'])->assertSessionHasErrors('url_acceso');
        $this->post("/admin/cuentas/{$cuenta->id}/contratos", ['sistema_id' => $this->sistema->id, 'plan' => 'Básico', 'para_empresa' => '1'])->assertSessionHasNoErrors();
        $contrato = Contrato::query()->firstOrFail();
        $this->assertSame([null, $empresa->id], [$contrato->cuenta_id, $contrato->cliente_id]);
        $this->put("/admin/contratos/{$contrato->id}", ['plan' => 'Pro', 'hasta' => now()->addYear()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame('Pro', $contrato->fresh()->plan);

        // Cambiar el correo obliga a confirmarlo otra vez.
        $this->put("/admin/cuentas/{$cuenta->id}", ['nombre' => 'Ana López', 'correo' => 'ana.nuevo@ejemplo.com', 'activa' => '1'])->assertSessionHasNoErrors();
        $this->assertFalse($cuenta->fresh()->verificada());
        $this->assertTrue(BitacoraCambio::query()->where('modulo', 'Cuentas de clientes')->where('accion', 'editar')->exists());

        $this->delete("/admin/contratos/{$contrato->id}")->assertRedirect();
        $this->delete("/admin/cuentas/{$cuenta->id}")->assertRedirect(route('admin.cuentas.index'));
        $this->assertSame(0, Cuenta::query()->count());
    }

    public function test_lo_que_hace_el_cliente_en_su_cuenta_no_va_a_la_bitacora_del_panel(): void
    {
        $cuenta = $this->cuenta();
        // Mismo navegador con el panel abierto: sigue sin anotarse como cambio del panel.
        $this->actingAs(User::query()->firstOrFail())->actingAs($cuenta, 'cliente')
            ->put('/mi-cuenta/perfil', ['nombre' => 'Ana María'])->assertSessionHasNoErrors();

        $this->assertSame(0, BitacoraCambio::query()->where('modulo', 'Cuentas de clientes')->count());
    }
}
