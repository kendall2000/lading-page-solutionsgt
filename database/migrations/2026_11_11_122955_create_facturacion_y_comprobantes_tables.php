<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos de facturación / comprobantes en la tabla de pagos
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('numero_comprobante', 50)->nullable()->unique()->after('paypal_id');
            $table->string('token_publico', 64)->nullable()->unique()->after('numero_comprobante');
            $table->string('receptor_nombre', 200)->nullable()->after('correo');
            $table->string('receptor_documento', 50)->nullable()->after('receptor_nombre'); // NIT, Tax ID o CF
            $table->string('receptor_direccion', 255)->nullable()->after('receptor_documento');
            $table->string('receptor_pais', 100)->nullable()->after('receptor_direccion');
            $table->text('notas_factura')->nullable()->after('receptor_pais');
        });

        // Tabla de documentos tributarios / facturas electrónicas (lista para desacoplamiento o SAT futuro)
        Schema::create('documentos_tributarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->nullOnDelete();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->string('tipo', 10)->default('FACT'); // FACT, INVOICE, RECIBO
            $table->string('numero_interno', 50)->index();
            $table->string('moneda', 5)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('tipo_cambio', 10, 4)->default(1.0000);
            
            // Datos del Receptor
            $table->string('nit_receptor', 50)->default('CF');
            $table->string('nombre_receptor', 200);
            $table->string('correo_receptor', 150)->nullable();
            $table->string('direccion_receptor', 255)->nullable();

            // Datos de certificación SAT (opcionales)
            $table->string('uuid_sat', 100)->nullable()->index();
            $table->string('serie_sat', 50)->nullable();
            $table->string('numero_sat', 50)->nullable();
            $table->timestamp('fecha_emision')->useCurrent();
            $table->timestamp('fecha_certificacion')->nullable();
            $table->string('estado', 20)->default('emitido'); // emitido, certificado, anulado, error
            $table->text('motivo_anulacion')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->longText('xml_generado')->nullable();
            $table->longText('xml_firmado')->nullable();
            $table->longText('xml_certificado')->nullable();
            $table->timestamps();
        });

        // Configuración DTE / FEL para cuando se requiera activar INFILE
        Schema::create('configuracion_dte', function (Blueprint $table) {
            $table->id();
            $table->boolean('activo')->default(false);
            $table->string('ambiente', 15)->default('pruebas'); // pruebas | produccion
            $table->string('nit_emisor', 25)->nullable();
            $table->string('nombre_emisor', 200)->nullable();
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('correo_emisor', 150)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('codigo_postal', 15)->default('01001');
            $table->string('municipio', 100)->default('GUATEMALA');
            $table->string('departamento', 100)->default('GUATEMALA');
            $table->string('pais', 5)->default('GT');
            $table->string('afiliacion_iva', 10)->default('GEN'); // GEN | PEQ | EXE
            $table->string('alias_ws', 100)->nullable();
            $table->string('token_signer', 255)->nullable();
            $table->string('llave_ws', 255)->nullable();
            $table->string('url_firma', 255)->default('https://signer-emisores.feel.com.gt/sign_solicitud_firmas/firma_xml');
            $table->string('url_certificacion', 255)->default('https://certificador.feel.com.gt/fel/certificacion/v2/dte/');
            $table->string('url_anulacion', 255)->default('https://certificador.feel.com.gt/fel/anulacion/v2/dte/');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_dte');
        Schema::dropIfExists('documentos_tributarios');
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn([
                'numero_comprobante',
                'token_publico',
                'receptor_nombre',
                'receptor_documento',
                'receptor_direccion',
                'receptor_pais',
                'notas_factura',
            ]);
        });
    }
};
