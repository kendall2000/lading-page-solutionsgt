<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Licencias de las instalaciones de Nexus ERP en renta (Panel → Licencias Nexus):
 *  - configuracion_licencias: par de claves Ed25519 del panel y valores por defecto (una fila);
 *  - instalaciones: cada cliente con su vencimiento, etapas, conexión y monitoreo;
 *  - pagos_licencia: pagos registrados a mano (transferencia, depósito, efectivo…).
 * Más las plantillas de correo de avisos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_licencias', function (Blueprint $t) {
            $t->id();
            $t->text('clave_privada')->nullable(); // cifrada con APP_KEY
            $t->string('clave_publica', 100)->nullable();
            $t->string('contacto', 255)->nullable();
            $t->string('enlace_pago', 500)->nullable();
            $t->unsignedSmallInteger('dias_aviso')->default(7);
            $t->unsignedSmallInteger('dias_gracia')->default(7);
            $t->unsignedSmallInteger('dias_restringido')->default(8);
            $t->timestamps();
        });

        Schema::create('instalaciones', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 150);
            $t->string('identificador', 40)->unique();
            $t->string('url', 255);
            $t->string('tipo', 10)->default('renta'); // renta | venta
            $t->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $t->string('correo_cobro', 150)->nullable();
            $t->string('telefono', 30)->nullable();
            $t->decimal('monto', 10, 2)->nullable();
            $t->string('moneda', 3)->default('GTQ');
            $t->unsignedTinyInteger('meses_periodo')->default(1);
            $t->date('vence_el')->nullable();
            $t->date('prorroga_hasta')->nullable();
            $t->string('modo', 20)->default('NORMAL'); // NORMAL | FORZAR_ACTIVO | SUSPENDIDO
            $t->unsignedSmallInteger('dias_aviso')->nullable();
            $t->unsignedSmallInteger('dias_gracia')->nullable();
            $t->unsignedSmallInteger('dias_restringido')->nullable();
            $t->boolean('bloquear_facturacion')->default(false);
            $t->string('contacto', 255)->nullable();
            $t->string('enlace_pago', 500)->nullable();
            $t->text('notas')->nullable();
            $t->char('token_hash', 64)->nullable()->unique();
            // Lo que informa la instalación en cada consulta (monitoreo).
            $t->dateTime('ultima_consulta_en')->nullable();
            $t->string('ip_consulta', 45)->nullable();
            $t->string('version', 40)->nullable();
            $t->unsignedInteger('usuarios_activos')->nullable();
            $t->unsignedInteger('empresas_activas')->nullable();
            $t->string('etapa_reportada', 20)->nullable();
            // Último aviso «consulta ya» y avisos por correo ya enviados.
            $t->dateTime('avisado_en')->nullable();
            $t->string('aviso_resultado', 255)->nullable();
            $t->string('correo_etapa', 20)->nullable();
            $t->date('correo_vence_el')->nullable();
            $t->dateTime('sin_comunicacion_avisada_en')->nullable();
            $t->timestamps();
        });

        Schema::create('pagos_licencia', function (Blueprint $t) {
            $t->id();
            $t->foreignId('instalacion_id')->constrained('instalaciones')->cascadeOnDelete();
            $t->date('pagado_el');
            $t->decimal('monto', 10, 2);
            $t->string('moneda', 3)->default('GTQ');
            $t->string('metodo', 20);
            $t->string('referencia', 120)->nullable();
            $t->unsignedTinyInteger('meses');
            $t->date('vence_antes')->nullable();
            $t->date('vence_despues');
            $t->string('notas', 500)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('anulado_en')->nullable();
            $t->timestamps();
        });

        $ahora = now();
        $plantillas = [
            [
                'codigo' => 'licencia_aviso', 'nombre' => 'Licencia: aviso al cliente',
                'descripcion' => 'Le llega al correo de cobro de una instalación de Nexus cuando está por vencer o cambia de etapa de mora.',
                'asunto' => '{{ titulo }} · {{ cliente }}',
                'contenido' => '<h2 style="margin:0 0 12px;">{{ titulo }}</h2>'
                    .'<p>Hola, equipo de <strong>{{ cliente }}</strong>:</p>'
                    .'<p>{{ texto }}</p>'
                    .'<div style="background:#f5f7fa;border-radius:8px;padding:16px;margin:16px 0;">'
                    .'<div><strong>Pagado hasta:</strong> {{ vence }}</div><div><strong>Siguiente etapa:</strong> {{ siguiente }}</div></div>'
                    .'<p><strong>Para pagar o aclarar:</strong> {{ contacto }}</p>'
                    .'<p>{{ enlace }}</p>'
                    .'<p style="color:#8a94ad;font-size:14px;margin-top:16px;">Tu información está segura: nunca se borra ni se modifica por un pago pendiente.</p>',
                'variables' => ['cliente', 'titulo', 'texto', 'vence', 'siguiente', 'contacto', 'enlace', 'sistema', 'color'],
            ],
            [
                'codigo' => 'licencia_estado', 'nombre' => 'Licencia: aviso para ti',
                'descripcion' => 'Te llega a «Avisos a» cuando una instalación de Nexus entra en mora, cambia de etapa o deja de comunicarse.',
                'asunto' => '{{ cliente }}: {{ titulo }}',
                'contenido' => '<h2 style="margin:0 0 12px;">{{ cliente }}: {{ titulo }}</h2>'
                    .'<p>{{ texto }}</p>'
                    .'<p><a href="{{ enlace }}">Abrir la licencia en el panel</a></p>',
                'variables' => ['cliente', 'titulo', 'texto', 'enlace', 'sistema', 'color'],
            ],
        ];
        foreach ($plantillas as $p) {
            if (! DB::table('plantillas_correo')->where('codigo', $p['codigo'])->exists()) {
                DB::table('plantillas_correo')->insert(array_merge($p, [
                    'del_sistema' => true, 'is_active' => true, 'variables' => json_encode($p['variables']),
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->whereIn('codigo', ['licencia_aviso', 'licencia_estado'])->delete();
        Schema::dropIfExists('pagos_licencia');
        Schema::dropIfExists('instalaciones');
        Schema::dropIfExists('configuracion_licencias');
    }
};
