<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ErrorPayPal;
use App\Http\Controllers\Controller;
use App\Models\ConfiguracionPagos;
use App\Models\Pago;
use App\Models\Suscripcion;
use App\Services\Pagos;
use App\Services\PayPal;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Panel → Ventas: suscripciones, pagos recibidos y la conexión con PayPal. */
class VentaController extends Controller
{
    public function index(Request $request): View
    {
        $pestana = in_array($request->query('ver'), ['suscripciones', 'pagos', 'paypal'], true) ? $request->query('ver') : 'suscripciones';
        $estadoSus = array_key_exists((string) $request->query('estado'), Suscripcion::ESTADOS) ? $request->query('estado') : null;
        $estadoPago = array_key_exists((string) $request->query('pago'), Pago::ESTADOS) ? $request->query('pago') : null;
        $buscar = trim((string) $request->query('q'));
        $filtroTexto = fn ($q) => $q->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('correo', 'like', "%{$buscar}%")
            ->orWhere('descripcion', 'like', "%{$buscar}%")->orWhere('paypal_id', $buscar)));

        $cobrado = Pago::query()->where('estado', 'completado');

        return view('admin.ventas.index', [
            'pestana' => $pestana,
            'cfg' => ConfiguracionPagos::actual(),
            'suscripciones' => Suscripcion::query()->with(['cuenta', 'contrato'])->when($estadoSus, fn ($q) => $q->where('estado', $estadoSus))
                ->tap($filtroTexto)->orderByDesc('id')->paginate(25, ['*'], 'pag_s')->withQueryString(),
            'pagos' => Pago::query()->with('cuenta')->when($estadoPago, fn ($q) => $q->where('estado', $estadoPago))
                ->tap($filtroTexto)->orderByDesc('pagado_en')->orderByDesc('id')->paginate(25, ['*'], 'pag_p')->withQueryString(),
            'conteos' => Suscripcion::query()->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado'),
            'resumen' => [
                'mes' => (float) (clone $cobrado)->where('pagado_en', '>=', now()->startOfMonth())->sum('monto'),
                'mesAnterior' => (float) (clone $cobrado)->whereBetween('pagado_en', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('monto'),
                'activas' => Suscripcion::query()->where('estado', 'activa')->count(),
                'mensual' => $this->ingresoMensual(),
            ],
            'estadoSus' => $estadoSus,
            'estadoPago' => $estadoPago,
            'buscar' => $buscar,
        ]);
    }

    /** Vuelve a pedir a PayPal el estado y los cobros de la suscripción. */
    public function sincronizar(Suscripcion $suscripcion, Pagos $pagos): RedirectResponse
    {
        try {
            $pagos->sincronizar($suscripcion);
        } catch (ErrorPayPal $e) {
            return back()->with('aviso', $e->getMessage());
        }

        return back()->with('status', 'Suscripción actualizada con lo que dice PayPal: '.mb_strtolower($suscripcion->estadoInfo()[0]).'.');
    }

    public function cancelar(Suscripcion $suscripcion, Pagos $pagos): RedirectResponse
    {
        try {
            $pagos->cancelar($suscripcion, 'Cancelada por '.config('app.name'));
        } catch (ErrorPayPal $e) {
            return back()->with('aviso', $e->getMessage());
        }

        return back()->with('status', 'Suscripción cancelada en PayPal. Ya no se le cobrará; su acceso sigue hasta el final del periodo pagado.');
    }

    /** Pagos para Excel. */
    public function exportar(Request $request): StreamedResponse
    {
        $pagos = Pago::query()->with('cuenta')->where('estado', '!=', 'pendiente')
            ->when($request->date('desde'), fn ($q, $d) => $q->where('pagado_en', '>=', $d->startOfDay()))
            ->when($request->date('hasta'), fn ($q, $d) => $q->where('pagado_en', '<=', $d->endOfDay()))
            ->orderByDesc('pagado_en')->get();

        return Csv::descargar('pagos', ['Fecha', 'Cliente', 'Correo', 'Producto', 'Tipo', 'Monto', 'Moneda', 'Estado', 'Referencia PayPal', 'Modo'],
            $pagos->map(fn (Pago $p) => [
                $p->pagado_en, $p->cuenta?->nombre, $p->correo, $p->descripcion, $p->suscripcion_id ? 'Suscripción' : 'Pago único',
                $p->monto, $p->moneda, $p->estadoInfo()[0], $p->paypal_id, $p->paypal_modo === 'live' ? 'Real' : 'Pruebas',
            ]));
    }

    // ── Conexión con PayPal ─────────────────────────────────────────────────

    public function guardar(Request $request): RedirectResponse
    {
        $cfg = ConfiguracionPagos::actual();
        $datos = $request->validate([
            'modo' => ['required', Rule::in(array_keys(ConfiguracionPagos::MODOS))],
            'client_id' => ['nullable', 'string', 'max:200'],
            'client_secret' => ['nullable', 'string', 'max:200'],
            'webhook_id' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9\-]+$/'],
            'moneda' => ['required', Rule::in(['USD', 'EUR', 'MXN', 'CAD'])],
            'dias_gracia' => ['required', 'integer', 'min:0', 'max:30'],
            'texto_asesor' => ['nullable', 'string', 'max:300'],
        ], ['webhook_id.regex' => 'El Webhook ID solo lleva letras, números y guiones.']);
        if (($datos['client_secret'] ?? '') === '') {
            unset($datos['client_secret']); // vacío = conserva el actual
        }
        $activo = $request->boolean('activo');
        $cambiaCuenta = $cfg->modo !== $datos['modo'] || ($datos['client_id'] ?? null) !== $cfg->client_id || isset($datos['client_secret']);
        $cfg->fill($datos + ['activo' => $activo]);
        if (! $cfg->tieneCredenciales() && $activo) {
            return back()->withInput()->withErrors(['client_id' => 'Para activar los cobros pon el Client ID y el Secret de PayPal.']);
        }
        if ($cambiaCuenta) {
            $cfg->probado_en = null;
        }
        $cfg->save();

        return redirect()->route('admin.ventas.index', ['ver' => 'paypal'])->with('status', $activo
            ? 'PayPal guardado. Los cobros en línea están encendidos'.($cfg->modo === 'sandbox' ? ' en modo de pruebas (no se cobra dinero real).' : ' con cobros reales.')
            : 'PayPal guardado. Los cobros en línea están apagados: el sitio muestra los precios con el botón de contacto.');
    }

    public function probar(PayPal $paypal): RedirectResponse
    {
        try {
            $paypal->token();
        } catch (ErrorPayPal $e) {
            return redirect()->route('admin.ventas.index', ['ver' => 'paypal'])->with('aviso', $e->getMessage());
        }
        ConfiguracionPagos::actual()->forceFill(['probado_en' => now()])->saveQuietly();

        return redirect()->route('admin.ventas.index', ['ver' => 'paypal'])->with('status', 'Conexión con PayPal correcta.');
    }

    /** Lo que entra al mes con las suscripciones activas (semanal ×52/12, trimestral ÷3, anual ÷12). */
    private function ingresoMensual(): float
    {
        return (float) Suscripcion::query()->where('estado', 'activa')->get(['periodo', 'monto'])
            ->sum(fn ($s) => (float) $s->monto * match ($s->periodo) {
                'semanal' => 52 / 12, 'trimestral' => 1 / 3, 'anual' => 1 / 12, default => 1,
            });
    }
}
