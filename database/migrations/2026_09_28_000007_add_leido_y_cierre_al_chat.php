<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chat: confirmación de lectura por mensaje (✓ enviado / ✓✓ visto) y cierre de la
 * conversación con aviso al visitante y copia por correo (pedido del usuario).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes_chat', function (Blueprint $table) {
            $table->timestamp('leido_en')->nullable()->after('cuerpo'); // cuándo lo leyó el otro lado
        });
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->timestamp('cerrada_en')->nullable()->after('estado');
        });
        // Lo ya existente se da por leído para no mostrar avisos viejos.
        DB::table('mensajes_chat')->whereNull('leido_en')->update(['leido_en' => DB::raw('created_at')]);

        $ahora = now();
        DB::table('plantillas_correo')->insert([
            'codigo' => 'copia_chat', 'nombre' => 'Copia de la conversación del chat', 'del_sistema' => true, 'is_active' => true,
            'descripcion' => 'Se envía al visitante cuando pide una copia de su conversación después de que se cerró.',
            'asunto' => 'Tu conversación con {{ sistema }}',
            'contenido' => '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2>'
                .'<p>Esta es la copia de tu conversación con nosotros del {{ fecha }}.</p>'
                .'<div style="background:#f5f7fa;border-radius:8px;padding:16px;white-space:pre-line;font-size:14px;">{{ conversacion }}</div>'
                .'<p style="margin-top:20px;">¿Necesitas algo más? Responde a este correo o vuelve a escribirnos por el chat del sitio.</p>',
            'variables' => json_encode(['nombre', 'fecha', 'conversacion', 'sistema', 'color']),
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->where('codigo', 'copia_chat')->delete();
        Schema::table('conversaciones', fn (Blueprint $table) => $table->dropColumn('cerrada_en'));
        Schema::table('mensajes_chat', fn (Blueprint $table) => $table->dropColumn('leido_en'));
    }
};
