<?php

namespace Tests\Feature;

use App\Models\ConfiguracionCorreo;
use App\Models\ConfiguracionPagos;
use App\Models\Contrato;
use App\Models\Cuenta;
use App\Models\Pagina;
use App\Models\Pago;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\Sistema;
use App\Models\Suscripcion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as Peticion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    private Sistema $sistema;

    private User $admin;

    /** Lo que «PayPal» responde en las pruebas (se cambia en cada prueba). */
    private array $pp = [
        'estado' => 'ACTIVE',
        'siguiente' => '2026-11-01T10:00:00Z',
        'transacciones' => [],
        'captura' => ['id' => 'CAP-1', 'status' => 'COMPLETED', 'amount' => ['value' => '150.00', 'currency_code' => 'USD']],
        'verificacion' => 'SUCCESS',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->sistema = Sistema::query()->where('visible', true)->firstOrFail();
        $this->admin = User::query()->firstOrFail();
        $this->fakePayPal();
    }

    private function conectarPayPal(array $datos = []): ConfiguracionPagos
    {
        $cfg = ConfiguracionPagos::actual();
        $cfg->forceFill($datos + ['activo' => true, 'modo' => 'sandbox', 'client_id' => 'cliente-prueba', 'client_secret' => 'secreto-prueba'])->save();
        ConfiguracionCorreo::actual()->update(['avisos_a' => 'yo@solutionsgt.com']);

        return $cfg;
    }

    private function fakePayPal(): void
    {
        Http::fake(function (Peticion $r) {
            $url = $r->url();
            $json = fn (array $datos, int $codigo = 200) => Http::response($datos, $codigo);

            return match (true) {
                str_contains($url, '/v1/oauth2/token') => $json(['access_token' => 'token-prueba', 'expires_in' => 3600]),
                str_contains($url, '/v1/catalogs/products') => $json(['id' => 'PROD-1']),
                str_ends_with($url, '/deactivate') => Http::response(null, 204),
                str_contains($url, '/v1/billing/plans') => $json(['id' => 'P-PLAN-'.substr(md5($r->body()), 0, 6)]),
                str_contains($url, '/transactions') => $json(['transactions' => $this->pp['transacciones']]),
                str_ends_with($url, '/cancel') => tap(Http::response(null, 204), fn () => $this->pp['estado'] = 'CANCELLED'),
                $r->method() === 'POST' && str_ends_with($url, '/v1/billing/subscriptions') => $json(['id' => 'I-SUB1', 'status' => 'APPROVAL_PENDING',
                    'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-1']]]),
                str_contains($url, '/v1/billing/subscriptions/') => $json(['id' => basename($url), 'status' => $this->pp['estados'][basename($url)] ?? $this->pp['estado'],
                    'billing_info' => ['next_billing_time' => $this->pp['siguiente']]]),
                str_ends_with($url, '/capture') => $json(['id' => 'ORD-1', 'status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [$this->pp['captura']]]]]]),
                $r->method() === 'POST' && str_ends_with($url, '/v2/checkout/orders') => $json(['id' => 'ORD-1', 'status' => 'CREATED',
                    'links' => [['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORD-1']]]),
                str_contains($url, '/v2/checkout/orders/') => $json(['id' => 'ORD-1', 'status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [$this->pp['captura']]]]]]),
                str_contains($url, '/verify-webhook-signature') => $json(['verification_status' => $this->pp['verificacion']]),
                default => $json(['name' => 'NO_SIMULADO'], 500),
            };
        });
    }

    private function cuenta(string $correo = 'ana@ejemplo.com'): Cuenta
    {
        $cuenta = new Cuenta(['nombre' => 'Ana López', 'correo' => $correo, 'activa' => true]);
        $cuenta->password = 'ClaveSegura123';
        $cuenta->correo_verificado_en = now();
        $cuenta->save();

        return $cuenta;
    }

    /** Producto del sistema con precio mensual y de por vida, y un servicio con pago único. */
    private function catalogo(): array
    {
        $software = Producto::query()->create(['nombre' => 'Sistema en la nube', 'slug' => 'sistema-nube', 'tipo' => 'sistema', 'sistema_id' => $this->sistema->id, 'activo' => true]);
        $mensual = $software->precios()->create(['periodo' => 'mensual', 'monto' => '25.00', 'activo' => true]);
        $software->precios()->create(['periodo' => 'de_por_vida', 'monto' => null, 'activo' => true]);
        $servicio = Producto::query()->create(['nombre' => 'Instalación', 'slug' => 'instalacion', 'tipo' => 'servicio', 'activo' => true]);
        $unico = $servicio->precios()->create(['periodo' => 'unico', 'monto' => '150.00', 'activo' => true]);

        return [$software, $mensual, $servicio, $unico];
    }

    private function correo(string $plantilla, ?string $destino = null): bool
    {
        return DB::table('bitacora_correos')->where('plantilla', $plantilla)->when($destino, fn ($q) => $q->where('destinatario', $destino))->exists();
    }

    // ---------- Panel ----------

    public function test_el_panel_crea_un_producto_con_precios_por_periodo(): void
    {
        $this->actingAs($this->admin)->post('/admin/productos', [
            'nombre' => 'Punto de venta', 'tipo' => 'sistema', 'sistema_id' => $this->sistema->id, 'activo' => '1',
            'precios' => ['semanal' => ['activo' => '1', 'monto' => '7'], 'anual' => ['activo' => '1', 'monto' => '250.5'], 'trimestral' => ['monto' => '60']],
        ])->assertSessionHasNoErrors();

        $producto = Producto::query()->where('slug', 'punto-de-venta')->firstOrFail();
        $this->assertSame(['semanal', 'trimestral', 'anual'], $producto->precios()->pluck('periodo')->all());
        $this->assertSame('250.50', $producto->precios()->where('periodo', 'anual')->value('monto'));
        $this->assertFalse((bool) $producto->precios()->where('periodo', 'trimestral')->value('activo'));
        $this->assertDatabaseHas('bitacora_cambios', ['modulo' => 'Ventas']);
    }

    public function test_el_software_no_se_vende_con_pago_unico_y_un_precio_activo_pide_monto(): void
    {
        $this->actingAs($this->admin)->post('/admin/productos', [
            'nombre' => 'X', 'tipo' => 'sistema', 'sistema_id' => $this->sistema->id,
            'precios' => ['unico' => ['activo' => '1', 'monto' => '500']],
        ])->assertSessionHasErrors('precios.unico.monto');

        $this->actingAs($this->admin)->post('/admin/productos', [
            'nombre' => 'Y', 'tipo' => 'servicio', 'precios' => ['mensual' => ['activo' => '1']],
        ])->assertSessionHasErrors('precios.mensual.monto');

        $this->assertSame(0, Producto::query()->count());
    }

    public function test_cambiar_el_precio_retira_el_plan_viejo_de_paypal(): void
    {
        $this->conectarPayPal();
        [$software, $mensual] = $this->catalogo();
        $mensual->forceFill(['paypal_plan_id' => 'P-VIEJO', 'paypal_modo' => 'sandbox'])->saveQuietly();

        $this->actingAs($this->admin)->put("/admin/productos/{$software->id}", [
            'nombre' => $software->nombre, 'slug' => $software->slug, 'tipo' => 'sistema', 'sistema_id' => $this->sistema->id, 'activo' => '1',
            'precios' => ['mensual' => ['activo' => '1', 'monto' => '30']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $mensual->refresh();
        $this->assertSame('30.00', $mensual->monto);
        $this->assertNull($mensual->paypal_plan_id);
        Http::assertSent(fn (Peticion $r) => str_ends_with($r->url(), '/P-VIEJO/deactivate'));
    }

    public function test_la_conexion_guarda_el_secreto_cifrado_y_lo_conserva_si_se_deja_vacio(): void
    {
        $this->actingAs($this->admin)->post('/admin/ventas/paypal', ['modo' => 'sandbox', 'moneda' => 'USD', 'dias_gracia' => 3, 'activo' => '1'])
            ->assertSessionHasErrors('client_id');

        $this->actingAs($this->admin)->post('/admin/ventas/paypal', [
            'modo' => 'sandbox', 'moneda' => 'USD', 'dias_gracia' => 3, 'activo' => '1', 'client_id' => 'abc', 'client_secret' => 'muy-secreto',
        ])->assertSessionHasNoErrors();
        $this->assertNotSame('muy-secreto', DB::table('configuracion_pagos')->value('client_secret'));

        $this->actingAs($this->admin)->post('/admin/ventas/paypal', [
            'modo' => 'sandbox', 'moneda' => 'USD', 'dias_gracia' => 5, 'activo' => '1', 'client_id' => 'abc', 'client_secret' => '',
        ]);
        $cfg = ConfiguracionPagos::query()->firstOrFail();
        $this->assertSame('muy-secreto', $cfg->client_secret);
        $this->assertSame(5, $cfg->dias_gracia);

        $this->actingAs($this->admin)->post('/admin/ventas/paypal/probar')->assertSessionHas('status');
        $this->actingAs($this->admin)->get('/admin/ventas?ver=paypal')->assertOk()->assertSee(route('paypal.aviso'))->assertDontSee('muy-secreto');
    }

    // ---------- Sitio público ----------

    public function test_el_sitio_muestra_los_precios_con_el_boton_que_corresponde(): void
    {
        [$software, $mensual] = $this->catalogo();
        $pagina = Pagina::query()->where('es_inicio', true)->firstOrFail();
        $pagina->secciones()->create(['tipo' => 'tienda', 'titulo' => 'Precios', 'visible' => true, 'orden' => 99]);

        // Cobros apagados: se ven los precios, pero el botón lleva al contacto.
        $this->get('/')->assertOk()->assertSee('Sistema en la nube')->assertSee('$25.00')->assertSee('Me interesa')
            ->assertDontSee(route('cuenta.comprar', $mensual))->assertSee('Hablar con un asesor');

        $this->conectarPayPal();
        $this->get('/')->assertSee(route('cuenta.comprar', $mensual))->assertSee('Suscribirme');
        $this->get('/sistemas/'.$this->sistema->slug)->assertOk()->assertSee('Ver precios')->assertSee(route('cuenta.comprar', $mensual));

        $software->update(['activo' => false]);
        $this->get('/sistemas/'.$this->sistema->slug)->assertDontSee('Ver precios');
    }

    public function test_para_comprar_hay_que_entrar_a_la_cuenta_y_luego_vuelve_a_la_compra(): void
    {
        $this->conectarPayPal();
        [, $mensual] = $this->catalogo();
        $this->cuenta();

        $this->get(route('cuenta.comprar', $mensual))->assertRedirect(route('cuenta.entrar'));
        $this->post('/mi-cuenta/entrar', ['correo' => 'ana@ejemplo.com', 'password' => 'ClaveSegura123'])
            ->assertRedirect(route('cuenta.comprar', $mensual));
    }

    // ---------- Suscripciones ----------

    public function test_suscripcion_completa_da_acceso_registra_el_pago_y_avisa(): void
    {
        $this->conectarPayPal();
        [$software, $mensual] = $this->catalogo();
        $cuenta = $this->cuenta();

        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.comprar', $mensual))->assertOk()->assertSee('Continuar a PayPal');
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $mensual))->assertSessionHasErrors('acepto');
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $mensual), ['acepto' => '1'])
            ->assertRedirect('https://www.sandbox.paypal.com/webapps/billing/subscriptions?ba_token=BA-1');

        $s = Suscripcion::query()->firstOrFail();
        $this->assertSame('pendiente', $s->estado);
        $this->assertSame('PROD-1', $software->fresh()->paypal_id);
        $this->assertNotNull($mensual->fresh()->paypal_plan_id);

        // Vuelve de PayPal: se consulta a PayPal (no se cree lo que dice la dirección).
        $this->pp['transacciones'] = [['id' => 'TX-1', 'status' => 'COMPLETED', 'time' => '2026-10-01T10:00:00Z',
            'amount_with_breakdown' => ['gross_amount' => ['value' => '25.00', 'currency_code' => 'USD']]]];
        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.pagos.suscripcion', ['subscription_id' => 'I-SUB1']))
            ->assertRedirect(route('cuenta.pagos'))->assertSessionHas('status');

        $s->refresh();
        $this->assertSame('activa', $s->estado);
        $contrato = Contrato::query()->where('cuenta_id', $cuenta->id)->where('sistema_id', $this->sistema->id)->firstOrFail();
        $this->assertSame($contrato->id, $s->contrato_id);
        $this->assertSame('2026-11-04', $contrato->hasta->toDateString()); // próximo cobro + 3 días de gracia
        $this->assertDatabaseHas('pagos', ['paypal_id' => 'TX-1', 'estado' => 'completado', 'suscripcion_id' => $s->id]);
        $this->assertTrue($this->correo('pago_recibido', 'ana@ejemplo.com'));
        $this->assertTrue($this->correo('nueva_venta'));

        // Volver otra vez no duplica el pago ni el aviso.
        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.pagos.suscripcion', ['subscription_id' => 'I-SUB1']));
        $this->assertSame(1, Pago::query()->count());
        $this->assertSame(1, DB::table('bitacora_correos')->where('plantilla', 'pago_recibido')->count());

        $this->actingAs($cuenta, 'cliente')->get('/mi-cuenta/pagos')->assertOk()->assertSee('Cancelar suscripción')->assertSee('TX-1');
        $this->actingAs($cuenta, 'cliente')->get('/mi-cuenta/sistemas')->assertSee($this->sistema->nombre);

        // No puede suscribirse dos veces al mismo producto.
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $mensual), ['acepto' => '1'])->assertSessionHasErrors('pago');
    }

    public function test_cancelar_la_suscripcion_corta_los_cobros_pero_respeta_lo_pagado(): void
    {
        $this->conectarPayPal();
        [, $mensual] = $this->catalogo();
        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $mensual), ['acepto' => '1']);
        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.pagos.suscripcion', ['subscription_id' => 'I-SUB1']));
        $s = Suscripcion::query()->firstOrFail();

        // Otra cuenta no la ve ni la cancela.
        $otra = $this->cuenta('otro@ejemplo.com');
        $this->actingAs($otra, 'cliente')->post(route('cuenta.pagos.cancelar', $s))->assertNotFound();
        $this->actingAs($otra, 'cliente')->get(route('cuenta.pagos.suscripcion', ['subscription_id' => 'I-SUB1']))->assertSessionHasErrors('pago');

        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.pagos.cancelar', $s))->assertSessionHas('status');
        $s->refresh();
        $this->assertSame('cancelada', $s->estado);
        $this->assertNotNull($s->cancelada_en);
        $this->assertSame('2026-11-04', $s->contrato->hasta->toDateString());
        $this->assertTrue($this->correo('suscripcion_cancelada'));
        Http::assertSent(fn (Peticion $r) => str_ends_with($r->url(), '/I-SUB1/cancel'));
    }

    public function test_el_aviso_de_paypal_se_verifica_y_solo_dispara_una_consulta(): void
    {
        $this->conectarPayPal(['webhook_id' => 'WH-1']);
        [, $mensual] = $this->catalogo();
        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $mensual), ['acepto' => '1']);
        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.pagos.suscripcion', ['subscription_id' => 'I-SUB1']));
        auth('cliente')->logout();

        $evento = ['event_type' => 'PAYMENT.SALE.COMPLETED', 'resource' => ['id' => 'TX-2', 'billing_agreement_id' => 'I-SUB1', 'amount' => ['total' => '9999']]];

        // Firma falsa: se rechaza.
        $this->pp['verificacion'] = 'FAILURE';
        $this->postJson('/paypal/aviso', $evento)->assertStatus(400);

        // Firma buena: se consulta a PayPal y se guarda lo que PayPal dice (no el monto del aviso).
        $this->pp['verificacion'] = 'SUCCESS';
        $this->pp['siguiente'] = '2026-12-01T10:00:00Z';
        $this->pp['transacciones'] = [['id' => 'TX-2', 'status' => 'COMPLETED', 'time' => '2026-11-01T10:00:00Z',
            'amount_with_breakdown' => ['gross_amount' => ['value' => '25.00', 'currency_code' => 'USD']]]];
        $this->postJson('/paypal/aviso', $evento)->assertOk();

        $this->assertDatabaseHas('pagos', ['paypal_id' => 'TX-2', 'monto' => '25.00']);
        $this->assertSame('2026-12-04', Suscripcion::query()->firstOrFail()->contrato->hasta->toDateString());
    }

    // ---------- Pago único ----------

    public function test_pago_unico_se_cobra_al_volver_y_valida_el_monto(): void
    {
        $this->conectarPayPal();
        [, , , $unico] = $this->catalogo();
        $cuenta = $this->cuenta();

        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $unico), ['acepto' => '1'])
            ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=ORD-1');
        $this->assertDatabaseHas('pagos', ['paypal_orden_id' => 'ORD-1', 'estado' => 'pendiente']);

        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.pagos.orden', ['token' => 'ORD-1']))->assertSessionHas('status');
        $this->assertDatabaseHas('pagos', ['paypal_orden_id' => 'ORD-1', 'paypal_id' => 'CAP-1', 'estado' => 'completado']);
        $this->assertTrue($this->correo('pago_recibido', 'ana@ejemplo.com'));
        $this->assertSame(0, Contrato::query()->count()); // un servicio no crea contrato de sistema

        // Si PayPal cobra otro monto, no se da por pagado.
        Pago::query()->delete();
        $this->pp['captura']['amount']['value'] = '1.00';
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $unico), ['acepto' => '1']);
        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.pagos.orden', ['token' => 'ORD-1']))->assertSessionHasErrors('pago');
        $this->assertDatabaseHas('pagos', ['paypal_orden_id' => 'ORD-1', 'estado' => 'fallido']);
    }

    public function test_con_los_cobros_apagados_o_de_por_vida_no_se_puede_pagar(): void
    {
        [$software, $mensual] = $this->catalogo();
        $cuenta = $this->cuenta();
        $this->actingAs($cuenta, 'cliente')->get(route('cuenta.comprar', $mensual))->assertRedirect(route('cuenta.pagos'));
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $mensual), ['acepto' => '1'])->assertSessionHasErrors('pago');

        $this->conectarPayPal();
        $porVida = $software->precios()->where('periodo', 'de_por_vida')->firstOrFail();
        $this->actingAs($cuenta, 'cliente')->post(route('cuenta.comprar.pagar', $porVida), ['acepto' => '1'])->assertSessionHasErrors('pago');
        Http::assertNotSent(fn (Peticion $r) => str_contains($r->url(), '/v1/billing/subscriptions'));
        $this->assertSame(0, Suscripcion::query()->count());
    }

    // ---------- Tarea programada ----------

    public function test_la_tarea_extiende_suscripciones_por_cobrar_y_borra_intentos_abandonados(): void
    {
        $this->conectarPayPal();
        [, $mensual] = $this->catalogo();
        $cuenta = $this->cuenta();
        $base = ['cuenta_id' => $cuenta->id, 'producto_id' => $mensual->producto_id, 'precio_id' => $mensual->id, 'paypal_modo' => 'sandbox',
            'descripcion' => 'Sistema en la nube · Mensual', 'periodo' => 'mensual', 'monto' => '25.00', 'moneda' => 'USD', 'correo' => $cuenta->correo];
        $activa = Suscripcion::query()->create($base + ['paypal_id' => 'I-SUB1', 'estado' => 'activa', 'activada_en' => now()->subMonth(), 'siguiente_cobro' => now()]);
        $vieja = Suscripcion::query()->create($base + ['paypal_id' => 'I-VIEJA', 'estado' => 'pendiente']);
        $vieja->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();
        // La pendiente vieja: PayPal la tiene sin aprobar → se borra.
        $this->pp['estados'] = ['I-VIEJA' => 'APPROVAL_PENDING'];

        $this->artisan('pagos:sincronizar')->assertSuccessful();
        $this->assertNotNull($activa->fresh()->contrato_id);
        $this->assertSame('2026-11-04', $activa->fresh()->contrato->hasta->toDateString());
        $this->assertNull(Suscripcion::query()->find($vieja->id));
    }
}
