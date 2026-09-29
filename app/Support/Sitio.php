<?php

namespace App\Support;

use App\Models\ConfiguracionSitio;
use App\Models\Pagina;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** Configuración del sitio (una fila) en caché, menú de páginas y enlaces a secciones. */
class Sitio
{
    // «v2»: la clave anterior guardaba el modelo serializado (ilegible en Laravel 13).
    private const CLAVE = 'configuracion_sitio_v2';

    private static ?ConfiguracionSitio $actual = null;

    private static ?Collection $menu = null;

    /** @var array<string, string> */
    private static array $enlaces = [];

    public static function config(): ConfiguracionSitio
    {
        if (self::$actual) {
            return self::$actual;
        }
        // En caché va solo el arreglo de columnas, no el modelo: Laravel 13 no deserializa objetos
        // de la caché (cache.serializable_classes = false, contra ataques si se filtra APP_KEY).
        $datos = rescue(
            fn () => Cache::rememberForever(self::CLAVE, fn () => ConfiguracionSitio::query()->first()?->getAttributes()),
            null,
            false,
        );

        return self::$actual = is_array($datos)
            ? (new ConfiguracionSitio)->newFromBuilder($datos)
            : new ConfiguracionSitio(['nombre' => 'Solutions GT']);
    }

    /** Páginas del menú (primer nivel) con sus submenús. */
    public static function menu(): Collection
    {
        return self::$menu ??= rescue(fn () => Pagina::query()->publicas()->where('en_menu', true)->whereNull('padre_id')
            ->with(['hijas' => fn ($q) => $q->where('visible', true)->where('en_menu', true)])
            ->orderByDesc('es_inicio')->orderBy('orden')->orderBy('id')->get(), collect(), false);
    }

    /** Enlace al formulario de contacto (primera página visible con una sección «Contacto»). */
    public static function enlaceContacto(): string
    {
        return self::enlaceBloque('contacto', 'contacto');
    }

    /**
     * Enlace a la primera página visible que tenga una sección del tipo indicado
     * (p. ej. «sistemas» para el catálogo); sin ninguna, a la portada.
     */
    public static function enlaceBloque(string $tipo, ?string $ancla = null): string
    {
        return self::$enlaces[$tipo] ??= rescue(function () use ($tipo, $ancla) {
            $pagina = Pagina::query()->publicas()
                ->whereHas('secciones', fn ($q) => $q->where('tipo', $tipo)->where('visible', true))
                ->orderByDesc('es_inicio')->orderBy('orden')->first();
            if (! $pagina) {
                return route('inicio');
            }
            $seccion = $pagina->secciones()->where('tipo', $tipo)->where('visible', true)->first();

            return $pagina->enlace().'#'.($ancla ?? 's'.$seccion->id);
        }, fn () => route('inicio'), false);
    }

    public static function nombre(): string
    {
        return self::config()->nombre ?: 'Solutions GT';
    }

    public static function olvidar(): void
    {
        self::$actual = null;
        self::$menu = null;
        self::$enlaces = [];
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
