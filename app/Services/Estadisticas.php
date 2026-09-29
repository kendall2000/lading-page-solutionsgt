<?php

namespace App\Services;

use App\Models\BitacoraCambio;
use App\Models\Cliente;
use App\Models\Manual;
use App\Models\MensajeContacto;
use App\Models\Pagina;
use App\Models\Servicio;
use App\Models\Sistema;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Estadísticas del panel para un rango de fechas (visitas, solicitudes, chat, correos, usuarios y bitácora),
 * comparadas con el periodo anterior de la misma duración.
 */
class Estadisticas
{
    public readonly Carbon $desde;

    public readonly Carbon $hasta;

    public function __construct(Carbon $desde, Carbon $hasta)
    {
        $this->desde = $desde->copy()->startOfDay();
        $this->hasta = $hasta->copy()->endOfDay();
    }

    /** Mismo número de días, justo antes. */
    public function anterior(): self
    {
        $dias = $this->dias()->count();

        return new self($this->desde->copy()->subDays($dias), $this->desde->copy()->subDay());
    }

    /** @return Collection<int, string> fechas Y-m-d del rango */
    public function dias(): Collection
    {
        $dias = collect();
        for ($d = $this->desde->copy(); $d->lte($this->hasta); $d->addDay()) {
            $dias->push($d->toDateString());
        }

        return $dias;
    }

    /** Variación en % contra el periodo anterior (null si antes no hubo nada). */
    public static function variacion(float|int $actual, float|int $antes): ?float
    {
        return $antes > 0 ? round(($actual - $antes) / $antes * 100, 1) : null;
    }

    private function tabla(string $tabla, string $columna = 'created_at'): Builder
    {
        return DB::table($tabla)->whereBetween($columna, [$this->desde, $this->hasta]);
    }

    /** Serie por día (rellena con 0 los días sin datos). */
    private function serie(Collection $porDia): array
    {
        return $this->dias()->map(fn ($d) => (int) ($porDia[$d] ?? 0))->all();
    }

