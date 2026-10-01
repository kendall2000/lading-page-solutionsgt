<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Suscripción de PayPal de una cuenta. El estado lo manda PayPal (App\Services\Pagos::sincronizar). */
class Suscripcion extends Model
{
    use RegistraCambios;

    protected $table = 'suscripciones';

    /** estado => [texto, color]. */
    public const ESTADOS = [
        'pendiente' => ['Sin aprobar', 'secondary'],
        'activa' => ['Activa', 'success'],
        'suspendida' => ['Suspendida', 'warning'],
        'cancelada' => ['Cancelada', 'danger'],
        'vencida' => ['Vencida', 'secondary'],
    ];

    protected $fillable = [
        'cuenta_id', 'producto_id', 'precio_id', 'contrato_id', 'paypal_id', 'paypal_modo', 'estado', 'descripcion', 'periodo',
        'monto', 'moneda', 'correo', 'activada_en', 'siguiente_cobro', 'cancelada_en', 'sincronizada_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2', 'activada_en' => 'datetime', 'siguiente_cobro' => 'datetime',
            'cancelada_en' => 'datetime', 'sincronizada_en' => 'datetime',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function precio(): BelongsTo
    {
        return $this->belongsTo(Precio::class);
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class)->orderByDesc('pagado_en')->orderByDesc('id');
    }

    /** @return array{0:string,1:string} */
    public function estadoInfo(): array
    {
        return self::ESTADOS[$this->estado] ?? [ucfirst($this->estado), 'secondary'];
    }

    public function sePuedeCancelar(): bool
    {
        return in_array($this->estado, ['activa', 'suspendida'], true);
    }

    /** «$25.00 / mes». */
    public function montoTexto(): string
    {
        return trim(Precio::formato($this->monto, $this->moneda).' '.(Precio::PERIODOS[$this->periodo][1] ?? ''));
    }
}
