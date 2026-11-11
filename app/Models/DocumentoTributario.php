<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoTributario extends Model
{
    protected $table = 'documentos_tributarios';

    protected $fillable = [
        'pago_id',
        'cuenta_id',
        'tipo',
        'numero_interno',
        'token_publico',
        'moneda',
        'metodo_pago',
        'condicion_pago',
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
        'notas',
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

    public function items(): HasMany
    {
        return $this->hasMany(DocumentoTributarioItem::class, 'documento_id')->orderBy('numero_linea');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogDTE::class, 'documento_id')->orderByDesc('id');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function asegurarToken(): void
    {
        if (empty($this->token_publico)) {
            $this->token_publico = bin2hex(random_bytes(24));
            $this->saveQuietly();
        }
    }

    public static function generarNumeroInterno(): string
    {
        $ano = date('Y');
        $ultimo = self::whereYear('created_at', $ano)->max('id') ?? 0;
        return 'FAC-' . $ano . '-' . str_pad((string)($ultimo + 1), 5, '0', STR_PAD_LEFT);
    }
}
