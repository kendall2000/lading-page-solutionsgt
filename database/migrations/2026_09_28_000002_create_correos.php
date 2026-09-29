<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correos (igual que el módulo del restaurante): servidor SMTP en la base
 * (contraseña cifrada), plantillas editables y bitácora. Nada en el .env.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_correo', function (Blueprint $table) {
            $table->id();
            $table->string('host', 150)->nullable();
            $table->unsignedSmallInteger('puerto')->nullable();
            $table->string('usuario', 200)->nullable();
            $table->text('clave')->nullable()->comment('Cifrada con APP_KEY; nunca se devuelve a la pantalla');
            $table->string('cifrado', 10)->nullable()->comment('tls, ssl o vacío');
            $table->string('remitente_correo', 150)->nullable();
            $table->string('remitente_nombre', 120)->nullable();
            $table->string('responder_a', 150)->nullable();
            $table->string('avisos_a', 500)->nullable()->comment('Reciben el aviso de cada mensaje nuevo, separados por coma');
            $table->boolean('is_active')->default(false);
            $table->dateTime('probado_en')->nullable();
            $table->timestamps();
        });
        DB::table('configuracion_correo')->insert(['created_at' => now(), 'updated_at' => now()]);

        Schema::create('plantillas_correo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->string('asunto', 200);
            $table->longText('contenido');
            $table->json('variables')->nullable();
            $table->boolean('del_sistema')->default(false)->comment('La usa el sitio: no se borra ni cambia su código');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $ahora = now();
        $plantillas = [
            [
                'codigo' => 'nuevo_mensaje', 'nombre' => 'Aviso de mensaje nuevo',
                'descripcion' => 'Te llega a los correos de «Avisos a» cada vez que alguien escribe en el formulario de contacto.',
                'asunto' => 'Nuevo mensaje de {{ nombre }} en {{ sistema }}',
                'contenido' => '<h2 style="margin:0 0 12px;">Nuevo mensaje desde tu sitio</h2>'
                    .'<table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:16px;">'
                    .'<tr><td style="padding:4px 0;color:#8a94ad;width:120px;">Nombre</td><td>{{ nombre }}</td></tr>'
                    .'<tr><td style="padding:4px 0;color:#8a94ad;">Empresa</td><td>{{ empresa }}</td></tr>'
                    .'<tr><td style="padding:4px 0;color:#8a94ad;">Correo</td><td>{{ correo }}</td></tr>'
                    .'<tr><td style="padding:4px 0;color:#8a94ad;">Teléfono</td><td>{{ telefono }}</td></tr>'
                    .'<tr><td style="padding:4px 0;color:#8a94ad;">Sistema</td><td>{{ sistema_interes }}</td></tr></table>'
                    .'<div style="background:#f5f7fa;border-radius:8px;padding:16px;white-space:pre-line;">{{ mensaje }}</div>'
                    .'<p style="text-align:center;margin:28px 0 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Abrir en el panel</a></p>',
                'variables' => ['nombre', 'empresa', 'correo', 'telefono', 'sistema_interes', 'mensaje', 'enlace', 'sistema', 'color'],
            ],
            [
                'codigo' => 'confirmacion_contacto', 'nombre' => 'Gracias por escribir',
                'descripcion' => 'Respuesta automática a quien escribe en el formulario de contacto.',
                'asunto' => 'Recibimos tu mensaje · {{ sistema }}',
                'contenido' => '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2>'
                    .'<p>¡Gracias por escribir! Recibí tu mensaje y te responderé lo antes posible.</p>'
                    .'<p>Mientras tanto, puedes conocer más de mis sistemas en el sitio:</p>'
                    .'<p style="text-align:center;margin:28px 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Ver sistemas</a></p>'
                    .'<p style="color:#8a94ad;font-size:13px;">Tu mensaje: {{ mensaje }}</p>',
                'variables' => ['nombre', 'mensaje', 'enlace', 'sistema', 'color'],
            ],
            [
                'codigo' => 'recuperar_contrasena', 'nombre' => 'Recuperar contraseña',
                'descripcion' => 'Se envía al pedir «¿Olvidaste tu contraseña?» en el acceso al panel.',
                'asunto' => 'Restablece tu contraseña de {{ sistema }}',
                'contenido' => '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2><p>Recibimos una solicitud para restablecer tu contraseña del panel.</p>'
                    .'<p style="text-align:center;margin:28px 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a></p>'
                    .'<p>El enlace vence en {{ minutos }} minutos. Si no lo pediste, ignora este correo: tu contraseña no cambia.</p>',
                'variables' => ['nombre', 'enlace', 'minutos', 'sistema', 'color'],
            ],
        ];
        foreach ($plantillas as $p) {
            DB::table('plantillas_correo')->insert(['variables' => json_encode($p['variables'])] + $p + [
                'del_sistema' => true, 'is_active' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        Schema::create('bitacora_correos', function (Blueprint $table) {
            $table->id();
            $table->string('plantilla', 60)->nullable();
            $table->string('destinatario', 200);
            $table->string('asunto', 250)->nullable();
            $table->string('estado', 15)->comment('enviado, fallido, desactivado, sin_plantilla');
            $table->text('error')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_correos');
        Schema::dropIfExists('plantillas_correo');
        Schema::dropIfExists('configuracion_correo');
    }
};
