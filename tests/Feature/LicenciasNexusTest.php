<?php

namespace Tests\Feature;

use App\Models\ConfiguracionCorreo;
use App\Models\ConfiguracionLicencias;
use App\Models\Contrato;
use App\Models\Instalacion;
use App\Models\Sistema;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as Peticion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Licencias Nexus: cada instalación de Nexus ERP consulta aquí su licencia firmada; el panel registra
 * pagos, prórrogas y suspensiones y le avisa que consulte ya. Protocolo: nexus-erp, docs/instalacion-clientes/licencia.md.
 */
class LicenciasNexusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** Respuesta de la instalación al aviso «consulta ya». */
    private array $nexus = ['ok' => true];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();
        Http::fake(['acme.test/licencia/sincronizar' => fn () => Http::response($this->nexus)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function hoy(string $fecha): void
    {
        Carbon::setTestNow($fecha.' 10:00:00');
        CarbonImmutable::setTestNow($fecha.' 10:00:00');
    }

    /** Crea la instalación desde el panel y devuelve [instalación, token]. */
    private function crear(array $datos = []): array
    {
        $this->actingAs($this->admin)->post(route('admin.licencias.store'), $datos + [
            'nombre' => 'Seguridad Acme', 'identificador' => 'acme', 'url' => 'https://acme.test', 'tipo' => 'renta',
            'moneda' => 'GTQ', 'meses_periodo' => 1, 'monto' => 1500, 'correo_cobro' => 'pagos@acme.test',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $token = session('token_licencia');
        $this->assertSame(48, strlen($token));

        return [Instalacion::query()->where('identificador', $datos['identificador'] ?? 'acme')->sole(), $token];
    }

    /** Lo que hace Nexus: consulta con su token y comprueba la firma con la clave pública del panel. */
    private function consultar(string $token, array $informe = []): array
    {
        $r = $this->withToken($token)->postJson(route('api.nexus.licencia'), $informe + [
            'instalacion' => 'acme', 'version' => '2026-11-04', 'usuarios_activos' => 12, 'empresas_activas' => 2, 'etapa' => 'AL_DIA',
        ])->assertOk()->json();
        $crudo = base64_decode($r['datos']);
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode($r['firma']), $crudo, base64_decode(ConfiguracionLicencias::actual()->clave_publica)));

        return json_decode($crudo, true);
    }

    public function test_la_instalacion_consulta_su_licencia_firmada_con_su_token(): void
    {
        $this->hoy('2026-11-04');
        [$i, $token] = $this->crear(['vence_el' => '2026-11-30', 'contacto' => 'WhatsApp 5555-5555']);
        $this->get(route('admin.licencias.show', $i))->assertOk(); // el token ya no se muestra
        $this->assertNotSame($token, $i->token_hash);

        $d = $this->consultar($token);
        $this->assertSame(['acme', '2026-11-30', 'NORMAL', 7, 7, 8, false, 'WhatsApp 5555-5555'], [
            $d['instalacion'], $d['vence_el'], $d['modo'], $d['dias_aviso'], $d['dias_gracia'], $d['dias_restringido'], $d['bloquear_facturacion'], $d['contacto'],
        ]);
        $i->refresh();
        $this->assertSame(['2026-11-04', 12, 2, 'AL_DIA'], [$i->version, $i->usuarios_activos, $i->empresas_activas, $i->etapa_reportada]);
        $this->assertFalse($i->sinComunicacion());

        // Sin sesión del panel: sin token o con el de otra instalación, nada.
        auth()->logout();
        $this->flushHeaders()->postJson(route('api.nexus.licencia'), ['instalacion' => 'acme'])->assertUnauthorized();
        $this->withToken('otro')->postJson(route('api.nexus.licencia'), ['instalacion' => 'acme'])->assertUnauthorized();
        $this->withToken($token)->postJson(route('api.nexus.licencia'), ['instalacion' => 'beta'])->assertForbidden();

        // Un token nuevo invalida el anterior.
        $this->actingAs($this->admin)->post(route('admin.licencias.token', $i))->assertSessionHas('token_licencia');
        auth()->logout();
        $this->withToken($token)->postJson(route('api.nexus.licencia'), ['instalacion' => 'acme'])->assertUnauthorized();
    }

    public function test_registrar_un_pago_corre_el_vencimiento_y_avisa_a_la_instalacion(): void
    {
        $this->hoy('2026-12-20'); // 20 días de mora: solo lectura
        [$i, $token] = $this->crear(['vence_el' => '2026-11-30']);
        $i->update(['modo' => 'SUSPENDIDO']);
        $this->assertSame('SOLO_LECTURA', $i->estado()['etapa']);

        $this->post(route('admin.licencias.pago', $i), ['pagado_el' => '2026-12-20', 'monto' => 3000, 'moneda' => 'GTQ', 'metodo' => 'transferencia', 'referencia' => 'BI-123', 'meses' => 2])
            ->assertSessionHasNoErrors()->assertSessionHas('status', 'Pago registrado: pagado hasta el 30/01/2027. La instalación ya lo aplicó.');
        $i->refresh();
        // Desde el vencimiento anterior (30/11), no desde el día del pago: quien paga tarde no gana días.
        $this->assertSame(['2027-01-30', 'NORMAL', 'AL_DIA', 'Aplicada'], [$i->vence_el->format('Y-m-d'), $i->modo, $i->estado()['etapa'], $i->aviso_resultado]);
        Http::assertSent(fn (Peticion $r) => $r->url() === 'https://acme.test/licencia/sincronizar' && empty($r->data()));
        $this->assertSame('2027-01-30', $this->consultar($token)['vence_el']);

        // Solo el último pago se anula, y el vencimiento vuelve al anterior.
        $this->post(route('admin.licencias.pago', $i), ['pagado_el' => '2026-12-20', 'monto' => 1500, 'moneda' => 'GTQ', 'metodo' => 'efectivo', 'meses' => 1]);
        $this->assertSame('2027-02-28', $i->refresh()->vence_el->format('Y-m-d'));
        [$segundo, $primero] = $i->pagos->all();
        $this->post(route('admin.licencias.pago.anular', $primero))->assertSessionHasErrors('pago');
        $this->post(route('admin.licencias.pago.anular', $segundo))->assertSessionHasNoErrors();
        $this->assertSame('2027-01-30', $i->refresh()->vence_el->format('Y-m-d'));

        // Si la instalación no responde, el pago queda y lo toma en su próxima consulta.
        $this->nexus = ['ok' => false];
        $this->post(route('admin.licencias.pago', $i), ['pagado_el' => '2026-12-20', 'monto' => 1500, 'moneda' => 'GTQ', 'metodo' => 'efectivo', 'meses' => 1])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'lo tomará en su próxima consulta'));
        $this->assertSame('2027-02-28', $i->refresh()->vence_el->format('Y-m-d'));
    }

    public function test_el_primer_pago_cuenta_desde_el_dia_del_pago(): void
    {
        $this->hoy('2026-11-04');
        [$i] = $this->crear();
        $this->assertNull($i->venceEfectivo()); // sin pagos: sin vencimiento todavía
        $this->post(route('admin.licencias.pago', $i), ['pagado_el' => '2026-11-04', 'monto' => 1500, 'moneda' => 'GTQ', 'metodo' => 'deposito', 'meses' => 1]);
        $this->assertSame('2026-12-03', $i->refresh()->vence_el->format('Y-m-d'));
    }

    public function test_prorroga_modos_y_venta(): void
    {
        $this->hoy('2026-12-10');
        [$i, $token] = $this->crear(['vence_el' => '2026-11-30']);
        $this->assertSame('RESTRINGIDO', $i->estado()['etapa']);

        $this->post(route('admin.licencias.prorroga', $i), ['dias' => 5])->assertSessionHasNoErrors();
        $this->assertSame(['2026-12-15', 'POR_VENCER'], [$i->refresh()->prorroga_hasta->format('Y-m-d'), $i->estado()['etapa']]);
        $this->assertSame('2026-12-15', $this->consultar($token)['vence_el']);
        $this->delete(route('admin.licencias.prorroga.quitar', $i));
        $this->assertSame('RESTRINGIDO', $i->refresh()->estado()['etapa']);

        $this->post(route('admin.licencias.modo', $i), ['modo' => 'FORZAR_ACTIVO']);
        $this->assertSame(['FORZAR_ACTIVO', 'AL_DIA'], [$i->refresh()->modo, $i->estado()['etapa']]);
        $this->assertSame('FORZAR_ACTIVO', $this->consultar($token)['modo']);

        // Una instalación vendida no vence ni lleva pagos.
        [$v, $tokenV] = $this->crear(['identificador' => 'beta', 'url' => 'https://beta.test', 'tipo' => 'venta', 'vence_el' => '2026-01-01']);
        $this->assertSame(['AL_DIA', null], [$v->estado()['etapa'], $v->venceEfectivo()]);
        $this->post(route('admin.licencias.pago', $v), ['pagado_el' => '2026-12-10', 'monto' => 1, 'moneda' => 'GTQ', 'metodo' => 'efectivo', 'meses' => 1])->assertSessionHasErrors('pago');
    }

    public function test_el_contrato_de_paypal_extiende_la_licencia_solo(): void
    {
        $this->hoy('2026-12-10');
        $sistema = Sistema::query()->firstOrFail();
        $contrato = Contrato::query()->create(['sistema_id' => $sistema->id, 'plan' => 'Suscripción mensual (PayPal)', 'desde' => '2026-10-01', 'hasta' => '2026-11-30']);
        [$i] = $this->crear(['contrato_id' => $contrato->id, 'vence_el' => '2026-11-15']);
        $this->assertSame('2026-11-30', $i->venceEfectivo()->format('Y-m-d')); // el mayor entre pago a mano y contrato

        // PayPal cobró: el contrato se extiende y la instalación consulta ya.
        Http::fake(['acme.test/*' => fn () => Http::response(['ok' => true])]);
        $contrato->update(['hasta' => '2027-01-03']);
        Http::assertSent(fn (Peticion $r) => $r->url() === 'https://acme.test/licencia/sincronizar');
        $this->assertSame(['2027-01-03', 'AL_DIA'], [$i->refresh()->venceEfectivo()->format('Y-m-d'), $i->estado()['etapa']]);
    }

    public function test_la_revision_diaria_avisa_cada_etapa_una_vez(): void
    {
        ConfiguracionCorreo::actual()->update(['avisos_a' => 'yo@solutionsgt.com']);
        $this->hoy('2026-11-25');
        [$i, $token] = $this->crear(['vence_el' => '2026-11-30']);
        $avisos = fn (string $plantilla, string $destino) => DB::table('bitacora_correos')->where('plantilla', $plantilla)->where('destinatario', $destino)->count();

        $this->artisan('licencias:revisar')->assertSuccessful();
        $this->artisan('licencias:revisar')->assertSuccessful(); // mismo día: no repite
        $this->assertSame([1, 0], [$avisos('licencia_aviso', 'pagos@acme.test'), $avisos('licencia_estado', 'yo@solutionsgt.com')]);

        $this->hoy('2026-12-02'); // gracia: al cliente y a ti
        $this->artisan('licencias:revisar');
        $this->assertSame([2, 1], [$avisos('licencia_aviso', 'pagos@acme.test'), $avisos('licencia_estado', 'yo@solutionsgt.com')]);

        // Se conectó y luego dejó de consultar más de un día: te avisa una vez.
        $this->consultar($token);
        $this->hoy('2026-12-04');
        $this->artisan('licencias:revisar');
        $this->artisan('licencias:revisar');
        $this->assertSame(2, $avisos('licencia_estado', 'yo@solutionsgt.com'));
        $this->get(route('admin.licencias.index', ['filtro' => 'sin_comunicacion']))->assertOk()->assertSee('Seguridad Acme');
        $this->get(route('admin.licencias.index', ['filtro' => 'mora']))->assertSee('Seguridad Acme')->assertSee('4 días de mora');
    }

    public function test_las_pantallas_abren_y_validan(): void
    {
        [$i] = $this->crear();
        $this->get(route('admin.licencias.index'))->assertOk()->assertSee(ConfiguracionLicencias::actual()->clave_publica);
        $this->get(route('admin.licencias.create'))->assertOk();
        $this->get(route('admin.licencias.edit', $i))->assertOk();
        $this->post(route('admin.licencias.store'), ['nombre' => 'X', 'identificador' => 'Con Espacios', 'url' => 'no-es-url', 'tipo' => 'renta', 'moneda' => 'GTQ', 'meses_periodo' => 1])
            ->assertSessionHasErrors(['identificador', 'url']);
        $this->post(route('admin.licencias.store'), ['nombre' => 'X', 'identificador' => 'acme', 'url' => 'https://x.test', 'tipo' => 'renta', 'moneda' => 'GTQ', 'meses_periodo' => 1, 'enlace_pago' => 'javascript:alert(1)'])
            ->assertSessionHasErrors(['identificador', 'enlace_pago']);
        $this->put(route('admin.licencias.configuracion'), ['contacto' => 'Pagos', 'dias_aviso' => 5, 'dias_gracia' => 3, 'dias_restringido' => 4])->assertSessionHasNoErrors();
        $this->assertSame([5, 3, 4], [$i->dias('aviso'), $i->dias('gracia'), $i->dias('restringido')]);
        $this->delete(route('admin.licencias.destroy', $i))->assertRedirect(route('admin.licencias.index'));
        $this->assertSame(0, Instalacion::query()->count());
    }
}
