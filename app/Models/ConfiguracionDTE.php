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
        'codigo_establecimiento',
        'pais',
        'afiliacion_iva',
        'frases_config',
        'permite_anulacion_directa',
        'habilitar_notas_credito',
        'habilitar_notas_debito',
        'habilitar_notas_abono',
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
            'permite_anulacion_directa' => 'boolean',
            'habilitar_notas_credito' => 'boolean',
            'habilitar_notas_debito' => 'boolean',
            'habilitar_notas_abono' => 'boolean',
            'frases_config' => 'array',
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
            'codigo_establecimiento' => '1',
            'pais' => 'GT',
            'afiliacion_iva' => 'PEQ',
            'permite_anulacion_directa' => true,
            'habilitar_notas_credito' => true,
            'habilitar_notas_debito' => true,
            'habilitar_notas_abono' => true,
            'frases_config' => [
                ['tipo_frase' => '1', 'codigo_escenario' => '2', 'descripcion' => 'No genera derecho a crédito fiscal (PEQ)'],
                ['tipo_frase' => '2', 'codigo_escenario' => '1', 'descripcion' => 'Sujeto a pagos trimestrales ISR / Régimen'],
            ],
        ]);
    }

    /**
     * Devuelve las frases configuradas en formato legible o arreglo por defecto
     */
    public function obtenerFrases(): array
    {
        if (!empty($this->frases_config) && is_array($this->frases_config)) {
            return $this->frases_config;
        }

        return match ($this->afiliacion_iva) {
            'PEQ' => [
                ['tipo_frase' => '1', 'codigo_escenario' => '2', 'descripcion' => 'No genera derecho a crédito fiscal'],
                ['tipo_frase' => '2', 'codigo_escenario' => '1', 'descripcion' => 'Sujeto a retención o pagos'],
            ],
            'EXE' => [
                ['tipo_frase' => '1', 'codigo_escenario' => '3', 'descripcion' => 'Exento de IVA'],
            ],
            default => [
                ['tipo_frase' => '1', 'codigo_escenario' => '1', 'descripcion' => 'Régimen General afecto a IVA'],
            ],
        };
    }
}
