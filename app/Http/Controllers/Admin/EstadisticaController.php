<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Estadisticas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Estadísticas de todo el sitio con rango de fechas: visitas, solicitudes, chat, correos, usuarios y bitácora. */
class EstadisticaController extends Controller
{
    /** rango => texto del botón. */
    public const RANGOS = [
        'hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', '90' => '90 días', 'mes' => 'Este mes',
        'mes_anterior' => 'Mes anterior', '365' => '12 meses',
    ];

    public function __invoke(Request $request): View
    {
        [$rango, $desde, $hasta] = self::rango($request);
        $e = new Estadisticas($desde, $hasta);
        $visitas = $e->visitas();

        return view('admin.estadisticas.index', [
            'rango' => $rango,
            'e' => $e,
            'etiquetas' => $e->dias()->map(fn ($d) => Carbon::parse($d)->format('d/m'))->all(),
            'visitas' => $visitas,
            'solicitudes' => $e->solicitudes($visitas['visitantes']),
            'chat' => $e->chat(),
            'correos' => $e->correos(),
            'usuarios' => $e->usuarios(),
            'contenido' => Estadisticas::contenido(),
        ]);
    }

    /** @return array{0: string, 1: Carbon, 2: Carbon} */
    public static function rango(Request $request): array
    {
        $rango = (string) $request->query('rango', '30');
        $hoy = now();

        if ($rango === 'personalizado') {
            $datos = $request->validate([
                'desde' => ['required', 'date_format:Y-m-d'],
                'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde', 'before_or_equal:'.$hoy->toDateString()],
            ], [], ['desde' => 'desde', 'hasta' => 'hasta']);
            $desde = Carbon::parse($datos['desde']);
            // Máximo 2 años para que las consultas sigan siendo rápidas.
            if ($desde->lt($hoy->copy()->subYears(2))) {
                $desde = $hoy->copy()->subYears(2);
            }

            return [$rango, $desde, Carbon::parse($datos['hasta'])];
        }

        return match ($rango) {
            'hoy' => ['hoy', $hoy->copy(), $hoy->copy()],
            '7', '90', '365' => [$rango, $hoy->copy()->subDays((int) $rango - 1), $hoy->copy()],
            'mes' => ['mes', $hoy->copy()->startOfMonth(), $hoy->copy()],
            'mes_anterior' => ['mes_anterior', $hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()],
            default => ['30', $hoy->copy()->subDays(29), $hoy->copy()],
        };
    }
}
