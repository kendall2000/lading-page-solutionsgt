<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MensajeContacto;
use App\Models\Servicio;
use App\Models\Sistema;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        $desde = now()->subDays(29)->startOfDay();
        $porDia = MensajeContacto::query()->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')->groupBy('dia')->pluck('total', 'dia');

        $dias = collect(range(29, 0))->map(fn ($n) => now()->subDays($n)->toDateString());

        return view('admin.inicio', [
            'totales' => [
                'sistemas' => Sistema::query()->count(),
                'clientes' => Cliente::query()->count(),
                'servicios' => Servicio::query()->count(),
                'nuevos' => MensajeContacto::query()->where('estado', 'nuevo')->count(),
            ],
            'ultimos' => MensajeContacto::query()->with('sistema')->latest()->limit(6)->get(),
            'masVistos' => Sistema::query()->orderByDesc('visitas')->limit(6)->get(),
            'grafica' => [
                'dias' => $dias->map(fn ($d) => Carbon::parse($d)->format('d/m'))->all(),
                'totales' => $dias->map(fn ($d) => (int) ($porDia[$d] ?? 0))->all(),
            ],
        ]);
    }
}
