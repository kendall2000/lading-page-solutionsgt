<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que los visitantes contacten por WhatsApp sin estar obligados a dejar correo
 * (formulario de contacto y chat en vivo), y agrega la variable «whatsapp» al aviso
 * «nuevo_mensaje» sin pisar plantillas editadas en el panel.
 */
return new class extends Migration
{
    private const CONTENIDO_ORIGINAL = '<h2 style="margin:0 0 12px;">{{ tipo }} desde tu sitio</h2>'
        .'<table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:16px;">'
        .'<tr><td style="padding:4px 0;color:#8a94ad;width:120px;">Nombre</td><td>{{ nombre }}</td></tr>'
        .'<tr><td style="padding:4px 0;color:#8a94ad;">Empresa</td><td>{{ empresa }}</td></tr>'
        .'<tr><td style="padding:4px 0;color:#8a94ad;">Correo</td><td>{{ correo }}</td></tr>'
        .'<tr><td style="padding:4px 0;color:#8a94ad;">Teléfono</td><td>{{ telefono }}</td></tr>'
        .'<tr><td style="padding:4px 0;color:#8a94ad;">Sistema</td><td>{{ sistema_interes }}</td></tr></table>'
        .'<div style="background:#f5f7fa;border-radius:8px;padding:16px;white-space:pre-line;">{{ mensaje }}</div>'
        .'<p style="text-align:center;margin:28px 0 0;"><a href="{{ enlace }}" style="background:{{ color }};color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">Abrir en el panel</a></p>';

    private const BOTON_WHATSAPP = ' <a href="{{ whatsapp }}" style="background:#25d366;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;margin-left:8px;">Responder por WhatsApp</a>';

    public function up(): void
    {
        Schema::table('mensajes_contacto', function (Blueprint $table) {
            $table->string('correo', 150)->nullable()->change();
        });

        Schema::table('conversaciones', function (Blueprint $table) {
            $table->string('correo', 150)->nullable()->change();
        });

        $plantilla = DB::table('plantillas_correo')->where('codigo', 'nuevo_mensaje')->first();
        if ($plantilla) {
            $variables = json_decode((string) $plantilla->variables, true);
            $variables = is_array($variables) ? $variables : [];
            if (! in_array('whatsapp', $variables, true)) {
                $variables[] = 'whatsapp';
            }

            $actualizacion = ['variables' => json_encode($variables)];
            if ($plantilla->contenido === self::CONTENIDO_ORIGINAL) {
                $actualizacion['contenido'] = str_replace(
                    'Abrir en el panel</a></p>',
                    'Abrir en el panel</a>'.self::BOTON_WHATSAPP.'</p>',
                    self::CONTENIDO_ORIGINAL
                );
            }

            DB::table('plantillas_correo')->where('id', $plantilla->id)->update($actualizacion);
        }
    }

    public function down(): void
    {
        $plantilla = DB::table('plantillas_correo')->where('codigo', 'nuevo_mensaje')->first();
        if ($plantilla) {
            $variables = json_decode((string) $plantilla->variables, true);
            $variables = is_array($variables) ? array_values(array_diff($variables, ['whatsapp'])) : [];
            $actualizacion = ['variables' => json_encode($variables)];
            $conBoton = str_replace(
                'Abrir en el panel</a></p>',
                'Abrir en el panel</a>'.self::BOTON_WHATSAPP.'</p>',
                self::CONTENIDO_ORIGINAL
            );
            if ($plantilla->contenido === $conBoton) {
                $actualizacion['contenido'] = self::CONTENIDO_ORIGINAL;
            }
            DB::table('plantillas_correo')->where('id', $plantilla->id)->update($actualizacion);
        }

        DB::table('conversaciones')->whereNull('correo')->update(['correo' => '']);
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->string('correo', 150)->nullable(false)->change();
        });

        DB::table('mensajes_contacto')->whereNull('correo')->update(['correo' => '']);
        Schema::table('mensajes_contacto', function (Blueprint $table) {
            $table->string('correo', 150)->nullable(false)->change();
        });
    }
};
