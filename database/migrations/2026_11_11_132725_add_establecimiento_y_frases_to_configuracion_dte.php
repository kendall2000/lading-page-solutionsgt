<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_dte', function (Blueprint $table) {
            $table->string('codigo_establecimiento', 10)->default('1')->after('departamento');
            // Frases dinámicas configurables: JSON con arreglo de [{tipo_frase, codigo_escenario, texto, tipo_doc}]
            $table->json('frases_config')->nullable()->after('afiliacion_iva');
            // Opciones para documentos especiales
            $table->boolean('permite_anulacion_directa')->default(true)->after('frases_config');
            $table->boolean('habilitar_notas_credito')->default(true)->after('permite_anulacion_directa');
            $table->boolean('habilitar_notas_debito')->default(true)->after('habilitar_notas_credito');
            $table->boolean('habilitar_notas_abono')->default(true)->after('habilitar_notas_debito');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_dte', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_establecimiento',
                'frases_config',
                'permite_anulacion_directa',
                'habilitar_notas_credito',
                'habilitar_notas_debito',
                'habilitar_notas_abono',
            ]);
        });
    }
};
