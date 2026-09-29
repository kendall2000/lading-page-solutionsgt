<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sitio armado por páginas y secciones (pedido del usuario): menú dinámico
 * (Inicio, Nosotros, Servicios, Software, Manuales, Contáctenos…) donde cada
 * página lleva los bloques que se quieran. Además: categorías y modalidad de
 * los sistemas (gratis / premium / a la medida, demo, prueba por días),
 * manuales y solicitudes de demo o prueba con envío de credenciales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')->nullable()->constrained('paginas')->nullOnDelete();
            $table->string('titulo', 120);
            $table->string('titulo_menu', 60)->nullable();
            $table->string('slug', 140)->unique();
            $table->string('subtitulo', 300)->nullable();
            $table->string('imagen_encabezado')->nullable();
            $table->string('meta_descripcion', 300)->nullable();
            $table->boolean('es_inicio')->default(false);
            $table->boolean('en_menu')->default(true);
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagina_id')->constrained('paginas')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('etiqueta', 80)->nullable(); // texto pequeño sobre el título
            $table->string('titulo', 200)->nullable();
            $table->text('contenido')->nullable(); // Markdown
            $table->string('imagen')->nullable();
            $table->string('imagen_oscura')->nullable();
            $table->string('boton_texto', 60)->nullable();
            $table->string('boton_enlace')->nullable();
            $table->string('boton2_texto', 60)->nullable();
            $table->string('boton2_enlace')->nullable();
            $table->string('fondo', 20)->default('claro'); // claro, suave, oscuro
            $table->json('opciones')->nullable();
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        // Elementos repetibles de una sección: fotos del carrusel, tarjetas, tecnologías, cifras, preguntas…
        Schema::create('elementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->string('grupo', 80)->nullable();
            $table->string('titulo', 200)->nullable();
            $table->string('subtitulo', 200)->nullable();
            $table->text('texto')->nullable();
            $table->string('icono', 60)->nullable();
            $table->string('imagen')->nullable();
            $table->string('enlace')->nullable();
            $table->string('enlace_texto', 60)->nullable();
            $table->string('valor', 30)->nullable();
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('categorias_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('slug', 100)->unique();
            $table->string('icono', 60)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::table('sistemas', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('slug')->constrained('categorias_sistema')->nullOnDelete();
            $table->string('modalidad', 20)->default('premium')->after('categoria_id'); // gratis, premium, a_medida
            $table->string('precio', 60)->nullable()->after('modalidad');
            $table->boolean('acepta_demo')->default(true)->after('precio');
            $table->boolean('acepta_prueba')->default(false)->after('acepta_demo');
            $table->unsignedSmallInteger('dias_prueba')->default(15)->after('acepta_prueba');
        });

        Schema::create('manuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->nullOnDelete();
            $table->string('titulo', 150);
            $table->string('slug', 170)->unique();
            $table->string('resumen', 300)->nullable();
            $table->longText('contenido')->nullable(); // Markdown
            $table->string('archivo')->nullable(); // PDF en Contabo
            $table->string('video_url')->nullable();
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('visitas')->default(0);
            $table->timestamps();
        });

        // Solicitudes: el mismo formulario sirve para contacto, demostración o prueba por días.
        Schema::table('mensajes_contacto', function (Blueprint $table) {
            $table->string('tipo', 20)->default('contacto')->after('id'); // contacto, demo, prueba
            $table->string('url_acceso')->nullable()->after('notas');
            $table->string('usuario_prueba', 150)->nullable()->after('url_acceso');
            $table->text('clave_prueba')->nullable()->after('usuario_prueba'); // cifrada
            $table->date('vence_el')->nullable()->after('clave_prueba');
            $table->dateTime('credenciales_enviadas_en')->nullable()->after('vence_el');
            $table->index(['tipo', 'estado']);
        });

        // La portada y «Sobre mí» ahora son secciones de páginas: estas columnas sobran.
        Schema::table('configuracion_sitio', function (Blueprint $table) {
            $table->dropColumn([
                'propietario', 'cargo', 'hero_titulo', 'hero_resaltado', 'hero_texto', 'sobre_titulo', 'sobre_texto',
                'anios_experiencia', 'proyectos_entregados', 'foto', 'imagen_hero', 'imagen_hero_oscura',
            ]);
        });

        $ahora = now();
        DB::table('plantillas_correo')->insert([
            'codigo' => 'credenciales_prueba', 'nombre' => 'Credenciales de prueba', 'del_sistema' => true, 'is_active' => true,
            'descripcion' => 'Se envía al visitante cuando le das acceso de prueba a un sistema desde Mensajes.',
            'asunto' => 'Tu acceso de prueba a {{ sistema_nombre }}',
            'contenido' => '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2>'
                .'<p>¡Tu prueba de <strong>{{ sistema_nombre }}</strong> está lista! Puedes usarla durante {{ dias }} días (hasta el {{ vence }}).</p>'
                .'<table style="width:100%;border-collapse:collapse;font-size:14px;margin:16px 0;background:#f5f7fa;border-radius:8px;">'
                .'<tr><td style="padding:10px 16px;color:#8a94ad;width:120px;">Dirección</td><td style="padding:10px 16px;">{{ url_acceso }}</td></tr>'
                .'<tr><td style="padding:10px 16px;color:#8a94ad;">Usuario</td><td style="padding:10px 16px;font-family:monospace;">{{ usuario }}</td></tr>'
                .'<tr><td style="padding:10px 16px;color:#8a94ad;">Contraseña</td><td style="padding:10px 16px;font-family:monospace;">{{ clave }}</td></tr></table>'
                .'<p style="text-align:center;margin:28px 0;"><a href="{{ url_acceso }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Entrar al sistema</a></p>'
                .'<p>Si tienes dudas durante la prueba, responde a este correo y con gusto te ayudo.</p>',
            'variables' => json_encode(['nombre', 'sistema_nombre', 'url_acceso', 'usuario', 'clave', 'dias', 'vence', 'sistema', 'color']),
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
        // El aviso de mensaje nuevo ahora dice qué pidió (contacto, demostración o prueba).
        DB::table('plantillas_correo')->where('codigo', 'nuevo_mensaje')->update([
            'asunto' => '{{ tipo }}: {{ nombre }} · {{ sistema }}',
            'contenido' => DB::raw("REPLACE(contenido, 'Nuevo mensaje desde tu sitio', '{{ tipo }} desde tu sitio')"),
            'variables' => json_encode(['tipo', 'nombre', 'empresa', 'correo', 'telefono', 'sistema_interes', 'mensaje', 'enlace', 'sistema', 'color']),
        ]);
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->where('codigo', 'credenciales_prueba')->delete();
        Schema::table('configuracion_sitio', function (Blueprint $table) {
            $table->string('propietario', 120)->nullable();
            $table->string('cargo', 120)->nullable();
            $table->string('hero_titulo', 150)->nullable();
            $table->string('hero_resaltado', 60)->nullable();
            $table->text('hero_texto')->nullable();
            $table->string('sobre_titulo', 150)->nullable();
            $table->text('sobre_texto')->nullable();
            $table->unsignedSmallInteger('anios_experiencia')->default(0);
            $table->unsignedSmallInteger('proyectos_entregados')->default(0);
            $table->string('foto')->nullable();
            $table->string('imagen_hero')->nullable();
            $table->string('imagen_hero_oscura')->nullable();
        });
        Schema::table('mensajes_contacto', function (Blueprint $table) {
            $table->dropIndex(['tipo', 'estado']);
            $table->dropColumn(['tipo', 'url_acceso', 'usuario_prueba', 'clave_prueba', 'vence_el', 'credenciales_enviadas_en']);
        });
        Schema::dropIfExists('manuales');
        Schema::table('sistemas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
            $table->dropColumn(['modalidad', 'precio', 'acepta_demo', 'acepta_prueba', 'dias_prueba']);
        });
        Schema::dropIfExists('categorias_sistema');
        Schema::dropIfExists('elementos');
        Schema::dropIfExists('secciones');
        Schema::dropIfExists('paginas');
    }
};
