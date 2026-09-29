<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('plantillas_correo')->where('codigo', 'respaldo_fallido')->exists()) {
            return;
        }
        $ahora = now();
        DB::table('plantillas_correo')->insert([
            'codigo' => 'respaldo_fallido', 'nombre' => 'Aviso de respaldo fallido', 'del_sistema' => true, 'is_active' => true,
            'descripcion' => 'Te llega a «Avisos a» si el respaldo diario de la base (3:00 a. m.) no se pudo crear.',
            'asunto' => 'No se pudo respaldar la base · {{ sistema }}',
            'contenido' => '<h2 style="margin:0 0 12px;">El respaldo del {{ fecha }} falló</h2>'
                .'<p>El respaldo automático de la base no se guardó en Contabo. Los anteriores siguen disponibles.</p>'
                .'<div style="background:#f5f7fa;border-radius:8px;padding:16px;white-space:pre-line;font-family:monospace;font-size:13px;">{{ error }}</div>'
                .'<p style="color:#8a94ad;font-size:14px;margin-top:16px;">Para probarlo a mano en el servidor: <code>php artisan respaldo:crear</code></p>',
            'variables' => json_encode(['error', 'fecha', 'sistema', 'color']),
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->where('codigo', 'respaldo_fallido')->delete();
    }
};
