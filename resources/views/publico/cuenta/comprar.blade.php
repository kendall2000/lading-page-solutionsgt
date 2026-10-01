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

                    <form method="POST" action="{{ route('cuenta.comprar.pagar', $precio) }}">
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
                        <button class="btn btn-primary btn-lg w-100" type="submit"><span class="fa-brands fa-paypal me-2"></span>Continuar a PayPal</button>
                    </form>
                    <p class="text-center fs--1 text-600 mt-3 mb-0">Puedes pagar con tu cuenta PayPal o con tarjeta. No guardamos datos de tu tarjeta.</p>
                </div>
            </div>
            <p class="text-center mt-3"><a class="fs--1" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('inicio') }}"><span class="fa-solid fa-angle-left me-1"></span>Volver</a></p>
        </div>
    </div>
@endsection
