<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contador de visitas propio, sin cookies: el visitante es un hash del día (IP + navegador + fecha + APP_KEY),
        // así se cuentan visitantes únicos por día sin guardar la IP ni poder seguir a nadie entre días.
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->char('visitante', 64);
            $table->string('ruta', 255);
            $table->string('tipo', 15); // pagina, sistema, manual
            $table->string('titulo', 150)->nullable();
            $table->string('origen', 30); // interno, directo, google, facebook… u «otro»
            $table->string('origen_sitio', 100)->nullable(); // dominio de donde llegó (si es otro sitio)
            $table->string('dispositivo', 10); // movil, tableta, escritorio
            $table->string('navegador', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at', 'visitante']);
            $table->index(['ruta', 'created_at']);
        });

        // Quién hizo qué en el panel (y entradas al panel).
        Schema::create('bitacora_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario', 150)->nullable(); // nombre o correo al momento (queda aunque se borre el usuario)
            $table->string('accion', 15); // crear, editar, borrar, entrar, salir, fallido
            $table->string('modulo', 40);
            $table->string('modelo_tipo', 80)->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->string('descripcion', 255);
            $table->json('cambios')->nullable(); // {campo: [antes, después]}
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at']);
            $table->index(['modulo', 'created_at']);
            $table->index(['accion', 'created_at']);
            $table->index(['modelo_tipo', 'modelo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_cambios');
        Schema::dropIfExists('visitas');
    }
};
