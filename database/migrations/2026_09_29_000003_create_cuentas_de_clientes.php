<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cuentas de clientes del sitio (portal «Mi cuenta»). Separadas de «users» (panel): otro guard, nunca entran a /admin.
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('correo', 150)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('empresa', 150)->nullable(); // texto libre del cliente
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete(); // empresa ligada desde el panel
            $table->string('password')->nullable(); // puede no tener: entra con enlace por correo
            $table->timestamp('correo_verificado_en')->nullable();
            $table->boolean('activa')->default(true);
            // Enlace de un solo uso (entrar por correo o invitación): solo el sha256.
            $table->char('token_hash', 64)->nullable()->index();
            $table->string('token_tipo', 15)->nullable();
            $table->timestamp('token_vence')->nullable();
            $table->timestamp('invitada_en')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Sistemas contratados: de una cuenta o de toda la empresa (cliente_id).
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')->constrained('sistemas')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->string('url_acceso')->nullable();
            $table->string('plan', 120)->nullable();
            $table->date('desde')->nullable();
            $table->date('hasta')->nullable(); // vacío = sin vencimiento
            $table->text('notas')->nullable(); // las ve el cliente
            $table->timestamps();
        });

        Schema::table('conversaciones', function (Blueprint $table) {
            $table->foreignId('cuenta_id')->nullable()->after('id')->constrained('cuentas')->nullOnDelete();
        });
        Schema::table('mensajes_contacto', function (Blueprint $table) {
            $table->foreignId('cuenta_id')->nullable()->after('id')->constrained('cuentas')->nullOnDelete();
        });
        Schema::table('manuales', function (Blueprint $table) {
            $table->boolean('solo_clientes')->default(false)->after('visible');
        });

        $ahora = now();
        $boton = fn (string $texto) => '<p style="text-align:center;margin:28px 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">'.$texto.'</a></p>';
        $plantillas = [
            ['cuenta_confirmar', 'Confirmar correo de la cuenta', 'Se envía al crear una cuenta en el sitio, para confirmar el correo.',
                'Confirma tu correo · {{ sistema }}',
                '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2><p>Gracias por crear tu cuenta. Confirma tu correo para ver tus sistemas, solicitudes, conversaciones y manuales.</p>'
                .$boton('Confirmar mi correo').'<p style="color:#8a94ad;font-size:13px;">El enlace vale 24 horas. Si no creaste esta cuenta, ignora este correo.</p>',
                ['nombre', 'enlace', 'sistema', 'color']],
            ['cuenta_enlace', 'Enlace para entrar a la cuenta', 'Enlace de un solo uso para entrar sin contraseña (o si la olvidaste).',
                'Tu enlace para entrar · {{ sistema }}',
                '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2><p>Usa este botón para entrar a tu cuenta. Después puedes crear o cambiar tu contraseña en «Mi perfil».</p>'
                .$boton('Entrar a mi cuenta').'<p style="color:#8a94ad;font-size:13px;">El enlace sirve una sola vez y vence en {{ minutos }} minutos. Si no lo pediste, ignora este correo: tu cuenta sigue segura.</p>',
                ['nombre', 'enlace', 'minutos', 'sistema', 'color']],
            ['cuenta_invitacion', 'Invitación a crear la cuenta', 'Se envía cuando creas una cuenta de cliente desde el panel.',
                '{{ sistema }} te invita a tu cuenta de cliente',
                '<h2 style="margin:0 0 12px;">Hola, {{ nombre }}</h2><p>Te creamos una cuenta para que veas tus sistemas, accesos, solicitudes, conversaciones y manuales en un solo lugar.</p>'
                .$boton('Crear mi contraseña').'<p style="color:#8a94ad;font-size:13px;">La invitación vale {{ dias }} días y sirve una sola vez.</p>',
                ['nombre', 'enlace', 'dias', 'sistema', 'color']],
            ['nueva_cuenta', 'Aviso de cuenta nueva', 'Te llega a «Avisos a» cuando alguien confirma una cuenta nueva creada desde el sitio.',
                'Cuenta nueva: {{ nombre }} · {{ sistema }}',
                '<h2 style="margin:0 0 12px;">{{ nombre }} creó su cuenta</h2><p style="color:#8a94ad;font-size:14px;">{{ correo }} · {{ empresa }}</p>'.$boton('Ver en el panel'),
                ['nombre', 'correo', 'empresa', 'enlace', 'sistema', 'color']],
        ];
        foreach ($plantillas as [$codigo, $nombre, $descripcion, $asunto, $contenido, $variables]) {
            if (DB::table('plantillas_correo')->where('codigo', $codigo)->exists()) {
                continue;
            }
            DB::table('plantillas_correo')->insert([
                'codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion, 'asunto' => $asunto, 'contenido' => $contenido,
                'variables' => json_encode($variables), 'del_sistema' => true, 'is_active' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plantillas_correo')->whereIn('codigo', ['cuenta_confirmar', 'cuenta_enlace', 'cuenta_invitacion', 'nueva_cuenta'])->delete();
        Schema::table('manuales', fn (Blueprint $table) => $table->dropColumn('solo_clientes'));
        Schema::table('mensajes_contacto', fn (Blueprint $table) => $table->dropConstrainedForeignId('cuenta_id'));
        Schema::table('conversaciones', fn (Blueprint $table) => $table->dropConstrainedForeignId('cuenta_id'));
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('cuentas');
    }
};
