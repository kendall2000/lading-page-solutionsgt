<?php

namespace App\Services;

use App\Exceptions\ErrorPayPal;
use App\Models\ConfiguracionPagos;
use App\Models\Contrato;
use App\Models\Cuenta;
use App\Models\Pago;
use App\Models\Precio;
use App\Models\Suscripcion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ventas con PayPal: iniciar la compra, confirmar al volver, sincronizar suscripciones,
 * dar acceso (contrato) y avisar por correo.
 *
 * Regla de oro: nunca se cree lo que dice el navegador ni el aviso (webhook); todo estado se
 * vuelve a consultar a PayPal con nuestras credenciales antes de guardarlo.
 */
class Pagos
{
    public function __construct(private readonly PayPal $paypal, private readonly Correos $correos) {}

    public static function cobrando(): bool
    {
        return ConfiguracionPagos::actual()->cobrando();
    }

    /** Crea la suscripción u orden en PayPal y devuelve el enlace donde el cliente aprueba el pago. */
    public function iniciar(Precio $precio, Cuenta $cuenta): string
    {
        if (! self::cobrando()) {
            throw new ErrorPayPal('Los pagos en línea no están disponibles en este momento.');
        }
        if (! $precio->sePuedeComprar()) {
            throw new ErrorPayPal('Este precio ya no está a la venta.');
        }
        $producto = $precio->producto;
        $descripcion = $producto->nombre.' · '.$precio->periodoTexto();
        $base = [
            'cuenta_id' => $cuenta->id, 'producto_id' => $producto->id, 'precio_id' => $precio->id, 'paypal_modo' => $this->paypal->modo(),
            'descripcion' => mb_substr($descripcion, 0, 200), 'periodo' => $precio->periodo, 'monto' => $precio->monto,
            'moneda' => ConfiguracionPagos::actual()->moneda, 'correo' => $cuenta->correo,
        ];

        if ($precio->esRecurrente()) {
            $yaTiene = Suscripcion::query()->where('cuenta_id', $cuenta->id)->where('producto_id', $producto->id)
                ->whereIn('estado', ['activa', 'suspendida'])->exists();
            if ($yaTiene) {
                throw new ErrorPayPal('Ya tienes una suscripción a este producto. La encuentras en «Mi cuenta» → Pagos.');
            }
            $r = $this->paypal->crearSuscripcion($this->plan($precio), "cuenta-{$cuenta->id}", $cuenta->nombre, $cuenta->correo,
                route('cuenta.pagos.suscripcion'), route('cuenta.pagos.cancelado'));
            Suscripcion::query()->create($base + ['paypal_id' => $r['id'], 'estado' => 'pendiente']);

            return $r['aprobar'];
        }

        $r = $this->paypal->crearOrden(number_format((float) $precio->monto, 2, '.', ''), $descripcion, "cuenta-{$cuenta->id}",
            route('cuenta.pagos.orden'), route('cuenta.pagos.cancelado'));
        Pago::query()->create($base + ['paypal_orden_id' => $r['id'], 'estado' => 'pendiente']);

        return $r['aprobar'];
    }

    /**
     * Plan de PayPal del precio. Se crea la primera vez que alguien compra; si cambiaste el monto
     * (o el modo pruebas/real), se crea otro y los suscriptores anteriores siguen con su precio.
     */
    public function plan(Precio $precio): string
    {
        $modo = $this->paypal->modo();
        if ($precio->paypal_plan_id && $precio->paypal_modo === $modo) {
            return $precio->paypal_plan_id;
        }
        $producto = $precio->producto;
        if (! $producto->paypal_id || $producto->paypal_modo !== $modo) {
            $producto->forceFill(['paypal_id' => $this->paypal->crearProducto($producto), 'paypal_modo' => $modo])->saveQuietly();
        }
        $plan = $this->paypal->crearPlan($precio, $producto->paypal_id);
        $precio->forceFill(['paypal_plan_id' => $plan, 'paypal_modo' => $modo])->saveQuietly();

        return $plan;
    }

    /** Al cambiar el monto: el plan viejo deja de aceptar suscriptores nuevos (los actuales no cambian). */
    public function retirarPlan(Precio $precio): void
    {
        if ($precio->paypal_plan_id && $precio->paypal_modo === $this->paypal->modo()) {
            rescue(fn () => $this->paypal->desactivarPlan($precio->paypal_plan_id), null, true);
        }
        $precio->forceFill(['paypal_plan_id' => null, 'paypal_modo' => null])->saveQuietly();
    }

    // ── Suscripciones ───────────────────────────────────────────────────────

