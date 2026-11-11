<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoTributario extends Model
{
    protected $table = 'documentos_tributarios';

    protected $fillable = [
        'pago_id',
        'cuenta_id',
        'tipo',
        'numero_interno',
        'moneda',
        'subtotal',
        'impuestos',
        'total',
        'tipo_cambio',
        'nit_receptor',
        'nombre_receptor',
        'correo_receptor',
        'direccion_receptor',
        'uuid_sat',
        'serie_sat',
        'numero_sat',
        'fecha_emision',
        'fecha_certificacion',
        'estado',
        'motivo_anulacion',
        'error_mensaje',
        'xml_generado',
        'xml_firmado',
        'xml_certificado',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'impuestos' => 'decimal:2',
            'total' => 'decimal:2',
            'tipo_cambio' => 'decimal:4',
            'fecha_emision' => 'datetime',
            'fecha_certificacion' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }
}
