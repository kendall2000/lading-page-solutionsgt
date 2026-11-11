<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoTributario extends Model
{
    protected $table = 'documentos_tributarios';

    public const TIPO_FACTURA = 'FACT';

    public const TIPO_NOTA_CREDITO = 'NCRE';

    public const TIPO_NOTA_DEBITO = 'NDEB';

    public const TIPO_NOTA_ABONO = 'NABN';

    /** Tipos que nacen de una factura certificada, con su nombre y prefijo del número interno. */
    public const NOTAS = [
        self::TIPO_NOTA_CREDITO => ['nombre' => 'Nota de crédito', 'prefijo' => 'NC'],
        self::TIPO_NOTA_DEBITO => ['nombre' => 'Nota de débito', 'prefijo' => 'ND'],
        self::TIPO_NOTA_ABONO => ['nombre' => 'Nota de abono', 'prefijo' => 'NA'],
    ];

    /** Notas que rebajan el saldo de la factura (la de débito lo aumenta). */
    public const NOTAS_QUE_REBAJAN = [self::TIPO_NOTA_CREDITO, self::TIPO_NOTA_ABONO];

    protected $fillable = [
        'pago_id',
        'cuenta_id',
        'documento_origen_id',
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
        'fecha_anulacion',
        'error_mensaje',
        'notas',
        'xml_generado',
        'xml_firmado',
        'xml_certificado',
        'xml_anulacion',
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
            'fecha_anulacion' => 'datetime',
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

    /** Factura a la que se refiere una nota. */
    public function origen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'documento_origen_id');
    }

    /** Notas de crédito, débito o abono emitidas sobre esta factura. */
    public function complementarios(): HasMany
    {
        return $this->hasMany(self::class, 'documento_origen_id')->orderBy('id');
    }

    public function esFactura(): bool
    {
        return $this->tipo === self::TIPO_FACTURA;
    }

    public function esNota(): bool
    {
        return isset(self::NOTAS[$this->tipo]);
    }

    public function estaCertificado(): bool
    {
        return $this->estado === 'certificado';
    }

    public function estaAnulado(): bool
    {
        return $this->estado === 'anulado';
    }

    /** «Factura», «Nota de crédito»… */
    public function nombreTipo(): string
    {
        return self::NOTAS[$this->tipo]['nombre'] ?? 'Factura';
    }

    /**
     * Saldo que aún se puede rebajar con notas de crédito o abono:
     * total de la factura − notas certificadas que rebajan.
     */
    public function saldoAcreditable(): float
    {
        $rebajado = (float) $this->complementarios()
            ->whereIn('tipo', self::NOTAS_QUE_REBAJAN)
            ->where('estado', 'certificado')
            ->sum('total');

        return max(0.0, round((float) $this->total - $rebajado, 2));
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

    public static function generarNumeroInterno(string $tipo = self::TIPO_FACTURA): string
    {
        $prefijo = self::NOTAS[$tipo]['prefijo'] ?? 'FAC';
        $ano = date('Y');
        $ultimo = self::whereYear('created_at', $ano)->max('id') ?? 0;
        return $prefijo . '-' . $ano . '-' . str_pad((string)($ultimo + 1), 5, '0', STR_PAD_LEFT);
    }
}
