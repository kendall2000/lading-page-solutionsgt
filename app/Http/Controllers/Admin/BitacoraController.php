<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraCambio;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Bitácora de cambios: quién hizo qué en el panel, con filtros y descarga. */
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $this->filtros($request);

        return view('admin.bitacora.index', [
            'registros' => $this->consulta($filtros)->with('user')->latest('id')->paginate(30)->withQueryString(),
            'filtros' => $filtros,
            'usuarios' => User::query()->orderBy('name')->pluck('name', 'id'),
            'modulos' => BitacoraCambio::query()->distinct()->orderBy('modulo')->pluck('modulo'),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $registros = $this->consulta($this->filtros($request))->latest('id')->limit(20000)->cursor();

        return Csv::descargar('bitacora-de-cambios', ['Fecha', 'Usuario', 'Acción', 'Módulo', 'Registro', 'Cambios', 'IP'],
            $registros->map(fn (BitacoraCambio $r) => [
                $r->created_at, $r->usuario, $r->accionInfo()[0], $r->modulo, $r->descripcion,
                collect($r->cambios ?? [])->map(fn ($v, $campo) => "{$campo}: ".json_encode($v[0], JSON_UNESCAPED_UNICODE).' → '.json_encode($v[1], JSON_UNESCAPED_UNICODE))->implode(' | '),
                $r->ip,
            ]));
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'usuario' => ['nullable', 'integer'],
            'accion' => ['nullable', Rule::in(array_keys(BitacoraCambio::ACCIONES))],
            'modulo' => ['nullable', 'string', 'max:40'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function consulta(array $f): Builder
    {
        return BitacoraCambio::query()
            ->when($f['usuario'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))
            ->when($f['modulo'] ?? null, fn ($q, $v) => $q->where('modulo', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->when($f['buscar'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('descripcion', 'like', "%{$v}%")
                ->orWhere('usuario', 'like', "%{$v}%")->orWhere('ip', 'like', "%{$v}%")));
    }
}
