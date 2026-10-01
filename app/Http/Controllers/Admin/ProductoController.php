<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionPagos;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\Sistema;
use App\Services\Pagos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Panel → Ventas → Productos y precios: qué se vende (software, servicios u otros),
 * con un precio por periodo que se activa o apaga por separado.
 */
class ProductoController extends Controller
{
    public function index(): View
    {
        return view('admin.productos.index', [
            'productos' => Producto::query()->with(['precios', 'sistema'])
                ->withCount(['suscripciones as activas' => fn ($q) => $q->where('estado', 'activa')])
                ->orderBy('orden')->orderBy('nombre')->get(),
            'pagos' => ConfiguracionPagos::actual(),
        ]);
    }

    public function create(Request $request): View
    {
        $sistema = Sistema::query()->find($request->integer('sistema'));

        return $this->formulario(new Producto([
            'tipo' => $sistema ? 'sistema' : 'servicio', 'sistema_id' => $sistema?->id, 'nombre' => $sistema?->nombre,
            'descripcion' => $sistema?->resumen ? Str::limit($sistema->resumen, 300, '') : null,
            'activo' => true, 'orden' => Producto::query()->max('orden') + 1,
        ]));
    }

    public function store(Request $request, Pagos $pagos): RedirectResponse
    {
        [$datos, $precios] = $this->validar($request);
        $producto = DB::transaction(function () use ($datos, $precios, $pagos) {
            $producto = Producto::query()->create($datos);
            $this->guardarPrecios($producto, $precios, $pagos);

            return $producto;
        });

        return redirect()->route('admin.productos.edit', $producto)->with('status', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        return $this->formulario($producto->load('precios'));
    }

    public function update(Request $request, Producto $producto, Pagos $pagos): RedirectResponse
    {
        [$datos, $precios] = $this->validar($request, $producto);
        DB::transaction(function () use ($producto, $datos, $precios, $pagos) {
            $producto->update($datos);
            $this->guardarPrecios($producto, $precios, $pagos);
        });

        return back()->with('status', 'Producto guardado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->suscripciones()->whereIn('estado', ['activa', 'suspendida'])->exists()) {
            return back()->with('aviso', 'Tiene suscripciones activas: desactívalo en lugar de borrarlo (los clientes actuales siguen pagando y con acceso).');
        }
        $producto->delete();

        return redirect()->route('admin.productos.index')->with('status', 'Producto eliminado. Su historial de pagos se conserva.');
    }

    private function formulario(Producto $producto): View
    {
        return view('admin.productos.form', [
            'producto' => $producto,
            'sistemas' => Sistema::query()->orderBy('nombre')->pluck('nombre', 'id'),
            'precios' => $producto->exists ? $producto->precios->keyBy('periodo') : collect(),
            'pagos' => ConfiguracionPagos::actual(),
        ]);
    }

    /** @return array{0: array, 1: array<string, array{activo: bool, monto: ?string}>} */
    private function validar(Request $request, ?Producto $producto = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('nombre'))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique('productos', 'slug')->ignore($producto?->id)],
            'tipo' => ['required', Rule::in(array_keys(Producto::TIPOS))],
            'sistema_id' => ['nullable', 'required_if:tipo,sistema', 'integer', 'exists:sistemas,id'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'incluye' => ['nullable', 'string', 'max:3000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'precios' => ['nullable', 'array'],
            'precios.*.monto' => ['nullable', 'numeric', 'min:1', 'max:99999'],
        ], [
            'slug.unique' => 'Ya hay otro producto con esa dirección.',
            'sistema_id.required_if' => 'Elige qué sistema da acceso este producto.',
            'precios.*.monto.min' => 'Los precios deben ser de al menos 1.',
            'precios.*.monto.numeric' => 'Escribe los precios solo con números (p. ej. 25 o 25.50).',
        ]);

        $precios = [];
        foreach (array_keys(Precio::PERIODOS) as $periodo) {
            $monto = $request->input("precios.{$periodo}.monto");
            $precios[$periodo] = ['activo' => $request->boolean("precios.{$periodo}.activo"), 'monto' => $monto !== null && $monto !== '' ? number_format((float) $monto, 2, '.', '') : null];
            if ($precios[$periodo]['activo'] && $periodo !== 'de_por_vida' && $precios[$periodo]['monto'] === null) {
                throw ValidationException::withMessages(["precios.{$periodo}.monto" => 'Pon el monto del precio «'.Precio::nombrePeriodo($periodo).'» o desactívalo.']);
            }
        }
        // Un sistema se vende por suscripción (el acceso vence si deja de pagar). Para siempre = «de por vida» con asesor.
        if ($datos['tipo'] === 'sistema' && $precios['unico']['activo']) {
            throw ValidationException::withMessages(['precios.unico.monto' => 'El software se vende por suscripción. Para venderlo para siempre usa «De por vida» (lo atiende un asesor).']);
        }

        $base = collect($datos)->except('precios')->all();
        $base['sistema_id'] = $datos['tipo'] === 'sistema' ? $datos['sistema_id'] : null;

        return [$base + ['orden' => (int) ($datos['orden'] ?? 0), 'activo' => $request->boolean('activo'), 'destacado' => $request->boolean('destacado')], $precios];
    }

    /** Crea o actualiza un precio por periodo. Si cambió el monto, el plan viejo de PayPal deja de aceptar suscriptores nuevos. */
    private function guardarPrecios(Producto $producto, array $precios, Pagos $pagos): void
    {
        $actuales = $producto->precios()->get()->keyBy('periodo');
        foreach ($precios as $periodo => ['activo' => $activo, 'monto' => $monto]) {
            $precio = $actuales[$periodo] ?? null;
            if (! $precio && ! $activo && $monto === null) {
                continue;
            }
            $precio ??= new Precio(['producto_id' => $producto->id, 'periodo' => $periodo]);
            $cambioMonto = $precio->exists && (string) $precio->monto !== (string) $monto;
            $precio->fill(['activo' => $activo, 'monto' => $monto])->save();
            if ($cambioMonto && $precio->paypal_plan_id) {
                $pagos->retirarPlan($precio);
            }
        }
    }
}
