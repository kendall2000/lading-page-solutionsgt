<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Servidor de correo (una fila). Con «is_active» se aplica al arrancar
 * (App\Providers\CorreoServiceProvider); si no, se usa la del .env.
 * La clave va cifrada (APP_KEY) y nunca se devuelve a la pantalla.
 */
class ConfiguracionCorreo extends Model
{
    protected $table = 'configuracion_correo';

    protected $fillable = ['host', 'puerto', 'usuario', 'clave', 'cifrado', 'remitente_correo', 'remitente_nombre', 'responder_a', 'avisos_a', 'is_active', 'probado_en'];

    protected $hidden = ['clave'];

    protected function casts(): array
    {
        return ['clave' => 'encrypted', 'puerto' => 'integer', 'is_active' => 'boolean', 'probado_en' => 'datetime'];
    }

    public static function actual(): self
    {
        return self::query()->firstOrCreate([]);
    }

    /** @return list<string> correos válidos de «Avisos a» */
    public function correosAvisos(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->avisos_a)), fn ($c) => filter_var($c, FILTER_VALIDATE_EMAIL)));
    }
}
