<?php

namespace App\Http\Middleware;

use App\Models\Manual;
use App\Models\Pagina;
use App\Models\Sistema;
use App\Services\Visitas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cuenta la visita a una página pública que se mostró bien (ver App\Services\Visitas). */
class RegistrarVisita
{
    public function __construct(private Visitas $visitas) {}

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if ($request->isMethod('GET') && $respuesta->getStatusCode() === 200) {
            [$tipo, $titulo] = match (true) {
                ($s = $request->route('sistema')) instanceof Sistema => ['sistema', $s->nombre],
                ($m = $request->route('manual')) instanceof Manual => ['manual', $m->titulo],
                ($p = $request->route('pagina')) instanceof Pagina => ['pagina', $p->titulo],
                default => ['pagina', 'Inicio'],
            };
            $this->visitas->registrar($request, $tipo, $titulo);
        }

        return $respuesta;
    }
}
