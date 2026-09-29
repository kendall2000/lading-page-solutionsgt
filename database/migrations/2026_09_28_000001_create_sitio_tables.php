<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datos generales del sitio público (una sola fila).
        Schema::create('configuracion_sitio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->default('Solutions GT');
            $table->string('eslogan', 150)->nullable();
            $table->string('propietario', 120)->nullable();
            $table->string('cargo', 120)->nullable();
            $table->string('hero_titulo', 150)->nullable();
            $table->string('hero_resaltado', 60)->nullable();
            $table->text('hero_texto')->nullable();
            $table->string('sobre_titulo', 150)->nullable();
            $table->text('sobre_texto')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('horario', 150)->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('youtube')->nullable();
            $table->string('github')->nullable();
            $table->unsignedSmallInteger('anios_experiencia')->default(0);
            $table->unsignedSmallInteger('proyectos_entregados')->default(0);
            $table->string('color_primario', 7)->nullable();
            $table->string('meta_descripcion', 300)->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_oscuro')->nullable();
            $table->string('favicon')->nullable();
            $table->string('foto')->nullable();
            $table->string('imagen_hero')->nullable();
            $table->string('imagen_hero_oscura')->nullable();
            $table->timestamps();
        });

        Schema::create('sistemas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('slug', 140)->unique();
            $table->string('resumen', 300);
            $table->text('descripcion')->nullable();
            $table->string('icono', 60)->default('fa-solid fa-laptop-code');
            $table->text('caracteristicas')->nullable(); // una por línea
            $table->string('tecnologias', 300)->nullable(); // separadas por coma
            $table->string('url_demo')->nullable();
            $table->string('imagen')->nullable();
            $table->string('imagen_oscura')->nullable();
            $table->boolean('destacado')->default(false);
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('visitas')->default(0);
            $table->timestamps();
        });

        // Capturas de pantalla de cada sistema (galería).
        Schema::create('sistema_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')->constrained('sistemas')->cascadeOnDelete();
            $table->string('ruta');
            $table->string('titulo', 150)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('precio', 60)->nullable(); // texto libre: «Desde Q1,500», «A convenir»
            $table->string('periodo', 40)->nullable(); // «/ mes», «pago único»
            $table->string('descripcion', 300)->nullable();
            $table->text('incluye')->nullable(); // una por línea
            $table->boolean('destacado')->default(false);
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 150);
            $table->string('contacto', 120)->nullable();
            $table->string('cargo', 120)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('logo')->nullable();
            $table->string('foto')->nullable();
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->nullOnDelete();
            $table->text('testimonio')->nullable();
            $table->unsignedTinyInteger('calificacion')->default(5);
            $table->boolean('mostrar_logo')->default(true);
            $table->boolean('mostrar_testimonio')->default(false);
            $table->text('notas')->nullable(); // internas, no se publican
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('direccion', 300);
            $table->string('ciudad', 120)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('horario', 150)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->boolean('principal')->default(false);
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        // Lo que llega por el formulario de contacto.
        Schema::create('mensajes_contacto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('empresa', 150)->nullable();
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->nullOnDelete();
            $table->text('mensaje');
            $table->string('estado', 20)->default('nuevo'); // nuevo, atendido, cliente, descartado
            $table->text('notas')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_contacto');
        Schema::dropIfExists('direcciones');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('sistema_imagenes');
        Schema::dropIfExists('sistemas');
        Schema::dropIfExists('configuracion_sitio');
    }
};
