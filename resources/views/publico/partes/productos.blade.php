{{--
    Tarjetas de productos en venta con sus precios (Panel → Ventas → Productos y precios).
    Recibe $productos (con «precios» activos cargados). Con los cobros apagados, los botones llevan al contacto.
--}}
@php
    $cobrandoEnLinea = \App\Services\Pagos::cobrando();
    $monedaVenta = \App\Models\ConfiguracionPagos::actual()->moneda;
@endphp
<div class="row g-3 justify-content-center">
    @foreach ($productos as $producto)
        @php [$tipoProducto, $iconoProducto] = $producto->tipoInfo(); @endphp
        <div class="col-md-6 col-lg-4">
            <div class="pricing-card h-100">
                <div class="card bg-transparent h-100 {{ $producto->destacado ? 'border border-2 border-info rounded-4' : 'border-0' }}">
                    <div class="card-body p-5 p-lg-6 d-flex flex-column">
                        <span class="badge badge-phoenix badge-phoenix-{{ $producto->destacado ? 'info' : 'secondary' }} align-self-start mb-3">
                            <span class="fa-solid {{ $iconoProducto }} me-1"></span>{{ $producto->destacado ? 'Recomendado' : $tipoProducto }}
                        </span>
                        <h3 class="mb-2">{{ $producto->nombre }}</h3>
                        @if ($producto->descripcion)<p class="text-700 fs--1 mb-4">{{ $producto->descripcion }}</p>@endif

                        <div class="mb-4">
                            @foreach ($producto->precios as $precio)
                                @php $precio->setRelation('producto', $producto); @endphp
                                <div class="d-flex align-items-center justify-content-between gap-2 border border-300 rounded-3 px-3 py-2 mb-2">
                                    <div>
                                        <div class="fw-bold text-1000">{{ $precio->periodoTexto() }}</div>
                                        <div class="fs--1 text-700">
                                            @if ($precio->esDePorVida())
                                                {{ $precio->monto ? 'Desde '.$precio->montoTexto($monedaVenta) : 'Precio a consultar' }}
                                            @else
                                                <span class="fs-0 fw-bold text-900">{{ $precio->montoTexto($monedaVenta) }}</span> {{ $precio->esRecurrente() ? $precio->sufijo() : '' }}
                                            @endif
                                        </div>
                                    </div>
                                    @if ($precio->esDePorVida())
                                        @php $enlaceAsesor = $producto->enlaceAsesor(); @endphp
                                        <a class="btn btn-sm btn-phoenix-primary text-nowrap" href="{{ $enlaceAsesor }}" @if (str_starts_with($enlaceAsesor, 'https://wa.me')) target="_blank" rel="noopener" @endif>
                                            <span class="fa-solid fa-headset me-1"></span>Hablar con un asesor
                                        </a>
                                    @elseif ($cobrandoEnLinea && $precio->sePuedeComprar())
                                        <a class="btn btn-sm btn-primary text-nowrap" href="{{ route('cuenta.comprar', $precio) }}">{{ $precio->esRecurrente() ? 'Suscribirme' : 'Comprar' }}</a>
                                    @else
                                        <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ \App\Support\Sitio::enlaceContacto() }}">Me interesa</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($producto->listaIncluye())
                            <h5 class="mb-3">Incluye</h5>
                            <ul class="fa-ul ps-4 m-0 pricing">
                                @foreach ($producto->listaIncluye() as $item)
                                    <li class="d-flex align-items-center mb-2"><span class="fa-li"><span class="fas fa-check text-primary"></span></span><p class="mb-0 fs--1">{{ $item }}</p></li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($producto->sistema && ! request()->routeIs('sistema'))
                            <a class="fs--1 fw-bold mt-auto pt-3" href="{{ route('sistema', $producto->sistema->slug) }}">Ver el sistema<span class="fa-solid fa-angle-right ms-1"></span></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@if ($cobrandoEnLinea)
    <p class="text-center fs--1 text-700 mt-4 mb-0">
        <span class="fa-brands fa-paypal me-1"></span>Pago seguro con PayPal (tarjeta o saldo). Las suscripciones se cobran solas y las cancelas cuando quieras desde «Mi cuenta».
    </p>
@endif
