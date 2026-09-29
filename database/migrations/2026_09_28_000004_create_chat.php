<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chat en vivo visitante ↔ panel (pedido del usuario): una conversación por
 * visitante (identificado por una cookie con token; en la base solo va su hash)
 * y el historial de mensajes. El tiempo real va por Laravel Reverb.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique(); // sha256 del token de la cookie del visitante
            $table->string('nombre', 120);
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('pagina', 255)->nullable(); // dónde empezó el chat
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('estado', 15)->default('abierta'); // abierta, cerrada
            $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('no_leidos_admin')->default(0);
            $table->unsignedInteger('no_leidos_visitante')->default(0);
            $table->timestamp('ultimo_mensaje_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'ultimo_mensaje_en']);
        });

        Schema::create('mensajes_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->string('autor', 10); // visitante, admin
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cuerpo');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['conversacion_id', 'id']);
        });

        // Configuración del widget (se edita en Datos del sitio).
        Schema::table('configuracion_sitio', function (Blueprint $table) {
            $table->boolean('chat_activo')->default(true);
            $table->string('chat_titulo', 80)->nullable();
            $table->string('chat_bienvenida', 300)->nullable();
        });

        $ahora = now();
        DB::table('plantillas_correo')->insert([
            'codigo' => 'nuevo_chat', 'nombre' => 'Aviso de chat nuevo', 'del_sistema' => true, 'is_active' => true,
            'descripcion' => 'Te llega a «Avisos a» cuando un visitante empieza una conversación en el chat del sitio.',
            'asunto' => 'Chat nuevo de {{ nombre }} · {{ sistema }}',
            'contenido' => '<h2 style="margin:0 0 12px;">{{ nombre }} te escribió por el chat</h2>'
                .'<p style="color:#8a94ad;font-size:14px;">{{ correo }} · {{ pagina }}</p>'
                .'<div style="background:#f5f7fa;border-radius:8px;padding:16px;white-space:pre-line;">{{ mensaje }}</div>'
                .'<p style="text-align:center;margin:28px 0 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Responder en el panel</a></p>',
            'variables' => json_encode(['nombre', 'correo', 'pagina', 'mensaje', 'enlace', 'sistema', 'color']),
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->where('codigo', 'nuevo_chat')->delete();
        Schema::table('configuracion_sitio', function (Blueprint $table) {
            $table->dropColumn(['chat_activo', 'chat_titulo', 'chat_bienvenida']);
        });
        Schema::dropIfExists('mensajes_chat');
        Schema::dropIfExists('conversaciones');
    }
};
