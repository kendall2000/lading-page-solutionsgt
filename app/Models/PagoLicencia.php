<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pago de una licencia de Nexus registrado a mano (transferencia, depósito, efectivo…). */
class PagoLicencia extends Model
{
    use RegistraCambios;

    protected $table = 'pagos_licencia';

    public const METODOS = [
        'transferencia' => 'Transferencia', 'deposito' => 'Depósito', 'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta', 'paypal' => 'PayPal', 'otro' => 'Otro',
    ];

    protected $fillable = ['instalacion_id', 'pagado_el', 'monto', 'moneda', 'metodo', 'referencia', 'meses', 'vence_antes', 'vence_despues', 'notas', 'user_id', 'anulado_en'];

    protected function casts(): array
    {
        return [
            'pagado_el' => 'date', 'vence_antes' => 'date', 'vence_despues' => 'date', 'anulado_en' => 'datetime',
            'monto' => 'decimal:2', 'meses' => 'integer',
        ];
    }

    public function instalacion(): BelongsTo
    {
        return $this->belongsTo(Instalacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function montoTexto(): string
    {
        return $this->moneda.' '.number_format((float) $this->monto, 2);
    }
}
