<?php

namespace App\Services;

use App\Exceptions\ErrorPayPal;
use App\Models\ConfiguracionPagos;
use App\Models\Precio;
use App\Models\Producto;
use App\Support\Sitio;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Llamadas a la API REST de PayPal (sin SDK ni JavaScript de PayPal: todo desde el servidor).
 * Usa las credenciales de Panel → Ventas → PayPal. Si PayPal falla, lanza ErrorPayPal.
 */
class PayPal
{
    private ConfiguracionPagos $cfg;

    public function __construct()
    {
        $this->cfg = ConfiguracionPagos::actual();
    }

    public function modo(): string
    {
        return $this->cfg->modo;
    }

    /** Pide un token de acceso (en caché mientras vale). Sirve también para «Probar conexión». */
    public function token(): string
    {
        if (! $this->cfg->tieneCredenciales()) {
            throw new ErrorPayPal('Faltan el Client ID y el Secret de PayPal.');
        }
        $llave = 'paypal-token:'.$this->cfg->modo.':'.hash('sha256', $this->cfg->client_id.$this->cfg->client_secret);
        if ($token = Cache::get($llave)) {
            return $token;
        }

        try {
            $r = Http::asForm()->timeout(20)->withBasicAuth($this->cfg->client_id, $this->cfg->client_secret)
                ->post($this->cfg->urlApi().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException) {
            throw new ErrorPayPal('No se pudo conectar con PayPal. Intenta de nuevo en unos minutos.');
        }
        if (! $r->successful() || ! $r->json('access_token')) {
            throw new ErrorPayPal($r->status() === 401
                ? 'PayPal no aceptó las credenciales. Revisa el Client ID, el Secret y que el modo (pruebas o real) sea el de esas credenciales.'
                : 'PayPal no entregó el acceso ('.$r->status().').');
        }
        Cache::put($llave, $r->json('access_token'), max(60, (int) $r->json('expires_in', 3600) - 120));

        return $r->json('access_token');
    }

    /** Crea el producto en el catálogo de PayPal (lo piden los planes de suscripción). */
    public function crearProducto(Producto $producto): string
    {
        return $this->llamar('post', '/v1/catalogs/products', [
            'name' => Str::limit($producto->nombre, 120, ''),
            'description' => Str::limit($producto->descripcion ?: $producto->nombre, 250, ''),
            'type' => 'SERVICE',
            'category' => 'SOFTWARE',
        ])->json('id');
    }

    /** Crea un plan con el monto y periodo del precio. */
    public function crearPlan(Precio $precio, string $productoPaypal): string
    {
        [, , $unidad, $cada] = Precio::PERIODOS[$precio->periodo];

        return $this->llamar('post', '/v1/billing/plans', [
            'product_id' => $productoPaypal,
            'name' => Str::limit($precio->producto->nombre.' · '.$precio->periodoTexto(), 127, ''),
            'status' => 'ACTIVE',
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => $unidad, 'interval_count' => $cada],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0, // sin fin, hasta que se cancele
                'pricing_scheme' => ['fixed_price' => ['value' => number_format((float) $precio->monto, 2, '.', ''), 'currency_code' => $this->cfg->moneda]],
            ]],
            'payment_preferences' => ['auto_bill_outstanding' => true, 'payment_failure_threshold' => 2],
        ])->json('id');
    }

    /** Desactiva un plan viejo: nadie nuevo se suscribe a él, los suscriptores actuales siguen igual. */
    public function desactivarPlan(string $planId): void
    {
        $this->llamar('post', "/v1/billing/plans/{$planId}/deactivate");
    }

    /** @return array{id:string, aprobar:string} */
    public function crearSuscripcion(string $planId, string $referencia, string $nombre, string $correo, string $volver, string $cancelar): array
    {
        $r = $this->llamar('post', '/v1/billing/subscriptions', [
            'plan_id' => $planId,
            'custom_id' => $referencia,
            'subscriber' => ['name' => ['given_name' => Str::limit($nombre, 140, '')], 'email_address' => $correo],
            'application_context' => [
                'brand_name' => Str::limit(Sitio::nombre(), 127, ''),
                'locale' => 'es-XC',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $volver,
                'cancel_url' => $cancelar,
            ],
        ]);

        return ['id' => $r->json('id'), 'aprobar' => $this->enlace($r, ['approve'])];
    }

    public function suscripcion(string $id): array
    {
        return $this->llamar('get', '/v1/billing/subscriptions/'.rawurlencode($id))->json();
    }

    /** Cobros de una suscripción entre dos fechas. @return list<array> */
    public function transacciones(string $id, \DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        return $this->llamar('get', '/v1/billing/subscriptions/'.rawurlencode($id).'/transactions', [
            'start_time' => $desde->format('Y-m-d\TH:i:s\Z'),
            'end_time' => $hasta->format('Y-m-d\TH:i:s\Z'),
        ])->json('transactions') ?? [];
    }

    public function cancelarSuscripcion(string $id, string $motivo): void
    {
        $this->llamar('post', '/v1/billing/subscriptions/'.rawurlencode($id).'/cancel', ['reason' => Str::limit($motivo, 120, '')]);
    }

    /** @return array{id:string, aprobar:string} */
    public function crearOrden(string $monto, string $descripcion, string $referencia, string $volver, string $cancelar): array
    {
        $r = $this->llamar('post', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $referencia,
                'custom_id' => $referencia,
                'description' => Str::limit($descripcion, 127, ''),
                'amount' => ['currency_code' => $this->cfg->moneda, 'value' => $monto],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'brand_name' => Str::limit(Sitio::nombre(), 127, ''),
                'locale' => 'es-XC',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $volver,
                'cancel_url' => $cancelar,
            ]]],
        ]);

        return ['id' => $r->json('id'), 'aprobar' => $this->enlace($r, ['payer-action', 'approve'])];
    }

    public function orden(string $id): array
    {
        return $this->llamar('get', '/v2/checkout/orders/'.rawurlencode($id))->json();
    }

    /** Cobra una orden aprobada. Con la misma llave de PayPal: repetirla no cobra dos veces. */
    public function capturarOrden(string $id): array
    {
        return $this->llamar('post', '/v2/checkout/orders/'.rawurlencode($id).'/capture', null, ['PayPal-Request-Id' => 'captura-'.$id])->json();
    }

    /** Comprueba con PayPal que el aviso (webhook) es auténtico. */
    public function avisoAutentico(Request $request): bool
    {
        if (blank($this->cfg->webhook_id)) {
            return false;
        }
        try {
            $r = $this->llamar('post', '/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => $this->cfg->webhook_id,
                'webhook_event' => json_decode($request->getContent(), false),
            ]);
        } catch (ErrorPayPal) {
            return false;
        }

        return $r->json('verification_status') === 'SUCCESS';
    }

    private function llamar(string $metodo, string $ruta, ?array $datos = null, array $encabezados = []): Response
    {
        try {
            $r = $this->cliente()->withHeaders($encabezados)->{$metodo}($this->cfg->urlApi().$ruta, $datos ?? ($metodo === 'get' ? [] : (object) []));
        } catch (ConnectionException) {
            throw new ErrorPayPal('No se pudo conectar con PayPal. Intenta de nuevo en unos minutos.');
        }
        if (! $r->successful()) {
            $detalle = $r->json('details.0.issue') ?? $r->json('name') ?? (string) $r->status();
            report(new ErrorPayPal("PayPal {$metodo} {$ruta}: ".mb_substr($r->body(), 0, 1000)));

            throw new ErrorPayPal('PayPal rechazó la operación ('.$detalle.').', $detalle);
        }

        return $r;
    }

    private function cliente(): PendingRequest
    {
        return Http::withToken($this->token())->acceptJson()->asJson()->timeout(30)->withHeaders(['Prefer' => 'return=representation']);
    }

    /** @param list<string> $rels */
    private function enlace(Response $r, array $rels): string
    {
        $enlace = collect($r->json('links') ?? [])->first(fn ($l) => in_array($l['rel'] ?? '', $rels, true));
        if (! $enlace || ! str_starts_with((string) $enlace['href'], 'https://')) {
            throw new ErrorPayPal('PayPal no devolvió el enlace para pagar.');
        }

        return $enlace['href'];
    }
}
