<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Configuración del sistema (como el módulo del restaurante): color secundario, inicio de sesión y pie. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_sitio', function (Blueprint $table) {
            $table->string('color_secundario', 7)->nullable()->after('color_primario');
            $table->string('login_titulo', 100)->nullable();
            $table->string('login_subtitulo', 200)->nullable();
            $table->string('fondo_login')->nullable();
            $table->string('pie_texto', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_sitio', function (Blueprint $table) {
            $table->dropColumn(['color_secundario', 'login_titulo', 'login_subtitulo', 'fondo_login', 'pie_texto']);
        });
    }
};
