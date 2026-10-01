<?php

namespace App\Http\Controllers\Cuenta;

use App\Exceptions\ErrorPayPal;
use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Pago;
use App\Models\Precio;
use App\Models\Suscripcion;
use App\Services\Pagos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Compras con PayPal desde «Mi cuenta»: confirmar, volver de PayPal, ver pagos y cancelar suscripciones. */
class PagoController extends Controller
{
    public function __construct(private readonly Pagos $pagos) {}

    private function cuenta(): Cuenta
    {
        return Auth::guard('cliente')->user();
    }

    /** Resumen antes de ir a PayPal (también es a donde vuelve tras iniciar sesión). */
    public function confirmar(Precio $precio): View|RedirectResponse
    {
        $precio->load('producto.sistema');
        if (! Pagos::cobrando() || ! $precio->sePuedeComprar()) {
            return redirect()->route('cuenta.pagos')->withErrors(['pago' => 'Ese precio ya no está a la venta en línea. Escríbenos y te ayudamos.']);
        }

        $cuenta = $this->cuenta();

        return view('publico.cuenta.comprar', ['cuenta' => $cuenta, 'precio' => $precio, 'producto' => $precio->producto] + PortalController::contadores($cuenta));
    }

    public function comprar(Request $request, Precio $precio): RedirectResponse
    {
        $request->validate(['acepto' => ['accepted']], ['acepto.accepted' => 'Marca que estás de acuerdo con el cobro para continuar.']);
        try {
            $url = $this->pagos->iniciar($precio->load('producto'), $this->cuenta());
        } catch (ErrorPayPal $e) {
            return back()->withErrors(['pago' => $e->getMessage()]);
        }

        return redirect()->away($url);
    }

    /** PayPal devuelve al cliente aquí tras aprobar la suscripción (?subscription_id=…). */
    public function volverSuscripcion(Request $request): RedirectResponse
    {
        $s = Suscripcion::query()->where('cuenta_id', $this->cuenta()->id)->where('paypal_id', (string) $request->query('subscription_id'))->first();
        if (! $s) {
            return redirect()->route('cuenta.pagos')->withErrors(['pago' => 'No encontramos esa suscripción en tu cuenta.']);
        }
        try {
            $this->pagos->sincronizar($s);
        } catch (ErrorPayPal) {
            return redirect()->route('cuenta.pagos')->with('status', 'Recibimos tu suscripción; PayPal la está confirmando. En unos minutos aparecerá activa.');
        }

        return redirect()->route('cuenta.pagos')->with('status', match ($s->estado) {
            'activa' => '¡Listo! Tu suscripción a '.$s->descripcion.' está activa. Te enviamos el comprobante por correo.',
            'pendiente' => 'Recibimos tu suscripción; PayPal la está confirmando. En unos minutos aparecerá activa.',
            default => 'PayPal no activó la suscripción ('.mb_strtolower($s->estadoInfo()[0]).'). Si crees que es un error, escríbenos.',
        });
    }

    /** PayPal devuelve al cliente aquí tras aprobar un pago único (?token=ID de la orden). */
    public function volverOrden(Request $request): RedirectResponse
    {
        $pago = Pago::query()->where('cuenta_id', $this->cuenta()->id)->where('paypal_orden_id', (string) $request->query('token'))->first();
        if (! $pago) {
            return redirect()->route('cuenta.pagos')->withErrors(['pago' => 'No encontramos ese pago en tu cuenta.']);
        }
        try {
            $this->pagos->confirmarOrden($pago);
        } catch (ErrorPayPal) {
            return redirect()->route('cuenta.pagos')->withErrors(['pago' => 'PayPal no pudo completar el cobro. No se te cobró nada; puedes intentar de nuevo.']);
        }

        $destino = redirect()->route('cuenta.pagos');

        return match ($pago->estado) {
            'completado' => $destino->with('status', '¡Gracias! Recibimos tu pago de '.$pago->montoTexto().'. Te enviamos el comprobante por correo.'),
            'pendiente' => $destino->with('status', 'PayPal está revisando tu pago. Te avisaremos por correo cuando se confirme.'),
            default => $destino->withErrors(['pago' => 'PayPal no completó el cobro. Si se te descontó dinero, escríbenos con la referencia '.$pago->paypal_id.'.']),
        };
    }

    public function cancelado(): RedirectResponse
    {
        return redirect()->route('cuenta.pagos')->with('status', 'Cancelaste el pago en PayPal: no se te cobró nada.');
    }

    public function index(): View
    {
        $cuenta = $this->cuenta();

        return view('publico.cuenta.pagos', [
            'cuenta' => $cuenta,
            'suscripciones' => Suscripcion::query()->with('contrato')->where('cuenta_id', $cuenta->id)->where('estado', '!=', 'pendiente')->orderByDesc('id')->get(),
            'pagos' => Pago::query()->where('cuenta_id', $cuenta->id)->where('estado', '!=', 'pendiente')->orderByDesc('pagado_en')->paginate(15),
        ] + PortalController::contadores($cuenta));
    }

    public function cancelar(Suscripcion $suscripcion): RedirectResponse
    {
        abort_unless($suscripcion->cuenta_id === $this->cuenta()->id, 404);
        try {
            $this->pagos->cancelar($suscripcion, 'Cancelada por el cliente');
        } catch (ErrorPayPal) {
            return back()->withErrors(['pago' => 'No se pudo cancelar en este momento. Intenta de nuevo o escríbenos.']);
        }
        $hasta = $suscripcion->contrato?->hasta;

        return back()->with('status', 'Suscripción cancelada: ya no se te cobrará.'.($hasta ? ' Puedes seguir usándolo hasta el '.$hasta->format('d/m/Y').'.' : ''));
    }
}
