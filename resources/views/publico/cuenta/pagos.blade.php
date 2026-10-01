@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Pagos'])

@section('portal')
    <h4 class="mb-3">Mis suscripciones</h4>
    @if ($suscripciones->isEmpty())
        <div class="card mb-5"><div class="card-body text-center py-5">
            <span class="fa-solid fa-credit-card fs-3 text-400 mb-3 d-block"></span>
            <p class="text-700 mb-3">No tienes suscripciones. Cuando te suscribas a un sistema o servicio, aquí verás cuándo se cobra y podrás cancelarla.</p>
            <a class="btn btn-primary" href="{{ \App\Support\Sitio::enlaceBloque('tienda') }}">Ver precios</a>
        </div></div>
    @else
        <div class="row g-3 mb-5">
            @foreach ($suscripciones as $s)
                @php [$estadoTexto, $estadoColor] = $s->estadoInfo(); @endphp
                <div class="col-md-6">
                    <div class="card h-100"><div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="mb-0">{{ $s->descripcion }}</h5>
                            <span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span>
                        </div>
                        <p class="fs-1 fw-bold text-1000 mb-2">{{ $s->montoTexto() }}</p>
                        <ul class="list-unstyled fs--1 text-700 mb-3">
                            @if ($s->estado === 'activa' && $s->siguiente_cobro)
                                <li><span class="fa-solid fa-calendar me-2"></span>Próximo cobro: {{ $s->siguiente_cobro->format('d/m/Y') }}</li>
                            @endif
                            @if ($s->estado === 'suspendida')
                                <li class="text-warning"><span class="fa-solid fa-triangle-exclamation me-2"></span>PayPal no pudo cobrar. Revisa tu método de pago en PayPal.</li>
                            @endif
                            @if ($s->contrato?->hasta)
                                <li><span class="fa-solid fa-unlock me-2"></span>Acceso hasta: {{ $s->contrato->hasta->format('d/m/Y') }}</li>
                            @endif
                            <li><span class="fa-solid fa-hashtag me-2"></span>{{ $s->paypal_id }}</li>
                        </ul>
                        @if ($s->sePuedeCancelar())
                            <form class="mt-auto" method="POST" action="{{ route('cuenta.pagos.cancelar', $s) }}"
                                  onsubmit="return confirm('¿Cancelar tu suscripción? Ya no se te cobrará y podrás usarlo hasta el final del periodo pagado.')">
                                @csrf
                                <button class="btn btn-sm btn-phoenix-danger" type="submit">Cancelar suscripción</button>
                            </form>
                        @endif
                    </div></div>
                </div>
            @endforeach
        </div>
    @endif

    <h4 class="mb-3">Historial de pagos</h4>
    <div class="card"><div class="card-body">
        @if ($pagos->isEmpty())
            <p class="text-700 mb-0">Todavía no hay pagos.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th class="ps-0">Fecha</th><th>Concepto</th><th class="text-end">Monto</th><th>Estado</th><th class="pe-0">Referencia</th></tr></thead>
                    <tbody>
                        @foreach ($pagos as $p)
                            @php [$estadoTexto, $estadoColor] = $p->estadoInfo(); @endphp
                            <tr>
                                <td class="ps-0 text-nowrap">{{ $p->pagado_en?->format('d/m/Y') }}</td>
                                <td>{{ $p->descripcion }}</td>
                                <td class="text-end text-nowrap fw-semi-bold">{{ $p->montoTexto() }}</td>
                                <td><span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span></td>
                                <td class="pe-0 text-600">{{ $p->paypal_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $pagos->links() }}</div>
        @endif
    </div></div>
@endsection