    /** Hora del día (0-23) y día de la semana (0 = domingo) según el motor de la base. */
    private function expresion(string $parte, string $columna = 'created_at'): string
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        return match ($parte) {
            'hora' => $sqlite ? "CAST(strftime('%H', {$columna}) AS INTEGER)" : "HOUR({$columna})",
            'semana' => $sqlite ? "CAST(strftime('%w', {$columna}) AS INTEGER)" : "(DAYOFWEEK({$columna}) - 1)",
        };
    }

    public function visitas(): array
    {
        $totales = fn (self $p) => (array) $p->tabla('visitas')
            ->selectRaw("COUNT(*) as vistas, COUNT(DISTINCT visitante) as visitantes, SUM(CASE WHEN origen <> 'interno' THEN 1 ELSE 0 END) as entradas")
            ->first();
        $actual = $totales($this);
        $antes = $totales($this->anterior());
        $porDia = $this->tabla('visitas')->selectRaw('DATE(created_at) as dia, COUNT(*) as vistas, COUNT(DISTINCT visitante) as visitantes')
            ->groupBy('dia')->get()->keyBy('dia');
        $agrupar = fn (string $campo) => $this->tabla('visitas')->selectRaw("{$campo} as clave, COUNT(DISTINCT visitante) as total")
            ->groupBy('clave')->orderByDesc('total')->pluck('total', 'clave')->map(fn ($v) => (int) $v);

        $horas = $this->tabla('visitas')->selectRaw($this->expresion('hora').' as h, COUNT(*) as total')->groupBy('h')->pluck('total', 'h');
        $semana = $this->tabla('visitas')->selectRaw($this->expresion('semana').' as d, COUNT(*) as total')->groupBy('d')->pluck('total', 'd');

        return [
            'vistas' => (int) $actual['vistas'],
            'visitantes' => (int) $actual['visitantes'],
            'entradas' => (int) $actual['entradas'],
            'paginasPorVisitante' => $actual['visitantes'] ? round($actual['vistas'] / $actual['visitantes'], 1) : 0,
            'antes' => ['vistas' => (int) $antes['vistas'], 'visitantes' => (int) $antes['visitantes']],
            'serie' => [
                'vistas' => $this->serie($porDia->map->vistas),
                'visitantes' => $this->serie($porDia->map->visitantes),
            ],
            'paginas' => $this->tabla('visitas')
                ->selectRaw('ruta, tipo, MAX(titulo) as titulo, COUNT(*) as vistas, COUNT(DISTINCT visitante) as visitantes')
                ->groupBy('ruta', 'tipo')->orderByDesc('vistas')->limit(20)->get(),
            // Por dónde llegaron: solo la primera página de cada visita (no la navegación dentro del sitio).
            'origenes' => $this->tabla('visitas')->where('origen', '<>', 'interno')->selectRaw('origen, COUNT(*) as total')
                ->groupBy('origen')->orderByDesc('total')->pluck('total', 'origen')->map(fn ($v) => (int) $v),
            'sitios' => $this->tabla('visitas')->whereIn('origen', ['otro', 'campana'])->whereNotNull('origen_sitio')
                ->selectRaw('origen_sitio, COUNT(*) as total')->groupBy('origen_sitio')->orderByDesc('total')->limit(10)->pluck('total', 'origen_sitio'),
            'dispositivos' => $agrupar('dispositivo'),
            'navegadores' => $agrupar('navegador'),
            'horas' => collect(range(0, 23))->map(fn ($h) => (int) ($horas[$h] ?? 0))->all(),
            'semana' => collect(range(0, 6))->map(fn ($d) => (int) ($semana[$d] ?? 0))->all(),
        ];
    }

    public function solicitudes(int $visitantes): array
    {
        $total = $this->tabla('mensajes_contacto')->count();
        $porDia = $this->tabla('mensajes_contacto')->selectRaw('DATE(created_at) as dia, tipo, COUNT(*) as total')->groupBy('dia', 'tipo')->get();
        $sistemas = $this->tabla('mensajes_contacto')->whereNotNull('sistema_id')
            ->selectRaw("sistema_id, SUM(CASE WHEN tipo = 'demo' THEN 1 ELSE 0 END) as demos, SUM(CASE WHEN tipo = 'prueba' THEN 1 ELSE 0 END) as pruebas, COUNT(*) as total")
            ->groupBy('sistema_id')->orderByDesc('total')->limit(10)->get();
        $nombres = Sistema::query()->whereIn('id', $sistemas->pluck('sistema_id'))->pluck('nombre', 'id');

        return [
            'total' => $total,
            'antes' => $this->anterior()->tabla('mensajes_contacto')->count(),
            'conversion' => $visitantes ? round($total / $visitantes * 100, 1) : null,
            'porTipo' => $this->tabla('mensajes_contacto')->selectRaw('tipo, COUNT(*) as total')->groupBy('tipo')->pluck('total', 'tipo')->map(fn ($v) => (int) $v),
            'porEstado' => $this->tabla('mensajes_contacto')->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado')->map(fn ($v) => (int) $v),
            'serie' => collect(array_keys(MensajeContacto::TIPOS))->mapWithKeys(fn ($t) => [
                $t => $this->serie($porDia->where('tipo', $t)->pluck('total', 'dia')),
            ])->all(),
            'sistemas' => $sistemas->map(fn ($s) => (object) ['nombre' => $nombres[$s->sistema_id] ?? '—', 'id' => $s->sistema_id,
                'demos' => (int) $s->demos, 'pruebas' => (int) $s->pruebas, 'total' => (int) $s->total]),
            'pendientes' => MensajeContacto::query()->where('estado', 'nuevo')->count(),
        ];
    }

    public function chat(): array
    {
        $conversaciones = $this->tabla('conversaciones')->pluck('id');
        // Primera respuesta: del primer mensaje del visitante al primero del panel, por conversación.
        $primeros = DB::table('mensajes_chat')->whereIn('conversacion_id', $conversaciones)->whereIn('autor', ['visitante', 'admin'])
            ->selectRaw('conversacion_id, autor, MIN(created_at) as primero')->groupBy('conversacion_id', 'autor')->get()->groupBy('conversacion_id');
        $minutos = $primeros->map(function ($filas) {
            $visitante = $filas->firstWhere('autor', 'visitante');
            $admin = $filas->firstWhere('autor', 'admin');

            return $visitante && $admin ? max(0, Carbon::parse($visitante->primero)->diffInMinutes(Carbon::parse($admin->primero))) : null;
        })->filter(fn ($m) => $m !== null);
        $porUsuario = $this->tabla('mensajes_chat')->where('autor', 'admin')->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
        $usuarios = User::query()->whereIn('id', $porUsuario->keys())->pluck('name', 'id');

        return [
            'conversaciones' => $conversaciones->count(),
            'antes' => $this->anterior()->tabla('conversaciones')->count(),
            'mensajes' => $this->tabla('mensajes_chat')->selectRaw('autor, COUNT(*) as total')->groupBy('autor')->pluck('total', 'autor')->map(fn ($v) => (int) $v),
            'respondidas' => $minutos->count(),
            'sinResponder' => $conversaciones->count() - $minutos->count(),
            'primeraRespuesta' => $minutos->isNotEmpty() ? (int) round($minutos->avg()) : null,
            'abiertasAhora' => DB::table('conversaciones')->where('estado', 'abierta')->count(),
            'serie' => $this->serie($this->tabla('conversaciones')->selectRaw('DATE(created_at) as dia, COUNT(*) as total')->groupBy('dia')->pluck('total', 'dia')),
            'paginas' => $this->tabla('conversaciones')->whereNotNull('pagina')->selectRaw('pagina, COUNT(*) as total')
                ->groupBy('pagina')->orderByDesc('total')->limit(8)->pluck('total', 'pagina'),
            'porUsuario' => $porUsuario->mapWithKeys(fn ($t, $id) => [$usuarios[$id] ?? 'Usuario borrado' => (int) $t])->sortDesc(),
        ];
    }

    public function correos(): array
    {
        $porEstado = $this->tabla('bitacora_correos')->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado')->map(fn ($v) => (int) $v);
        $porDia = $this->tabla('bitacora_correos')->selectRaw('DATE(created_at) as dia, estado, COUNT(*) as total')->groupBy('dia', 'estado')->get();
        $total = $porEstado->sum();

        return [
            'total' => $total,
            'porEstado' => $porEstado,
            'exito' => $total ? round(($porEstado['enviado'] ?? 0) / $total * 100, 1) : null,
            'serie' => [
                'enviado' => $this->serie($porDia->where('estado', 'enviado')->pluck('total', 'dia')),
                'fallido' => $this->serie($porDia->where('estado', '<>', 'enviado')->groupBy('dia')->map->sum('total')),
            ],
            'plantillas' => $this->tabla('bitacora_correos')
                ->selectRaw("COALESCE(plantilla, '—') as plantilla, SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados, SUM(CASE WHEN estado <> 'enviado' THEN 1 ELSE 0 END) as fallidos, COUNT(*) as total")
                ->groupBy('plantilla')->orderByDesc('total')->get(),
        ];
    }

    /** Actividad de cada usuario del panel en el rango. */
    public function usuarios(): array
    {
        $acciones = $this->tabla('bitacora_cambios')->whereNotNull('user_id')
            ->selectRaw("user_id, SUM(CASE WHEN accion = 'entrar' THEN 1 ELSE 0 END) as entradas, SUM(CASE WHEN accion IN ('crear', 'editar', 'borrar') THEN 1 ELSE 0 END) as cambios, MAX(created_at) as ultima")
            ->groupBy('user_id')->get()->keyBy('user_id');
        $chat = $this->tabla('mensajes_chat')->where('autor', 'admin')->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'user_id');

        return [
            'lista' => User::query()->orderByDesc('is_active')->orderBy('name')->get()->map(fn (User $u) => (object) [
                'usuario' => $u,
                'entradas' => (int) ($acciones[$u->id]->entradas ?? 0),
                'cambios' => (int) ($acciones[$u->id]->cambios ?? 0),
                'chat' => (int) ($chat[$u->id] ?? 0),
                'ultimaAccion' => isset($acciones[$u->id]) ? Carbon::parse($acciones[$u->id]->ultima) : null,
            ]),
            'fallidos' => $this->tabla('bitacora_cambios')->where('accion', 'fallido')->count(),
            'porModulo' => $this->tabla('bitacora_cambios')->whereIn('accion', ['crear', 'editar', 'borrar'])
                ->selectRaw('modulo, COUNT(*) as total')->groupBy('modulo')->orderByDesc('total')->pluck('total', 'modulo')->map(fn ($v) => (int) $v),
            'porAccion' => $this->tabla('bitacora_cambios')->selectRaw('accion, COUNT(*) as total')->groupBy('accion')->pluck('total', 'accion')->map(fn ($v) => (int) $v),
            'recientes' => BitacoraCambio::query()->whereBetween('created_at', [$this->desde, $this->hasta])->latest('id')->limit(12)->get(),
        ];
    }

    /** Foto actual del contenido (no depende del rango). */
    public static function contenido(): array
    {
        return [
            'paginas' => [Pagina::query()->where('visible', true)->count(), Pagina::query()->count()],
            'sistemas' => [Sistema::query()->where('visible', true)->count(), Sistema::query()->count()],
            'modalidades' => Sistema::query()->where('visible', true)->selectRaw('modalidad, COUNT(*) as total')->groupBy('modalidad')->pluck('total', 'modalidad'),
            'proximamente' => Sistema::query()->where('visible', true)->where('proximamente', true)->count(),
            'manuales' => [Manual::query()->where('visible', true)->count(), Manual::query()->count()],
            'clientes' => Cliente::query()->count(),
            'testimonios' => Cliente::query()->where('mostrar_testimonio', true)->whereNotNull('testimonio')->count(),
            'planes' => Servicio::query()->count(),
            'masVistos' => Sistema::query()->orderByDesc('visitas')->limit(5)->get(['id', 'nombre', 'visitas']),
        ];
    }
}