    /** Trae de PayPal el estado y los cobros de la suscripción y actualiza contrato y avisos. */
    public function sincronizar(Suscripcion $s): Suscripcion
    {
        $datos = $this->paypal->suscripcion($s->paypal_id);
        $antes = $s->estado;
        $estado = match ($datos['status'] ?? '') {
            'ACTIVE' => 'activa',
            'SUSPENDED' => 'suspendida',
            'CANCELLED' => 'cancelada',
            'EXPIRED' => 'vencida',
            default => 'pendiente', // APPROVAL_PENDING, APPROVED
        };
        $siguiente = data_get($datos, 'billing_info.next_billing_time');

        $s->fill([
            'estado' => $estado,
            'siguiente_cobro' => $siguiente && $estado === 'activa' ? Carbon::parse($siguiente) : null,
            'sincronizada_en' => now(),
        ]);
        if ($estado === 'activa' && ! $s->activada_en) {
            $s->activada_en = now();
        }
        if (in_array($estado, ['cancelada', 'vencida'], true) && ! $s->cancelada_en) {
            $s->cancelada_en = now();
        }
        $s->save();

        $nuevos = $this->registrarCobros($s);
        if ($estado === 'activa') {
            $this->darAcceso($s);
        }

        if ($antes === 'pendiente' && $estado === 'activa') {
            $this->avisarVenta($s->cuenta?->nombre ?? $s->correo, $s->correo, $s->descripcion, $s->periodo, $s->montoTexto());
        }
        foreach ($nuevos as $pago) {
            $this->enviarComprobante($pago);
        }
        if (in_array($antes, ['activa', 'pendiente'], true) && in_array($estado, ['suspendida', 'cancelada', 'vencida'], true) && $s->activada_en) {
            $this->correos->avisar('suscripcion_cancelada', [
                'nombre' => $s->cuenta?->nombre ?? $s->correo, 'correo' => $s->correo, 'producto' => $s->descripcion,
                'periodo' => Precio::nombrePeriodo($s->periodo), 'monto' => $s->montoTexto(), 'estado' => mb_strtolower($s->estadoInfo()[0]),
                'hasta' => $s->contrato?->hasta?->format('d/m/Y') ?? '—', 'enlace' => route('admin.ventas.index'),
            ]);
        }

        return $s;
    }

    /** Cancela en PayPal. El acceso sigue hasta el final del periodo ya pagado. */
    public function cancelar(Suscripcion $s, string $motivo): Suscripcion
    {
        if ($s->sePuedeCancelar()) {
            $this->paypal->cancelarSuscripcion($s->paypal_id, $motivo);
        }

        return $this->sincronizar($s);
    }

    /** Guarda los cobros que PayPal tiene de la suscripción. @return list<Pago> los que acaban de quedar pagados */
    private function registrarCobros(Suscripcion $s): array
    {
        if ($s->estado === 'pendiente') {
            return [];
        }
        $desde = Carbon::parse($s->pagos()->max('pagado_en') ?? $s->created_at)->subDays(2);
        $nuevos = [];
        foreach ($this->paypal->transacciones($s->paypal_id, $desde, now()) as $t) {
            if (blank($t['id'] ?? null)) {
                continue;
            }
            $estado = match ($t['status'] ?? '') {
                'COMPLETED' => 'completado',
                'REFUNDED', 'PARTIALLY_REFUNDED' => 'reembolsado',
                'DECLINED', 'FAILED' => 'fallido',
                default => 'pendiente',
            };
            $pago = Pago::query()->firstOrNew(['paypal_id' => $t['id']]);
            $eraPagado = $pago->exists && $pago->estado !== 'pendiente';
            $pago->fill([
                'cuenta_id' => $s->cuenta_id, 'producto_id' => $s->producto_id, 'precio_id' => $s->precio_id, 'suscripcion_id' => $s->id,
                'paypal_modo' => $s->paypal_modo, 'estado' => $estado, 'descripcion' => $s->descripcion, 'periodo' => $s->periodo,
                'monto' => data_get($t, 'amount_with_breakdown.gross_amount.value', $s->monto),
                'moneda' => data_get($t, 'amount_with_breakdown.gross_amount.currency_code', $s->moneda),
                'correo' => $s->correo, 'pagado_en' => isset($t['time']) ? Carbon::parse($t['time']) : now(),
            ])->save();
            if ($estado === 'completado' && ! $eraPagado) {
                $nuevos[] = $pago;
            }
        }

        return $nuevos;
    }

