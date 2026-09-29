<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Filtros contra robots sin servicios externos (además del campo trampa y el límite de envíos):
 * marca de llegada cifrada (un formulario enviado demasiado rápido o sin marca es de un robot)
 * y conteo de enlaces en los textos.
 */
class Antispam
{
    /** Una persona no llena nombre, correo y mensaje en menos de esto. */
    public const MINIMO_SEGUNDOS = 3;

    public const MAXIMO_ENLACES = 3;

    public const MENSAJE_RAPIDO = 'No pudimos enviar el formulario. Espera unos segundos e inténtalo de nuevo.';

    /** Marca que va oculta en el formulario (campo «llegada»): la hora en que se mostró, cifrada. */
    public static function marca(?int $hora = null): string
    {
        return Crypt::encryptString((string) ($hora ?? time()));
    }

    /** true si la marca falta, fue alterada o el formulario se envió antes de MINIMO_SEGUNDOS. */
    public static function muyRapido(mixed $marca): bool
    {
        if (! is_string($marca) || $marca === '') {
            return true;
        }
        try {
            $hora = (int) Crypt::decryptString($marca);
        } catch (DecryptException) {
            return true;
        }

        return time() - $hora < self::MINIMO_SEGUNDOS;
    }

    /** Cantidad de enlaces (http://, https://, www.) en un texto. */
    public static function enlaces(?string $texto): int
    {
        return preg_match_all('~\b(?:https?://|www\.)~i', (string) $texto);
    }
}
