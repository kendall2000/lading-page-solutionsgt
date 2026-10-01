<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cobros con PayPal (una fila). Las credenciales viven aquí, no en el .env; el secreto va cifrado con APP_KEY.
        Schema::create('configuracion_pagos', function (Blueprint $table) {
            $table->id();
            $table->boolean('activo')->default(false);
            $table->string('modo', 10)->default('sandbox'); // sandbox (pruebas) | live (cobros reales)
            $table->string('client_id', 200)->nullable();
            $table->text('client_secret')->nullable();
            $table->string('webhook_id', 60)->nullable();
            $table->char('moneda', 3)->default('USD');
            $table->unsignedTinyInteger('dias_gracia')->default(3); // días extra del contrato tras la fecha de cobro
            $table->string('texto_asesor', 300)->nullable(); // mensaje del botón «de por vida»
            $table->timestamp('probado_en')->nullable();
            $table->timestamps();
        });

        // Lo que se vende: un sistema (suscripción al software), un servicio u otra cosa.
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('slug', 140)->unique();
            $table->string('tipo', 15)->default('servicio'); // sistema | servicio | otro
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->nullOnDelete();
            $table->string('descripcion', 300)->nullable();
            $table->text('incluye')->nullable();
            $table->boolean('destacado')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            // Producto creado en PayPal (y en qué modo: el de pruebas no sirve en el real).
            $table->string('paypal_id', 60)->nullable();
            $table->string('paypal_modo', 10)->nullable();
            $table->timestamps();
        });

        // Un precio por periodo de cada producto. «de_por_vida» no se cobra en línea: lleva a un asesor.
        Schema::create('precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('periodo', 15); // unico | semanal | mensual | trimestral | anual | de_por_vida
            $table->decimal('monto', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            // Plan de PayPal con este monto; al cambiar el monto se crea otro (los suscriptores conservan su precio).
            $table->string('paypal_plan_id', 60)->nullable();
            $table->string('paypal_modo', 10)->nullable();
            $table->timestamps();
            $table->unique(['producto_id', 'periodo']);
        });

        // Suscripciones de PayPal. Nombre, monto y periodo se copian: el historial no cambia si cambias el producto.
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('precio_id')->nullable()->constrained('precios')->nullOnDelete();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->string('paypal_id', 60)->unique();
            $table->string('paypal_modo', 10);
            $table->string('estado', 15)->default('pendiente'); // pendiente | activa | suspendida | cancelada | vencida
            $table->string('descripcion', 200);
            $table->string('periodo', 15);
            $table->decimal('monto', 10, 2);
            $table->char('moneda', 3);
            $table->string('correo', 150);
            $table->timestamp('activada_en')->nullable();
            $table->timestamp('siguiente_cobro')->nullable();
            $table->timestamp('cancelada_en')->nullable();
            $table->timestamp('sincronizada_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'siguiente_cobro']);
        });

        // Cada cobro: pago único (orden de PayPal) o cuota de una suscripción.
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('precio_id')->nullable()->constrained('precios')->nullOnDelete();
            $table->foreignId('suscripcion_id')->nullable()->constrained('suscripciones')->nullOnDelete();
            $table->string('paypal_orden_id', 60)->nullable()->index();
            $table->string('paypal_id', 60)->nullable()->unique(); // captura (pago único) o transacción (suscripción)
            $table->string('paypal_modo', 10);
            $table->string('estado', 15)->default('pendiente'); // pendiente | completado | reembolsado | fallido
            $table->string('descripcion', 200);
            $table->string('periodo', 15);
            $table->decimal('monto', 10, 2);
            $table->char('moneda', 3);
            $table->string('correo', 150);
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'pagado_en']);
        });

        $ahora = now();
        $boton = fn (string $texto) => '<p style="text-align:center;margin:28px 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">'.$texto.'</a></p>';
        $plantillas = [
            ['pago_recibido', 'Comprobante de pago', 'Se envía al cliente cada vez que PayPal confirma un cobro (compra o cuota de suscripción).',
                'Recibimos tu pago · {{ sistema }}',
                '<h2 style="margin:0 0 12px;">¡Gracias, {{ nombre }}!</h2><p>Recibimos tu pago de <strong>{{ monto }}</strong> por <strong>{{ producto }}</strong> ({{ periodo }}).</p>'
                .'<p style="color:#8a94ad;font-size:13px;">Fecha: {{ fecha }} · Referencia de PayPal: {{ referencia }}</p>'
                .$boton('Ver mis pagos').'<p style="color:#8a94ad;font-size:13px;">Las suscripciones se cancelan cuando quieras desde «Mi cuenta» → Pagos.</p>',
                ['nombre', 'producto', 'periodo', 'monto', 'fecha', 'referencia', 'enlace', 'sistema', 'color']],
            ['nueva_venta', 'Aviso de venta nueva', 'Te llega a «Avisos a» con cada compra o suscripción nueva, para que le prepares el acceso al cliente.',
                'Venta nueva: {{ producto }} · {{ sistema }}',
                '<h2 style="margin:0 0 12px;">{{ nombre }} compró {{ producto }}</h2><p><strong>{{ monto }}</strong> · {{ periodo }}</p>'
                .'<p style="color:#8a94ad;font-size:14px;">{{ correo }}</p><p>Si es un sistema, créale su usuario y pon el enlace de acceso en su contrato.</p>'.$boton('Ver en el panel'),
                ['nombre', 'correo', 'producto', 'periodo', 'monto', 'enlace', 'sistema', 'color']],
            ['suscripcion_cancelada', 'Aviso de suscripción cancelada', 'Te llega a «Avisos a» cuando una suscripción se cancela, se suspende por falta de pago o vence.',
                'Suscripción {{ estado }}: {{ producto }} · {{ sistema }}',
                '<h2 style="margin:0 0 12px;">La suscripción de {{ nombre }} quedó {{ estado }}</h2><p>{{ producto }} · {{ periodo }} · {{ monto }}</p>'
                .'<p style="color:#8a94ad;font-size:14px;">{{ correo }} · Su acceso sigue hasta el {{ hasta }}.</p>'.$boton('Ver en el panel'),
                ['nombre', 'correo', 'producto', 'periodo', 'monto', 'estado', 'hasta', 'enlace', 'sistema', 'color']],
        ];
        foreach ($plantillas as [$codigo, $nombre, $descripcion, $asunto, $contenido, $variables]) {
            if (DB::table('plantillas_correo')->where('codigo', $codigo)->exists()) {
                continue;
            }
            DB::table('plantillas_correo')->insert([
                'codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion, 'asunto' => $asunto, 'contenido' => $contenido,
                'variables' => json_encode($variables), 'del_sistema' => true, 'is_active' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->whereIn('codigo', ['pago_recibido', 'nueva_venta', 'suscripcion_cancelada'])->delete();
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('suscripciones');
        Schema::dropIfExists('precios');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('configuracion_pagos');
    }
};
