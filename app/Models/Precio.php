<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precio de un producto en un periodo. «unico» se cobra una vez (orden de PayPal);
 * semanal/mensual/trimestral/anual son suscripciones automáticas; «de_por_vida» no se
 * cobra en línea: el botón lleva a hablar con un asesor.
 */
class Precio extends Model
{
    use RegistraCambios;

    protected $table = 'precios';

    /** periodo => [nombre, texto junto al monto, unidad de PayPal, cada cuántas]. */
    public const PERIODOS = [
        'unico' => ['Pago único', 'pago único', null, null],
        'semanal' => ['Semanal', '/ semana', 'WEEK', 1],
        'mensual' => ['Mensual', '/ mes', 'MONTH', 1],
        'trimestral' => ['Trimestral', '/ trimestre', 'MONTH', 3],
        'anual' => ['Anual', '/ año', 'YEAR', 1],
        'de_por_vida' => ['De por vida', 'licencia de por vida', null, null],
    ];

    /** Periodos que son suscripción (cobro automático). */
    public const RECURRENTES = ['semanal', 'mensual', 'trimestral', 'anual'];

    protected $fillable = ['producto_id', 'periodo', 'monto', 'activo'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'activo' => 'boolean'];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** Orden fijo de los periodos (para listas y el sitio). */
    public static function ordenSql(): string
    {
        $casos = collect(array_keys(self::PERIODOS))->map(fn ($p, $i) => "when '{$p}' then {$i}")->implode(' ');

        return "case periodo {$casos} else 99 end";
    }

    public static function nombrePeriodo(?string $periodo): string
    {
        return self::PERIODOS[$periodo][0] ?? (string) $periodo;
    }

    public function esRecurrente(): bool
    {
        return in_array($this->periodo, self::RECURRENTES, true);
    }

    public function esDePorVida(): bool
    {
        return $this->periodo === 'de_por_vida';
    }

    public function periodoTexto(): string
    {
        return self::nombrePeriodo($this->periodo);
    }

    public function sufijo(): string
    {
        return self::PERIODOS[$this->periodo][1] ?? '';
    }

    public function montoTexto(?string $moneda = null): string
    {
        return self::formato($this->monto, $moneda);
    }

    /** Monto con moneda, p. ej. «$25.00». */
    public static function formato(mixed $monto, ?string $moneda = null): string
    {
        $moneda ??= ConfiguracionPagos::actual()->moneda;
        $simbolo = ['USD' => '$', 'EUR' => '€'][$moneda] ?? $moneda.' ';

        return $simbolo.number_format((float) $monto, 2);
    }

    /** Se puede pagar en línea (activo, con monto y con el producto activo). */
    public function sePuedeComprar(): bool
    {
        return $this->activo && ! $this->esDePorVida() && (float) $this->monto > 0 && (bool) $this->producto?->activo;
    }
}
