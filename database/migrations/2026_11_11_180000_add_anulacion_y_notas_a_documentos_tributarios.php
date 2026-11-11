<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anulación ante SAT y documentos complementarios (NCRE, NDEB, NABN).
 * Una nota es otro documento tributario que apunta a la factura de origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_tributarios', function (Blueprint $table) {
            $table->foreignId('documento_origen_id')->nullable()->after('cuenta_id')
                ->constrained('documentos_tributarios')->nullOnDelete();
            $table->timestamp('fecha_anulacion')->nullable()->after('motivo_anulacion');
            $table->longText('xml_anulacion')->nullable()->after('xml_certificado');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_tributarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documento_origen_id');
            $table->dropColumn(['fecha_anulacion', 'xml_anulacion']);
        });
    }
};
