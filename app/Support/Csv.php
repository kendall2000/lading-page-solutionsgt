<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Descargas CSV que Excel abre bien (UTF-8 con BOM) y a salvo de fórmulas inyectadas. */
class Csv
{
    /**
     * @param  list<string>  $encabezados
     * @param  iterable<array<int, mixed>>  $filas
     */
    public static function descargar(string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $encabezados, ',', '"', '');
            foreach ($filas as $fila) {
                fputcsv($salida, array_map([self::class, 'celda'], $fila), ',', '"', '');
            }
            fclose($salida);
        }, Str::slug($nombre).'-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Un texto que empieza con = + - @ lo ejecutaría Excel como fórmula: se antepone un apóstrofo. */
    public static function celda(mixed $valor): string
    {
        $texto = match (true) {
            $valor === null => '',
            is_bool($valor) => $valor ? 'Sí' : 'No',
            $valor instanceof \DateTimeInterface => $valor->format('Y-m-d H:i'),
            default => (string) $valor,
        };

        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'".$texto : $texto;
    }
}
