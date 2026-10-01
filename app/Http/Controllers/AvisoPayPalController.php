<?php

namespace App\Http\Controllers;

use App\Exceptions\ErrorPayPal;
use App\Models\ConfiguracionPagos;
use App\Services\Pagos;
use App\Services\PayPal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Avisos de PayPal (webhook): cobros de suscripción, cancelaciones, reembolsos.
 * Si hay Webhook ID, la firma se comprueba con PayPal; aun así el aviso solo indica qué
 * revisar: el estado se vuelve a pedir a PayPal (App\Services\Pagos::procesarAviso).
 */
class AvisoPayPalController extends Controller
{
    public function __invoke(Request $request, PayPal $paypal, Pagos $pagos): Response
    {
        $cfg = ConfiguracionPagos::actual();
        $evento = json_decode($request->getContent(), true);
        if (! $cfg->tieneCredenciales() || ! is_array($evento)) {
            return response('', 204);
        }
        if (filled($cfg->webhook_id) && ! $paypal->avisoAutentico($request)) {
            return response('', 400);
        }

        try {
            $pagos->procesarAviso($evento);
        } catch (ErrorPayPal $e) {
            report($e);

            return response('', 503); // PayPal lo reintenta más tarde
        }

        return response('', 200);
    }
}
