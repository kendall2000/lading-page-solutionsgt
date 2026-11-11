<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un cobro de PayPal (pago único o cuota de suscripción). Lo escribe solo App\Services\Pagos: el panel no lo edita. */
class Pago extends Model
{
    protected $table = 'pagos';

    /** estado => [texto, color]. */
    public const ESTADOS = [
        'pendiente' => ['Pendiente', 'secondary'],
        'completado' => ['Pagado', 'success'],
        'reembolsado' => ['Reembolsado', 'warning'],
        'fallido' => ['Falló', 'danger'],
    ];

    protected $fillable = [
        'cuenta_id', 'producto_id', 'precio_id', 'suscripcion_id', 'paypal_orden_id', 'paypal_id', 'paypal_modo', 'estado',
        'descripcion', 'periodo', 'monto', 'moneda', 'correo', 'pagado_en',
        'numero_comprobante', 'token_publico', 'receptor_nombre', 'receptor_documento', 'receptor_direccion', 'receptor_pais', 'notas_factura',
    ];

    public function asegurarComprobante(): void
    {
        $dirty = false;
        if (empty($this->numero_comprobante)) {
            $prefijo = 'INV-' . ($this->pagado_en ? $this->pagado_en->format('Y') : date('Y'));
            $this->numero_comprobante = $prefijo . '-' . str_pad((string)$this->id, 5, '0', STR_PAD_LEFT);
            $dirty = true;
        }
        if (empty($this->token_publico)) {
            $this->token_publico = bin2hex(random_bytes(24));
            $dirty = true;
        }
        if ($dirty) {
            $this->saveQuietly();
        }
    }

    public function documentoTributario(): BelongsTo
    {
        return $this->belongsTo(DocumentoTributario::class, 'id', 'pago_id');
    }

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'pagado_en' => 'datetime'];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(Suscripcion::class);
    }

    /** @return array{0:string,1:string} */
    public function estadoInfo(): array
    {
        return self::ESTADOS[$this->estado] ?? [ucfirst($this->estado), 'secondary'];
    }

    public function montoTexto(): string
    {
        return Precio::formato($this->monto, $this->moneda);
    }
}
