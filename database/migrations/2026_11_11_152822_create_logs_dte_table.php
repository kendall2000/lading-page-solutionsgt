<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_dte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->nullable()->constrained('documentos_tributarios')->nullOnDelete();
            $table->string('accion', 30)->default('certificar'); // firmar, certificar, anular, consultar
            $table->string('url', 500)->nullable();
            $table->longText('request_body')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedSmallInteger('codigo_http')->nullable();
            $table->boolean('exito')->default(false);
            $table->text('error_mensaje')->nullable();
            $table->unsignedInteger('duracion_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('documento_id');
            $table->index('exito');
            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_dte');
    }
};
