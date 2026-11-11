<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionDTE extends Model
{
    protected $table = 'configuracion_dte';

    protected $fillable = [
        'activo',
        'ambiente',
        'nit_emisor',
        'nombre_emisor',
        'nombre_comercial',
        'correo_emisor',
        'direccion',
        'codigo_postal',
        'municipio',
        'departamento',
        'pais',
        'afiliacion_iva',
        'alias_ws',
        'token_signer',
        'llave_ws',
        'url_firma',
        'url_certificacion',
        'url_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public static function actual(): self
    {
        return self::firstOrCreate([], [
            'activo' => false,
            'ambiente' => 'pruebas',
            'nit_emisor' => 'CF',
            'nombre_emisor' => 'SOLUTIONS GT',
            'nombre_comercial' => 'Solutions GT',
            'municipio' => 'GUATEMALA',
            'departamento' => 'GUATEMALA',
            'pais' => 'GT',
            'afiliacion_iva' => 'GEN',
        ]);
    }
}
