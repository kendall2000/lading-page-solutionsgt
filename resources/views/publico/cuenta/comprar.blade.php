@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Confirmar compra'])

@section('portal')
    @php [$tipoProducto, $iconoProducto] = $producto->tipoInfo(); @endphp
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-body p-4 p-sm-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="sgt-icono mb-0"><span class="fa-solid {{ $iconoProducto }}"></span></span>
                        <div>
                            <p class="fs--1 text-700 mb-0">{{ $tipoProducto }}</p>
                            <h3 class="mb-0">{{ $producto->nombre }}</h3>
                        </div>
                    </div>

                    <div class="border border-300 rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semi-bold">{{ $precio->periodoTexto() }}</span>
                            <span class="fs-1 fw-bold text-1000">{{ $precio->montoTexto() }} <span class="fs--1 fw-normal text-700">{{ $precio->esRecurrente() ? $precio->sufijo() : '' }}</span></span>
                        </div>
                        @if ($producto->descripcion)<p class="fs--1 text-700 mb-0 mt-2">{{ $producto->descripcion }}</p>@endif
                    </div>

                    <ul class="fa-ul ps-4 fs--1 text-800 mb-4">
                        @if ($precio->esRecurrente())
                            <li class="mb-2"><span class="fa-li"><span class="fa-solid fa-rotate text-primary"></span></span>PayPal te cobrará {{ $precio->montoTexto() }} {{ $precio->sufijo() }} de forma automática, empezando hoy.</li>
                            <li class="mb-2"><span class="fa-li"><span class="fa-solid fa-ban text-primary"></span></span>Cancelas cuando quieras desde «Mi cuenta» → Pagos; no hay más cobros y lo sigues usando hasta el final del periodo pagado.</li>
                            @if ($producto->sistema)
                                <li class="mb-2"><span class="fa-li"><span class="fa-solid fa-laptop-code text-primary"></span></span>{{ $producto->sistema->nombre }} aparecerá en «Mis sistemas» y te enviaremos tu acceso.</li>
                            @endif
                        @else
                            <li class="mb-2"><span class="fa-li"><span class="fa-solid fa-check text-primary"></span></span>Es un solo pago de {{ $precio->montoTexto() }}; no se repite.</li>
                            <li class="mb-2"><span class="fa-li"><span class="fa-solid fa-headset text-primary"></span></span>Después del pago te contactamos para coordinar el servicio.</li>
                        @endif
                        <li class="mb-2"><span class="fa-li"><span class="fa-solid fa-envelope text-primary"></span></span>El comprobante llega a {{ $cuenta->correo }}.</li>
                    </ul>

                    {{-- La casilla vale para los botones de PayPal y para el envío directo a PayPal (respaldo). --}}
                    <form id="formCompra" method="POST" action="{{ route('cuenta.comprar.pagar', $precio) }}">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" id="acepto" name="acepto" type="checkbox" value="1" required />
                            <label class="form-check-label fs--1" for="acepto">
                                Estoy de acuerdo con {{ $precio->esRecurrente() ? 'el cobro automático '.mb_strtolower($precio->periodoTexto()) : 'el cobro' }} de {{ $precio->montoTexto() }}.
                                @if ($enlacePrivacidad = \App\Support\Sitio::enlacePrivacidad())
                                    <a href="{{ $enlacePrivacidad }}" target="_blank" rel="noopener">Aviso de privacidad</a>.
                                @endif
                            </label>
                        </div>
                        <div class="alert alert-subtle-warning fs--1 py-2 d-none" id="avisoPago" role="alert"></div>
                        <div id="botonesPayPal" class="mb-2"></div>
                        <p class="text-center fs--1 text-600 mb-2" id="cargandoPayPal"><span class="fa-solid fa-spinner fa-spin me-1"></span>Cargando las formas de pago…</p>
                        <div class="text-center">
                            <button class="btn btn-link fs--1 p-0" id="respaldoPayPal" type="submit"><span class="fa-brands fa-paypal me-1"></span>Continuar en la página de PayPal</button>
                        </div>
                    </form>
                    <p class="text-center fs--1 text-600 mt-3 mb-0">Paga con tu cuenta PayPal o con «Tarjeta de débito o crédito», sin crear cuenta. La tarjeta la procesa PayPal: nosotros no vemos ni guardamos sus datos.</p>
                </div>
            </div>
            <p class="text-center mt-3"><a class="fs--1" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('inicio') }}"><span class="fa-solid fa-angle-left me-1"></span>Volver</a></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ \App\Services\PayPal::urlBotones($precio->esRecurrente()) }}"></script>
    <script>
        // Botones oficiales de PayPal: «PayPal» y «Tarjeta de débito o crédito». El cobro se crea en nuestro servidor
        // (urlIniciar) y, al aprobarlo, se confirma con PayPal en la página de regreso: aquí no se decide nada.
        (function () {
            var esSuscripcion = @json($precio->esRecurrente());
            var urlIniciar = @json(route('cuenta.comprar.iniciar', $precio));
            var urlSuscripcion = @json(route('cuenta.pagos.suscripcion'));
            var urlOrden = @json(route('cuenta.pagos.orden'));
            var token = document.querySelector('meta[name="csrf-token"]').content;
            var acepto = document.getElementById('acepto');
            var aviso = document.getElementById('avisoPago');
            var cargando = document.getElementById('cargandoPayPal');

            function mostrar(texto) {
                aviso.textContent = texto || '';
                aviso.classList.toggle('d-none', !texto);
            }

            if (!window.paypal || !window.paypal.Buttons) {
                cargando.textContent = 'No se pudieron cargar los botones de PayPal. Usa «Continuar en la página de PayPal».';
                return;
            }

            function crear() {
                return fetch(urlIniciar, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ acepto: '1' })
                }).then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (d) {
                        if (!r.ok || !d.id) { throw new Error(d.message || 'No se pudo iniciar el pago. Intenta de nuevo.'); }
                        return d.id;
                    });
                }).catch(function (e) { mostrar(e.message); throw e; });
            }

            var opciones = {
                style: { layout: 'vertical', shape: 'pill', label: esSuscripcion ? 'subscribe' : 'pay' },
                onClick: function (datos, acciones) {
                    if (!acepto.checked) {
                        mostrar('Marca que estás de acuerdo con el cobro para continuar.');
                        acepto.focus();
                        return acciones.reject();
                    }
                    mostrar('');
                    return acciones.resolve();
                },
                onApprove: function (datos) {
                    cargando.innerHTML = '<span class="fa-solid fa-spinner fa-spin me-1"></span>Confirmando tu pago con PayPal…';
                    cargando.classList.remove('d-none');
                    window.location.href = esSuscripcion
                        ? urlSuscripcion + '?subscription_id=' + encodeURIComponent(datos.subscriptionID)
                        : urlOrden + '?token=' + encodeURIComponent(datos.orderID);
                },
                onCancel: function () { mostrar('Cancelaste el pago: no se te cobró nada.'); },
                onError: function () {
                    if (aviso.classList.contains('d-none')) {
                        mostrar('PayPal no pudo procesar el pago. Intenta de nuevo o usa «Continuar en la página de PayPal».');
                    }
                }
            };
            opciones[esSuscripcion ? 'createSubscription' : 'createOrder'] = crear;

            window.paypal.Buttons(opciones).render('#botonesPayPal').then(function () {
                cargando.classList.add('d-none');
            }).catch(function () {
                cargando.textContent = 'No se pudieron cargar los botones de PayPal. Usa «Continuar en la página de PayPal».';
            });
        })();
    </script>
@endpush
