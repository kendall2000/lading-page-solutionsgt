<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Throwable;

class QrService
{
    /**
     * Genera un código QR en formato SVG limpio (código vectorial).
     */
    public static function generarSvg(string $contenido, int $tamano = 160): string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle($tamano, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            return $writer->writeString($contenido);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Devuelve el SVG codificado en base64 para insertar directamente en <img src="data:image/svg+xml;base64,...">
     */
    public static function generarDataUri(string $contenido, int $tamano = 160): string
    {
        $svg = self::generarSvg($contenido, $tamano);
        if (empty($svg)) {
            return '';
        }
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
