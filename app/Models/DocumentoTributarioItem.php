<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoTributarioItem extends Model
{
    protected $table = 'documento_tributario_items';

    protected $fillable = [
        'documento_id',
        'numero_linea',
        'tipo',
        'descripcion',
        'cantidad',
        'unidad_medida',
        'precio_unitario',
        'descuento',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'numero_linea' => 'integer',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoTributario::class, 'documento_id');
    }

    public function tipoTexto(): string
    {
        return $this->tipo === 'B' ? 'Bien' : 'Servicio';
    }
}
