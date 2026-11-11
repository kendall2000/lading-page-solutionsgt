@extends('layouts.admin', ['titulo' => 'Factura ' . $doc->numero_interno])

@section('contenido')
    @php $ventaReciente = session('venta_exitosa'); @endphp

    {{-- Modal de Confirmación y Decisión Post-Venta (Estilo POS / Ventas) --}}
    @if ($ventaReciente)
        <div class="modal fade show d-block" id="modalPostVenta" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content border-0 shadow-lg text-center p-4 rounded-4">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-soft-success text-success" style="width: 64px; height: 64px;">
                        <span class="fa-solid fa-circle-check fs-4"></span>
                    </div>
                    <h3 class="fw-bolder text-1100 mb-1">¡Factura Registrada!</h3>
                    <p class="text-600 fs--1 mb-4 font-monospace">
                        Comprobante <strong>{{ $ventaReciente['numero'] }}</strong> &middot;
                        <strong class="text-primary">{{ $ventaReciente['moneda'] === 'GTQ' ? 'Q' : '$' }}{{ number_format($ventaReciente['total'], 2) }} {{ $ventaReciente['moneda'] }}</strong>
                    </p>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.facturas.documento.descargar-pdf', $doc) }}?formato=factura" class="btn btn-primary py-2 fw-bold">
                            <span class="fa-solid fa-file-arrow-down me-2"></span>Descargar Factura en PDF
                        </a>
                        <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=ticket" target="_blank" class="btn btn-phoenix-primary py-2">
                            <span class="fa-solid fa-receipt me-2"></span>Imprimir Ticket Térmico
                        </a>
                        <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=factura" target="_blank" class="btn btn-phoenix-secondary py-2">
                            <span class="fa-solid fa-eye me-2"></span>Ver Comprobante en Pantalla
                        </a>
                        <a href="{{ route('admin.facturas.create') }}" class="btn btn-outline-secondary py-2 mt-1">
                            <span class="fa-solid fa-arrow-right me-1"></span>Seguir Facturando
                        </a>
                        <button type="button" class="btn btn-link text-muted fs--2 p-0 mt-1" onclick="document.getElementById('modalPostVenta').remove()">
                            Cerrar y ver detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.facturas.index') }}">Facturas</a></li>
                    <li class="breadcrumb-item active">{{ $doc->numero_interno }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h2 class="mb-0 text-1100">Factura {{ $doc->numero_interno }}</h2>
                @if ($doc->estado === 'certificado')
                    <span class="badge badge-phoenix badge-phoenix-success fs--1">
                        <span class="fa-solid fa-circle-check me-1"></span>Certificada SAT
                    </span>
                @elseif ($doc->estado === 'error')
                    <span class="badge badge-phoenix badge-phoenix-danger fs--1">
                        <span class="fa-solid fa-triangle-exclamation me-1"></span>Error de Certificación
                    </span>
                @else
                    <span class="badge badge-phoenix badge-phoenix-warning fs--1">
                        <span class="fa-solid fa-clock me-1"></span>Sin Certificar SAT
                    </span>
                @endif
                <span class="badge badge-phoenix badge-phoenix-primary fs--2">{{ $doc->moneda }}</span>
            </div>
            <p class="text-700 mb-0">Receptor: <strong>{{ $doc->nombre_receptor }}</strong> &middot; NIT: {{ $doc->nit_receptor ?: 'CF' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($doc->estado !== 'certificado')
                <form method="POST" action="{{ route('admin.facturas.certificar-sat', $doc) }}" onsubmit="return confirm('¿Enviar este documento a certificar ante la SAT / INFILE?')">
                    @csrf
                    <button type="submit" class="btn btn-phoenix-warning">
                        <span class="fa-solid fa-stamp me-1"></span>{{ $doc->estado === 'error' ? 'Reintentar Certificación' : 'Certificar en SAT' }}
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=factura" target="_blank" class="btn btn-phoenix-primary">
                <span class="fa-solid fa-file-invoice me-1"></span>Ver A4
            </a>
            <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=ticket" target="_blank" class="btn btn-phoenix-secondary">
                <span class="fa-solid fa-receipt me-1"></span>Ver Ticket
            </a>
            <a href="{{ route('admin.facturas.documento.descargar-pdf', $doc) }}?formato=factura" class="btn btn-phoenix-info">
                <span class="fa-solid fa-download me-1"></span>Descargar PDF
            </a>
            <a href="{{ route('admin.facturas.create') }}" class="btn btn-primary">
                <span class="fa-solid fa-plus me-1"></span>Nueva Factura
            </a>
        </div>
    </div>

    @if (session('exito'))
        <div class="alert alert-subtle-success alert-dismissible fade show mb-4 border-success" role="alert">
            <span class="fa-solid fa-circle-check me-2"></span>{{ session('exito') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error_sat') || $doc->error_mensaje)
        <div class="alert alert-subtle-danger alert-dismissible fade show mb-4 border-danger" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="fa-solid fa-triangle-exclamation text-danger fs-1 mt-1"></span>
                <div class="flex-grow-1">
                    <h6 class="text-danger fw-bold mb-1">Error de Certificación FEL (INFILE / SAT):</h6>
                    <div class="text-900 font-monospace fs--1 bg-white p-2 rounded border border-danger-subtle">
                        {{ session('error_sat') ?? $doc->error_mensaje }}
                    </div>
                    <div class="mt-2 fs--2 text-700">
                        Puede corregir los datos o credenciales y presionar el botón <strong>"Reintentar Certificación"</strong> arriba.
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($doc->estado === 'certificado')
        <div class="alert alert-subtle-success mb-4 border-success d-flex align-items-center justify-content-between">
            <div>
                <span class="fa-solid fa-stamp text-success me-2 fs-1"></span>
                <strong class="text-success">Documento Autorizado por SAT:</strong>
                <span class="font-monospace ms-2">UUID: {{ $doc->uuid_sat }}</span>
                <span class="ms-3 text-700">Serie: <strong>{{ $doc->serie_sat }}</strong></span>
                <span class="ms-3 text-700">Número: <strong>{{ $doc->numero_sat }}</strong></span>
            </div>
            <span class="fs--2 text-muted">Certificado el {{ $doc->fecha_certificacion ? $doc->fecha_certificacion->format('d/m/Y H:i') : '' }}</span>
        </div>
    @endif

    <div class="row g-4 mb-4">
        {{-- Detalle de ítems facturados (Lo que se consumió) --}}
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-body-highlight py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-1100"><span class="fa-solid fa-box-open me-2 text-primary"></span>Conceptos y Servicios Consumidos ({{ $doc->items->count() }})</h5>
                    <span class="fs--2 text-600">Régimen Pequeño Contribuyente</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body-secondary">
                                <tr>
                                    <th style="width: 50px;" class="ps-3 text-center">#</th>
                                    <th style="width: 100px;">Tipo SAT</th>
                                    <th>Descripción detallada del consumo</th>
                                    <th class="text-center" style="width: 90px;">Cant.</th>
                                    <th class="text-end" style="width: 130px;">P. Unitario</th>
                                    <th class="text-end pe-3" style="width: 130px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doc->items as $it)
                                    <tr>
                                        <td class="ps-3 text-center text-600 font-monospace">{{ $it->numero_linea }}</td>
                                        <td>
                                            <span class="badge {{ $it->tipo === 'B' ? 'badge-phoenix-primary' : 'badge-phoenix-info' }} fs--2">
                                                {{ $it->tipo === 'B' ? 'Bien (B)' : 'Servicio (S)' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-1000">{!! nl2br(e($it->descripcion)) !!}</div>
                                        </td>
                                        <td class="text-center font-monospace">{{ number_format($it->cantidad, 2) }}</td>
                                        <td class="text-end font-monospace">
                                            {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($it->precio_unitario, 2) }}
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-primary">
                                            {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($it->subtotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($doc->notas)
                    <div class="card-footer bg-light py-2">
                        <div class="fs--2 text-700"><strong>Observaciones:</strong> {{ $doc->notas }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Panel lateral: Datos del Cliente, Totales y QR --}}
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-body-highlight py-3 border-bottom">
                    <h5 class="mb-0 text-1100"><span class="fa-solid fa-address-card me-2 text-primary"></span>Datos del Receptor</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="text-600 fs--2 d-block">Razón Social:</span>
                        <div class="fw-bold text-1000">{{ $doc->nombre_receptor }}</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-600 fs--2 d-block">NIT / Documento:</span>
                        <div class="font-monospace fw-bold text-primary">{{ $doc->nit_receptor ?: 'CF' }}</div>
                    </div>
                    @if($doc->correo_receptor)
                        <div class="mb-2">
                            <span class="text-600 fs--2 d-block">Correo:</span>
                            <div>{{ $doc->correo_receptor }}</div>
                        </div>
                    @endif
                    @if($doc->direccion_receptor)
                        <div class="mb-2">
                            <span class="text-600 fs--2 d-block">Dirección:</span>
                            <div>{{ $doc->direccion_receptor }}</div>
                        </div>
                    @endif
                    <hr>
                    <div class="mb-2">
                        <span class="text-600 fs--2 d-block">Condición &amp; Método:</span>
                        <div>{{ ucfirst($doc->condicion_pago) }} &middot; {{ ucfirst($doc->metodo_pago) }}</div>
                    </div>
                    <div>
                        <span class="text-600 fs--2 d-block">Fecha de Emisión:</span>
                        <div>{{ $doc->fecha_emision ? $doc->fecha_emision->format('d/m/Y H:i') : date('d/m/Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Resumen Económico --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 bg-body-highlight rounded-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-700">Subtotal Ítems:</span>
                        <span class="font-monospace fw-bold">{{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($doc->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 fs--1 text-muted">
                        <span>Desglose IVA:</span>
                        <span>0.00 (Régimen PEQ)</span>
                    </div>
                    <div class="border-top my-2"></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-1 fw-bold text-1100">TOTAL:</span>
                        <span class="fs-3 fw-bolder text-primary font-monospace">
                            {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($doc->total, 2) }} {{ $doc->moneda }}
                        </span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=factura" target="_blank" class="btn btn-primary">
                            <span class="fa-solid fa-print me-1"></span>Imprimir Documento
                        </a>
                        <a href="{{ route('admin.facturas.create') }}" class="btn btn-outline-secondary">
                            <span class="fa-solid fa-plus me-1"></span>Seguir Facturando
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
