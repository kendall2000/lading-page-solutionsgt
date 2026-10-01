<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;

/**
 * Cobros con PayPal (una fila, Panel → Ventas → PayPal). Las credenciales viven
 * en la base (no en el .env); el secreto va cifrado (APP_KEY) y nunca vuelve a la pantalla.
 */
class ConfiguracionPagos extends Model
{
    use RegistraCambios;

    protected $table = 'configuracion_pagos';

    public const MODOS = ['sandbox' => 'Pruebas (sandbox)', 'live' => 'Cobros reales'];

    protected $fillable = ['activo', 'modo', 'client_id', 'client_secret', 'webhook_id', 'moneda', 'dias_gracia', 'texto_asesor', 'probado_en'];

    protected $hidden = ['client_secret'];

    protected function casts(): array
    {
        return ['client_secret' => 'encrypted', 'activo' => 'boolean', 'dias_gracia' => 'integer', 'probado_en' => 'datetime'];
    }

    /** La fila de configuración (una consulta por petición). */
    public static function actual(): self
    {
        return once(fn () => self::query()->firstOrCreate([], ['modo' => 'sandbox', 'moneda' => 'USD', 'dias_gracia' => 3]));
    }

    public function tieneCredenciales(): bool
    {
        return filled($this->client_id) && filled($this->client_secret);
    }

    /** Se puede cobrar en línea: encendido y con credenciales. */
    public function cobrando(): bool
    {
        return $this->activo && $this->tieneCredenciales();
    }

    public function urlApi(): string
    {
        return $this->modo === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }
}
