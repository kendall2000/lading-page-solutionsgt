<?php

namespace Tests\Feature;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as Peticion;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Certificación FEL con INFILE (simulado): factura, notas NCRE/NDEB/NABN, anulación y XML.
 */
class FacturacionDteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** Peticiones enviadas a INFILE en la prueba. */
    private array $enviadas = [];

    /** Si INFILE rechaza la anulación. */
    private bool $rechazarAnulacion = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->firstOrFail();

        ConfiguracionDTE::actual()->update([
            'activo' => true,
            'nit_emisor' => '12345678',
            'nombre_emisor' => 'SOLUTIONS GT',
            'nombre_comercial' => 'Solutions GT',
            'direccion' => 'Ciudad',
            'afiliacion_iva' => 'PEQ',
            'alias_ws' => 'ALIAS',
            'token_signer' => 'token',
            'llave_ws' => 'llave',
        ]);

        $contador = 0;
        Http::fake(function (Peticion $r) use (&$contador) {
            $this->enviadas[] = $r;
            $url = $r->url();

            if (str_contains($url, 'firma_xml')) {
                // Devuelve el mismo XML como «firmado»
                return Http::response(['resultado' => true, 'descripcion' => 'OK', 'archivo' => $r['archivo']]);
            }

            if (str_contains($url, 'anulacion')) {
                return $this->rechazarAnulacion
                    ? Http::response(['resultado' => false, 'descripcion' => 'Documento no se puede anular', 'descripcion_errores' => []])
                    : Http::response(['resultado' => true, 'descripcion' => 'Anulado']);
            }

            $contador++;

            return Http::response([
                'resultado' => true,
                'uuid' => "UUID-{$contador}",
                'serie' => "SERIE{$contador}",
                'numero' => 1000 + $contador,
                'fecha' => '2026-11-11T10:00:00-06:00',
                'xml_certificado' => base64_encode('<?xml version="1.0"?><certificado n="'.$contador.'"/>'),
            ]);
        });
    }

    private function facturaCertificada(float $total = 100.00): DocumentoTributario
    {
        $doc = DocumentoTributario::create([
            'tipo' => 'FACT',
            'numero_interno' => DocumentoTributario::generarNumeroInterno(),
            'moneda' => 'GTQ',
            'subtotal' => $total,
            'total' => $total,
            'nit_receptor' => '1234567-8',
            'nombre_receptor' => 'Cliente & Hijos',
            'estado' => 'emitido',
            'fecha_emision' => now(),
        ]);
        $doc->items()->create([
            'numero_linea' => 1, 'tipo' => 'S', 'descripcion' => 'Soporte', 'cantidad' => 1,
            'precio_unitario' => $total, 'subtotal' => $total,
        ]);

        $this->actingAs($this->admin)->post("/admin/facturas/{$doc->id}/certificar-sat")->assertRedirect();

        return $doc->fresh();
    }

    public function test_certifica_la_factura_con_nit_limpio_y_xml_escapado(): void
    {
        $doc = $this->facturaCertificada();

        $this->assertSame('certificado', $doc->estado);
        $this->assertSame('UUID-1', $doc->uuid_sat);
        $this->assertStringContainsString('Tipo="FACT"', $doc->xml_generado);
        $this->assertStringContainsString('IDReceptor="12345678"', $doc->xml_generado);
        $this->assertStringContainsString('Cliente &amp; Hijos', $doc->xml_generado);
        $this->assertStringNotContainsString('Complementos', $doc->xml_generado);
    }

    public function test_emite_nota_de_credito_con_referencia_a_la_factura(): void
    {
        $factura = $this->facturaCertificada();

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NCRE', 'monto' => '40.00', 'motivo' => 'Descuento acordado',
        ])->assertRedirect();

        $nota = DocumentoTributario::where('tipo', 'NCRE')->firstOrFail();
        $this->assertSame($factura->id, $nota->documento_origen_id);
        $this->assertSame('certificado', $nota->estado);
        $this->assertStringStartsWith('NC-', $nota->numero_interno);
        $this->assertEquals(40.00, (float) $nota->total);
        $this->assertSame('GTQ', $nota->moneda);
        $this->assertStringContainsString('Tipo="NCRE"', $nota->xml_generado);
        $this->assertStringContainsString('NumeroAutorizacionDocumentoOrigen="UUID-1"', $nota->xml_generado);
        $this->assertStringContainsString('SerieDocumentoOrigen="SERIE1"', $nota->xml_generado);
        $this->assertStringContainsString('MotivoAjuste="Descuento acordado"', $nota->xml_generado);
        $this->assertStringContainsString('<dte:Frases>', $nota->xml_generado);

        $this->assertEquals(60.00, $factura->fresh()->saldoAcreditable());
    }

    public function test_la_nota_de_credito_no_puede_superar_el_saldo(): void
    {
        $factura = $this->facturaCertificada();

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NCRE', 'monto' => '80', 'motivo' => 'Primera rebaja',
        ]);
        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NABN', 'monto' => '30', 'motivo' => 'Segunda rebaja',
        ])->assertSessionHas('error_sat');

        $this->assertSame(1, DocumentoTributario::where('documento_origen_id', $factura->id)->count());
    }

    public function test_nota_de_debito_puede_superar_el_total_y_nota_de_abono_va_sin_frases(): void
    {
        $factura = $this->facturaCertificada();

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NDEB', 'monto' => '150', 'motivo' => 'Cargo por horas extra',
        ]);
        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NABN', 'monto' => '10', 'motivo' => 'Abono a cuenta',
        ]);

        $debito = DocumentoTributario::where('tipo', 'NDEB')->firstOrFail();
        $this->assertSame('certificado', $debito->estado);
        $this->assertStringContainsString('ReferenciasNota', $debito->xml_generado);

        $abono = DocumentoTributario::where('tipo', 'NABN')->firstOrFail();
        $this->assertSame('certificado', $abono->estado);
        $this->assertStringNotContainsString('<dte:Frases>', $abono->xml_generado);
        $this->assertStringNotContainsString('Complementos', $abono->xml_generado);
        $this->assertStringNotContainsString('<dte:Impuestos>', $abono->xml_generado);
    }

    public function test_con_afiliacion_general_la_nota_desglosa_iva(): void
    {
        ConfiguracionDTE::actual()->update(['afiliacion_iva' => 'GEN', 'frases_config' => null]);
        $factura = $this->facturaCertificada(112.00);

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NCRE', 'monto' => '112', 'motivo' => 'Devolución total',
        ]);

        $nota = DocumentoTributario::where('tipo', 'NCRE')->firstOrFail();
        $this->assertStringContainsString('<dte:MontoGravable>100.00</dte:MontoGravable>', $nota->xml_generado);
        $this->assertStringContainsString('TotalMontoImpuesto="12.00"', $nota->xml_generado);
    }

    public function test_no_emite_notas_sobre_factura_sin_certificar_o_si_estan_desactivadas(): void
    {
        $factura = $this->facturaCertificada();
        ConfiguracionDTE::actual()->update(['habilitar_notas_credito' => false]);

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NCRE', 'monto' => '10', 'motivo' => 'No debería',
        ])->assertSessionHas('error_sat');

        $sinCertificar = DocumentoTributario::create([
            'tipo' => 'FACT', 'numero_interno' => 'FAC-X', 'total' => 50, 'nombre_receptor' => 'X', 'estado' => 'emitido',
        ]);
        $this->actingAs($this->admin)->post("/admin/facturas/{$sinCertificar->id}/notas", [
            'tipo' => 'NDEB', 'monto' => '10', 'motivo' => 'No debería',
        ])->assertSessionHas('error_sat');

        $this->assertSame(0, DocumentoTributario::whereNotNull('documento_origen_id')->count());
    }

    public function test_anula_la_factura_ante_sat(): void
    {
        $factura = $this->facturaCertificada();
        $fechaCertificada = null;
        preg_match('/FechaHoraEmision="([^"]+)"/', $factura->xml_generado, $m);
        $fechaCertificada = $m[1];

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/anular", [
            'motivo' => 'Error en datos del receptor',
        ])->assertRedirect()->assertSessionHas('exito');

        $factura->refresh();
        $this->assertSame('anulado', $factura->estado);
        $this->assertSame('Error en datos del receptor', $factura->motivo_anulacion);
        $this->assertNotNull($factura->fecha_anulacion);
        $this->assertStringContainsString('GTAnulacionDocumento', $factura->xml_anulacion);
        $this->assertStringContainsString('NumeroDocumentoAAnular="UUID-1"', $factura->xml_anulacion);
        $this->assertStringContainsString("FechaEmisionDocumentoAnular=\"{$fechaCertificada}\"", $factura->xml_anulacion);

        $firmaAnulacion = collect($this->enviadas)->first(fn ($r) => str_contains($r->url(), 'firma_xml') && $r['es_anulacion'] === 'S');
        $this->assertNotNull($firmaAnulacion);
        $this->assertTrue($factura->logs()->where('accion', 'anular')->where('exito', true)->exists());

        // La página pública del QR lo muestra anulado
        $factura->asegurarToken();
        $this->get(route('comprobantes.documento.verificar', $factura->token_publico))->assertSee('ANULADO');
    }

    public function test_si_infile_rechaza_la_anulacion_sigue_certificada(): void
    {
        $factura = $this->facturaCertificada();
        $this->rechazarAnulacion = true;

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/anular", [
            'motivo' => 'Prueba de rechazo',
        ])->assertSessionHas('error_sat');

        $factura->refresh();
        $this->assertSame('certificado', $factura->estado);
        $this->assertStringContainsString('Documento no se puede anular', $factura->error_mensaje);
    }

    public function test_no_anula_una_factura_con_notas_vigentes_ni_si_esta_desactivado(): void
    {
        $factura = $this->facturaCertificada();
        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NCRE', 'monto' => '10', 'motivo' => 'Rebaja parcial',
        ]);

        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/anular", ['motivo' => 'Quiero anular'])
            ->assertSessionHas('error_sat');
        $this->assertSame('certificado', $factura->fresh()->estado);

        $nota = DocumentoTributario::where('tipo', 'NCRE')->firstOrFail();
        ConfiguracionDTE::actual()->update(['permite_anulacion_directa' => false]);
        $this->actingAs($this->admin)->post("/admin/facturas/{$nota->id}/anular", ['motivo' => 'Quiero anular'])
            ->assertSessionHas('error_sat');
        $this->assertSame('certificado', $nota->fresh()->estado);
    }

    public function test_muestra_y_descarga_los_xml(): void
    {
        $factura = $this->facturaCertificada();

        $this->actingAs($this->admin)->get("/admin/facturas/{$factura->id}/xml/generado")
            ->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('GTDocumento', false);

        // El certificado llega en base64 y se muestra decodificado
        $this->actingAs($this->admin)->get("/admin/facturas/{$factura->id}/xml/certificado?descargar=1")
            ->assertOk()->assertSee('<certificado n="1"/>', false)
            ->assertHeader('Content-Disposition', 'attachment; filename="FACT-'.$factura->numero_interno.'-certificado.xml"');

        // Sin anular, no hay XML de anulación
        $this->actingAs($this->admin)->get("/admin/facturas/{$factura->id}/xml/anulacion")->assertNotFound();
        $this->actingAs($this->admin)->get("/admin/facturas/{$factura->id}/xml/otro")->assertNotFound();
    }

    public function test_las_pantallas_muestran_las_acciones_y_las_notas(): void
    {
        $factura = $this->facturaCertificada();
        $this->actingAs($this->admin)->post("/admin/facturas/{$factura->id}/notas", [
            'tipo' => 'NCRE', 'monto' => '25', 'motivo' => 'Rebaja parcial',
        ]);
        $nota = DocumentoTributario::where('tipo', 'NCRE')->firstOrFail();

        $this->actingAs($this->admin)->get("/admin/facturas/{$factura->id}")
            ->assertOk()
            ->assertSee('Anular ante SAT')
            ->assertSee('Nota de crédito (NCRE)')
            ->assertSee($nota->numero_interno)
            ->assertSee('XML certificado por SAT');

        $this->actingAs($this->admin)->get("/admin/facturas/{$nota->id}")
            ->assertOk()
            ->assertSee('Nota de crédito '.$nota->numero_interno)
            ->assertSee($factura->numero_interno);

        $this->actingAs($this->admin)->get('/admin/facturas')->assertOk()->assertSee('Nota de crédito');
        $this->actingAs($this->admin)->get("/admin/facturas/{$nota->id}/imprimir")->assertOk()->assertSee('Nota de crédito electrónica')->assertSee('UUID-2');
    }

    public function test_pequeno_contribuyente_sin_frases_configuradas_usa_la_3_1(): void
    {
        // Error SAT 2614: la 2/1 (agente de retención del IVA) no corresponde a un NIT común
        ConfiguracionDTE::actual()->update(['afiliacion_iva' => 'PEQ', 'frases_config' => null]);
        $factura = $this->facturaCertificada();

        $this->assertStringContainsString('<dte:Frases><dte:Frase CodigoEscenario="1" TipoFrase="3"/></dte:Frases>', $factura->xml_generado);
        $this->assertStringNotContainsString('TipoFrase="2"', $factura->xml_generado);
    }

    public function test_el_panel_explica_que_significa_cada_frase(): void
    {
        ConfiguracionDTE::actual()->update([
            'afiliacion_iva' => 'GEN',
            'frases_config' => [['tipo_frase' => '2', 'codigo_escenario' => '1', 'descripcion' => 'Texto equivocado']],
        ]);

        $this->actingAs($this->admin)->get('/admin/facturacion?ver=configuracion')
            ->assertOk()
            ->assertSee('Según la SAT:', false)
            ->assertSee('Agente de retención del IVA');
    }

    public function test_los_logs_muestran_los_errores_sat_legibles_y_sin_base64(): void
    {
        $doc = DocumentoTributario::create([
            'tipo' => 'FACT', 'numero_interno' => 'FAC-Z', 'total' => 50, 'nombre_receptor' => 'Z', 'estado' => 'error',
            'error_mensaje' => 'SAT / INFILE rechazó la certificación: Existen errores — Error A | Error B',
        ]);
        $base64 = base64_encode('<?xml version="1.0"?><dte:GTDocumento xmlns:dte="x"><dte:Frase TipoFrase="2"/></dte:GTDocumento>');
        \App\Models\LogDTE::create([
            'documento_id' => $doc->id, 'accion' => 'certificar', 'url' => 'https://certificador.test',
            'request_body' => json_encode(['nit_emisor' => '1', 'xml_dte' => $base64]),
            'response_body' => json_encode(['resultado' => false, 'descripcion' => 'Existen errores en la validacion del XML.',
                'descripcion_errores' => [['resultado' => false, 'fuente' => 'FEL-GUI-30', 'categoria' => '2.6', 'numeral' => '2.6.1', 'validacion' => 'No. 4',
                    'mensaje_error' => 'El código de escenario [1] para el Tipo de Frase [2] no corresponde a la afiliación del NIT del emisor. (2614)']]]),
            'codigo_http' => 200, 'exito' => false, 'error_mensaje' => 'x', 'created_at' => now(),
        ]);

        foreach (["/admin/facturas/{$doc->id}", '/admin/facturacion?ver=logs'] as $ruta) {
            $this->actingAs($this->admin)->get($ruta)->assertOk()
                ->assertSee('no corresponde a la afiliación del NIT del emisor. (2614)')
                ->assertSee('Numeral 2.6.1')
                ->assertSee('XML en base64')
                ->assertDontSee($base64)
                ->assertSee('TipoFrase=&quot;2&quot;', false);
        }

        $this->actingAs($this->admin)->get("/admin/facturas/{$doc->id}")->assertSee('Error A')->assertSee('Error B');
    }

    public function test_descarga_el_pdf_en_carta_y_ticket(): void
    {
        $factura = $this->facturaCertificada();

        foreach (['factura', 'ticket'] as $formato) {
            $res = $this->actingAs($this->admin)->get("/admin/facturas/{$factura->id}/descargar-pdf?formato={$formato}");
            $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $res->getContent());
        }
    }

    public function test_certificar_sin_configuracion_activa_avisa_sin_romper(): void
    {
        ConfiguracionDTE::actual()->update(['activo' => false]);
        $doc = DocumentoTributario::create([
            'tipo' => 'FACT', 'numero_interno' => 'FAC-Y', 'total' => 50, 'nombre_receptor' => 'Y', 'estado' => 'emitido',
        ]);

        $this->actingAs($this->admin)->post("/admin/facturas/{$doc->id}/certificar-sat")
            ->assertRedirect()->assertSessionHas('error_sat');
    }
}
