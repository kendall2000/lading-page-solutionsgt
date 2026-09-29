<?php

namespace App\Support;

use App\Models\ConfiguracionSitio;
use Illuminate\Support\Facades\Cache;

/** Configuración del sitio (una fila) en caché: se usa en cada vista. */
class Sitio
{
    private const CLAVE = 'configuracion_sitio';

    private static ?ConfiguracionSitio $actual = null;

    public static function config(): ConfiguracionSitio
    {
        if (self::$actual) {
            return self::$actual;
        }
        $cfg = rescue(
            fn () => Cache::rememberForever(self::CLAVE, fn () => ConfiguracionSitio::query()->first()),
            null,
            false,
        );

        return self::$actual = $cfg ?? new ConfiguracionSitio(['nombre' => 'Solutions GT']);
    }

    public static function nombre(): string
    {
        return self::config()->nombre ?: 'Solutions GT';
    }

    public static function olvidar(): void
    {
        self::$actual = null;
        Cache::forget(self::CLAVE);
    }

    /** Color hex → «r, g, b». */
    public static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        return implode(', ', array_map('hexdec', str_split($hex, 2)));
    }

    /** Oscurece (factor > 0) o aclara (factor < 0) un color hex. */
    public static function oscurecer(string $hex, float $factor): string
    {
        $partes = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        $partes = array_map(fn ($c) => (int) max(0, min(255, $factor >= 0 ? $c * (1 - $factor) : $c + (255 - $c) * -$factor)), $partes);

        return sprintf('#%02x%02x%02x', ...$partes);
    }
}