    /**
     * Si el producto es un sistema: crea el contrato (o usa el que ya tenía con ese sistema) y lo
     * deja vigente hasta la próxima fecha de cobro + los días de gracia. Nunca acorta lo ya pagado.
     */
    private function darAcceso(Suscripcion $s): void
    {
        $sistemaId = $s->producto?->sistema_id;
        if (! $sistemaId || ! $s->cuenta_id) {
            return;
        }
        $base = $s->siguiente_cobro ?? now()->add(match ($s->periodo) {
            'semanal' => '1 week', 'trimestral' => '3 months', 'anual' => '1 year', default => '1 month',
        });
        $hasta = $base->copy()->addDays(ConfiguracionPagos::actual()->dias_gracia)->startOfDay();

        DB::transaction(function () use ($s, $sistemaId, $hasta) {
            $contrato = $s->contrato
                ?? Contrato::query()->where('cuenta_id', $s->cuenta_id)->where('sistema_id', $sistemaId)->latest('id')->first()
                ?? new Contrato(['sistema_id' => $sistemaId, 'cuenta_id' => $s->cuenta_id, 'desde' => today()]);
            $contrato->plan = mb_substr('Suscripción '.mb_strtolower(Precio::nombrePeriodo($s->periodo)).' (PayPal)', 0, 120);
            if ($contrato->exists && $contrato->hasta === null) {
                $contrato->save(); // ya tenía acceso sin vencimiento: no se le pone fecha
            } else {
                $contrato->hasta = $contrato->hasta && $contrato->hasta->greaterThan($hasta) ? $contrato->hasta : $hasta;
                $contrato->save();
            }
            if ($s->contrato_id !== $contrato->id) {
                $s->forceFill(['contrato_id' => $contrato->id])->saveQuietly();
            }
        });
    }

    // ── Pagos únicos (órdenes) ──────────────────────────────────────────────

    /** El cliente aprobó el pago en PayPal: se cobra y se guarda. */
    public function confirmarOrden(Pago $pago): Pago
    {
        if ($pago->estado !== 'pendiente') {
            return $pago;
        }
        try {
            $orden = $this->paypal->capturarOrden($pago->paypal_orden_id);
        } catch (ErrorPayPal $e) {
            if ($e->codigo !== 'ORDER_ALREADY_CAPTURED') {
                throw $e;
            }
            $orden = $this->paypal->orden($pago->paypal_orden_id);
        }

        return $this->aplicarOrden($pago, $orden);
    }

    /** Vuelve a consultar la orden (avisos de PayPal y tarea diaria). Si quedó aprobada sin cobrar, la cobra. */
    public function revisarOrden(Pago $pago): Pago
    {
        $orden = $this->paypal->orden($pago->paypal_orden_id);
        if (($orden['status'] ?? '') === 'APPROVED' && $pago->estado === 'pendiente') {
            return $this->confirmarOrden($pago);
        }

        return $this->aplicarOrden($pago, $orden);
    }

    private function aplicarOrden(Pago $pago, array $orden): Pago
    {
        $captura = data_get($orden, 'purchase_units.0.payments.captures.0');
        if (! $captura) {
            return $pago;
        }
        $antes = $pago->estado;
        $estado = match ($captura['status'] ?? '') {
            'COMPLETED' => 'completado',
            'REFUNDED', 'PARTIALLY_REFUNDED' => 'reembolsado',
            'DECLINED', 'FAILED' => 'fallido',
            default => 'pendiente',
        };
        // Lo cobrado tiene que ser lo que se pidió (moneda y monto).
        $cobrado = (string) data_get($captura, 'amount.value');
        if ($estado === 'completado' && (abs((float) $cobrado - (float) $pago->monto) > 0.004 || data_get($captura, 'amount.currency_code') !== $pago->moneda)) {
            report(new ErrorPayPal("Pago {$pago->id}: PayPal cobró {$cobrado} y se esperaba {$pago->monto}."));
            $estado = 'fallido';
        }
        $pago->fill([
            'paypal_id' => $captura['id'] ?? $pago->paypal_id,
            'estado' => $estado,
            'pagado_en' => $pago->pagado_en ?? (isset($captura['create_time']) ? Carbon::parse($captura['create_time']) : now()),
        ])->save();

        if ($antes === 'pendiente' && $estado === 'completado') {
            $this->enviarComprobante($pago);
            $this->avisarVenta($pago->cuenta?->nombre ?? $pago->correo, $pago->correo, $pago->descripcion, $pago->periodo, $pago->montoTexto());
        }

        return $pago;
    }

    // ── Avisos de PayPal (webhook) ──────────────────────────────────────────

