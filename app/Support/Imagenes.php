<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Imágenes del sitio en Contabo Object Storage (disco «contabo»).
 * Las rutas que empiezan con «assets/» son imágenes de la plantilla dentro de public/.
 */
class Imagenes
{
    public const DISCO = 'contabo';

    /** Regla de validación para una imagen (sin SVG: puede llevar código). */
    public static function regla(int $kb = 4096, string $mimes = 'png,jpg,jpeg,webp'): array
    {
        return ['nullable', 'file', 'max:'.$kb, 'mimes:'.$mimes];
    }

    public const MENSAJES = [
        'mimes' => 'Formato no permitido. Usa PNG, JPG o WEBP.',
        'max' => 'La imagen es demasiado grande.',
    ];

    public static function url(?string $ruta): ?string
    {
        if (blank($ruta)) {
            return null;
        }
        if (str_starts_with($ruta, 'assets/') || str_starts_with($ruta, 'http')) {
            return str_starts_with($ruta, 'http') ? $ruta : asset($ruta);
        }

        return Storage::disk(self::DISCO)->url($ruta);
    }

    /** Sube el archivo público a Contabo y devuelve la ruta guardada. */
    public static function subir(UploadedFile $archivo, string $carpeta): string
    {
        return $archivo->storePublicly($carpeta, self::DISCO);
    }

    /** Borra una imagen de Contabo sin detener el proceso si falla. */
    public static function borrar(?string $ruta): void
    {
        if (filled($ruta) && ! str_starts_with($ruta, 'assets/') && ! str_starts_with($ruta, 'http')) {
            rescue(fn () => Storage::disk(self::DISCO)->delete($ruta), null, false);
        }
    }

    /**
     * Para cada campo: sube el archivo nuevo (y borra el anterior) o lo quita si
     * marcaron «quitar_{campo}». Si Contabo falla, los datos ya quedaron guardados y se avisa.
     *
     * @param  list<string>  $campos
     */
    public static function guardarCampos(Request $request, Model $modelo, array $campos, string $carpeta): void
    {
        try {
            foreach ($campos as $campo) {
                $anterior = $modelo->{$campo};
                if ($request->hasFile($campo)) {
                    $modelo->update([$campo => self::subir($request->file($campo), $carpeta)]);
                } elseif ($request->boolean("quitar_{$campo}")) {
                    $modelo->update([$campo => null]);
                } else {
                    continue;
                }
                self::borrar($anterior);
            }
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages([$campos[0] => 'Los datos se guardaron, pero una imagen no se pudo subir a Contabo. Intenta de nuevo.']);
        }
    }

    /** Texto con un elemento por línea → lista sin vacíos. */
    public static function lineas(?string $texto): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $texto))));
    }
}
