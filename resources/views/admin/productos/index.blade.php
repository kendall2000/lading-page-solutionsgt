@extends('layouts.admin', ['titulo' => 'Productos y precios'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Productos y precios</h2>
            <p class="text-700 mb-0">Lo que vendes en el sitio: software por suscripción, servicios u otros, cada uno con sus precios por periodo.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.productos.create') }}"><span class="fa-solid fa-plus me-2"></span>Nuevo producto</a>
    </div>

    @unless ($pagos->cobrando())
        <div class="alert alert-soft-warning d-flex align-items-center gap-3 fs--1" role="alert">
            <span class="fa-solid fa-circle-info fs-1"></span>
            <div class="flex-1">
                Los cobros en línea están <strong>apagados</strong>{{ $pagos->tieneCredenciales() ? '' : ' (falta conectar PayPal)' }}.
                El sitio muestra los precios con el botón «Me interesa» (formulario de contacto).
            </div>
            <a class="btn btn-sm btn-warning" href="{{ route('admin.ventas.index', ['ver' => 'paypal']) }}">Configurar PayPal</a>
        </div>
    @endunless

    <div class="row g-3">
        @forelse ($productos as $p)
            @php [$tipoTexto, $tipoIcono] = $p->tipoInfo(); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 {{ $p->destacado ? 'border border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h4 class="mb-0">{{ $p->nombre }}</h4>
                            <span class="badge badge-phoenix badge-phoenix-{{ $p->activo ? 'success' : 'secondary' }}">{{ $p->activo ? 'En venta' : 'Desactivado' }}</span>
                        </div>
                        <p class="fs--1 text-700 mb-3">
                            <span class="fa-solid {{ $tipoIcono }} me-1"></span>{{ $tipoTexto }}{{ $p->sistema ? ' · '.$p->sistema->nombre : '' }}
                            @if ($p->activas) · <strong>{{ $p->activas }}</strong> {{ $p->activas === 1 ? 'suscripción activa' : 'suscripciones activas' }}@endif
                        </p>
                        <ul class="list-unstyled fs--1 mb-3">
                            @forelse ($p->precios as $precio)
                                <li class="d-flex justify-content-between border-bottom border-200 py-1 {{ $precio->activo ? '' : 'text-500 text-decoration-line-through' }}">
                                    <span>{{ $precio->periodoTexto() }}</span>
                                    <span class="fw-semi-bold">{{ $precio->esDePorVida() ? ($precio->monto ? 'Desde '.$precio->montoTexto($pagos->moneda) : 'Con asesor') : $precio->montoTexto($pagos->moneda) }}</span>
                                </li>
                            @empty
                                <li class="text-600">Sin precios todavía.</li>
                            @endforelse
                        </ul>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="fs--2 text-600">Orden {{ $p->orden }}{{ $p->destacado ? ' · Destacado' : '' }}</span>
                            <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.productos.edit', $p) }}">Editar</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-700">
                    Todavía no hay productos. Crea uno para cada cosa que vendas: p. ej. «Sistema de restaurante» con precio mensual y anual,
                    o «Instalación y capacitación» con pago único.
                </div></div>
            </div>
        @endforelse
    </div>
@endsection