    /** Procesa un aviso: solo dice «qué revisar»; el estado real se vuelve a pedir a PayPal. */
    public function procesarAviso(array $evento): void
    {
        $tipo = (string) ($evento['event_type'] ?? '');
        $r = $evento['resource'] ?? [];

        $suscripcionId = match (true) {
            str_starts_with($tipo, 'BILLING.SUBSCRIPTION.') => $r['id'] ?? null,
            str_starts_with($tipo, 'PAYMENT.SALE.') => $r['billing_agreement_id'] ?? null,
            default => null,
        };
        if ($suscripcionId && $s = Suscripcion::query()->where('paypal_id', $suscripcionId)->first()) {
            $this->sincronizar($s);

            return;
        }

        if (str_starts_with($tipo, 'PAYMENT.CAPTURE.')) {
            // Captura: trae la orden. Reembolso: el enlace «up» apunta a la captura.
            $orden = data_get($r, 'supplementary_data.related_ids.order_id');
            $captura = $tipo === 'PAYMENT.CAPTURE.REFUNDED'
                ? collect($r['links'] ?? [])->where('rel', 'up')->map(fn ($l) => basename(parse_url($l['href'] ?? '', PHP_URL_PATH) ?: ''))->first()
                : ($r['id'] ?? null);
            if (! $orden && ! $captura) {
                return;
            }
            $pago = Pago::query()->whereNotNull('paypal_orden_id')
                ->where(fn ($q) => $q->when($orden, fn ($q) => $q->where('paypal_orden_id', $orden))
                    ->when($captura, fn ($q) => $q->orWhere('paypal_id', $captura)))
                ->first();
            if ($pago) {
                $this->revisarOrden($pago);
            }
        }
    }

    // ── Tarea diaria ────────────────────────────────────────────────────────

    /**
     * Respaldo de los avisos: sincroniza suscripciones con cobro cercano (o sin revisar en una semana),
     * revisa pagos pendientes y borra intentos abandonados (no aprobados en 3 días).
     *
     * @return array{revisadas:int, errores:int, borradas:int}
     */
    public function sincronizarTodo(): array
    {
        $revisadas = $errores = $borradas = 0;

        $suscripciones = Suscripcion::query()
            ->where(fn ($q) => $q->whereIn('estado', ['activa', 'suspendida'])
                ->where(fn ($q) => $q->where('siguiente_cobro', '<=', now()->addDay())->orWhereNull('sincronizada_en')
                    ->orWhere('sincronizada_en', '<', now()->subWeek())))
            ->orWhere(fn ($q) => $q->where('estado', 'pendiente')->where('created_at', '<', now()->subHour()))
            ->get();
        foreach ($suscripciones as $s) {
            try {
                $this->sincronizar($s);
                $revisadas++;
                if ($s->estado === 'pendiente' && $s->created_at->lt(now()->subDays(3))) {
                    $s->delete();
                    $borradas++;
                }
            } catch (ErrorPayPal $e) {
                $errores++;
                if ($s->estado === 'pendiente' && $s->created_at->lt(now()->subDays(3))) {
                    $s->delete(); // PayPal ya no la conoce (nunca se aprobó)
                    $borradas++;
                }
            }
        }

        $pendientes = Pago::query()->where('estado', 'pendiente')->whereNotNull('paypal_orden_id')->where('created_at', '<', now()->subMinutes(15))->get();
        foreach ($pendientes as $pago) {
            try {
                $this->revisarOrden($pago);
                $revisadas++;
            } catch (ErrorPayPal) {
                $errores++;
            }
            if ($pago->estado === 'pendiente' && $pago->paypal_id === null && $pago->created_at->lt(now()->subDays(3))) {
                $pago->delete();
                $borradas++;
            }
        }

        return ['revisadas' => $revisadas, 'errores' => $errores, 'borradas' => $borradas];
    }

    // ── Correos ─────────────────────────────────────────────────────────────

    private function enviarComprobante(Pago $pago): void
    {
        $this->correos->enviar('pago_recibido', $pago->correo, [
            'nombre' => $pago->cuenta?->nombre ?? $pago->correo, 'producto' => $pago->descripcion, 'periodo' => Precio::nombrePeriodo($pago->periodo),
            'monto' => $pago->montoTexto(), 'fecha' => ($pago->pagado_en ?? now())->format('d/m/Y H:i'), 'referencia' => (string) $pago->paypal_id,
            'enlace' => route('cuenta.pagos'),
        ]);
    }

    private function avisarVenta(string $nombre, string $correo, string $producto, string $periodo, string $monto): void
    {
        $this->correos->avisar('nueva_venta', [
            'nombre' => $nombre, 'correo' => $correo, 'producto' => $producto, 'periodo' => Precio::nombrePeriodo($periodo),
            'monto' => $monto, 'enlace' => route('admin.ventas.index'),
        ], $correo);
    }
}
