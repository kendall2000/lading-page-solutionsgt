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

    /**
     * Catálogo de frases FEL de la SAT más usadas («tipo-escenario» => texto).
     * La SAT valida cada frase contra lo inscrito en el RTU del NIT emisor (error 2614 si no corresponde).
     */
    public const CATALOGO_FRASES = [
        '1-1' => 'Sujeto a pagos trimestrales ISR',
        '1-2' => 'Sujeto a retención definitiva ISR',
        '1-3' => 'Sujeto a pago directo ISR',
        '2-1' => 'Agente de retención del IVA (solo si el NIT está calificado como agente)',
        '3-1' => 'No genera derecho a crédito fiscal (Pequeño Contribuyente)',
        '4-*' => 'Exenta o no afecta al IVA (el escenario depende del artículo de la ley)',
    ];

    /** Texto de una frase según el catálogo, o null si no está en él. */
    public static function textoFrase(string $tipo, string $escenario): ?string
    {
        return self::CATALOGO_FRASES["{$tipo}-{$escenario}"]
            ?? self::CATALOGO_FRASES["{$tipo}-*"]
            ?? null;
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
                ['tipo_frase' => '3', 'codigo_escenario' => '1', 'descripcion' => 'No genera derecho a crédito fiscal (Pequeño Contribuyente)'],
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
                ['tipo_frase' => '3', 'codigo_escenario' => '1', 'descripcion' => 'No genera derecho a crédito fiscal (Pequeño Contribuyente)'],
            ],
            'EXE' => [
                ['tipo_frase' => '1', 'codigo_escenario' => '3', 'descripcion' => 'Sujeto a pago directo ISR'],
            ],
            default => [
                ['tipo_frase' => '1', 'codigo_escenario' => '1', 'descripcion' => 'Sujeto a pagos trimestrales ISR'],
            ],
        };
    }
}
