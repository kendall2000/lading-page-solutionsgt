<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraCambio;
use App\Models\Conversacion;
use App\Models\MensajeContacto;
use App\Models\Sistema;
use App\Services\Estadisticas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        $desde = now()->subDays(29)->startOfDay();
        $porDia = MensajeContacto::query()->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')->groupBy('dia')->pluck('total', 'dia');
        $visitantesPorDia = DB::table('visitas')->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as dia, COUNT(DISTINCT visitante) as total')->groupBy('dia')->pluck('total', 'dia');

        $dias = collect(range(29, 0))->map(fn ($n) => now()->subDays($n)->toDateString());
        $visitantes = fn (Carbon $inicio, Carbon $fin) => DB::table('visitas')->whereBetween('created_at', [$inicio, $fin])->distinct()->count('visitante');
        $mes = $visitantes($desde, now());
        $mesAnterior = $visitantes($desde->copy()->subDays(30), $desde->copy()->subSecond());

        return view('admin.inicio', [
            'totales' => [
                'visitantesHoy' => $visitantes(now()->startOfDay(), now()),
                'visitantesMes' => $mes,
                'variacionMes' => Estadisticas::variacion($mes, $mesAnterior),
                'nuevos' => MensajeContacto::query()->where('estado', 'nuevo')->count(),
                'chatsAbiertos' => Conversacion::query()->where('estado', 'abierta')->count(),
                'sistemas' => Sistema::query()->where('visible', true)->count(),
            ],
            'ultimos' => MensajeContacto::query()->with('sistema')->latest()->limit(6)->get(),
            'masVistos' => Sistema::query()->orderByDesc('visitas')->limit(6)->get(),
            'actividad' => BitacoraCambio::query()->latest('id')->limit(6)->get(),
            'grafica' => [
                'dias' => $dias->map(fn ($d) => Carbon::parse($d)->format('d/m'))->all(),
                'totales' => $dias->map(fn ($d) => (int) ($porDia[$d] ?? 0))->all(),
                'visitantes' => $dias->map(fn ($d) => (int) ($visitantesPorDia[$d] ?? 0))->all(),
            ],
        ]);
    }
}
