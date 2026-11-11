@extends('layouts.admin', ['titulo' => 'Cotización ' . $cotizacion->numero])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.cotizaciones.index') }}">Cotizaciones</a></li>
                    <li class="breadcrumb-item active">{{ $cotizacion->numero }}</li>
                </ol>
            </nav>
            <h2 class="mb-0 text-1100">{{ $cotizacion->titulo }}</h2>
            <p class="text-700 mb-0">{{ $cotizacion->cliente_empresa }} &middot; {{ $cotizacion->numero }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-phoenix-success" href="{{ route('admin.facturas.create', ['cotizacion' => $cotizacion->id]) }}" title="Generar Factura de esta Cotización">
                <span class="fa-solid fa-file-invoice me-2"></span>Facturar
            </a>
            <a class="btn btn-phoenix-secondary" href="{{ route('admin.cotizaciones.preview', $cotizacion) }}" target="_blank">
                <span class="fa-solid fa-arrow-up-right-from-square me-2"></span>Vista previa / Imprimir
            </a>
            <a class="btn btn-phoenix-primary" href="{{ route('admin.cotizaciones.descargar', $cotizacion) }}">
                <span class="fa-solid fa-file-pdf me-2"></span>Descargar PDF
            </a>
            <a class="btn btn-primary" href="{{ route('admin.cotizaciones.edit', $cotizacion) }}">
                <span class="fa-solid fa-pen-to-square me-2"></span>Editar
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom bg-body-emphasis py-3">
                    <h5 class="mb-0">Previsualización del Documento</h5>
                </div>
                <div class="card-body p-0" style="background: #525659; min-height: 700px;">
                    <iframe src="{{ route('admin.cotizaciones.preview', $cotizacion) }}" style="width: 100%; height: 750px; border: none; background: #fff;"></iframe>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header border-bottom bg-body-emphasis py-3">
                    <h5 class="mb-0">Detalles & Estado</h5>
                </div>
                <div class="card-body">
                    @php [$textoEstado, $colorBadge] = $cotizacion->badgeEstado(); @endphp
                    <div class="mb-3">
                        <span class="text-700 fs--1 d-block mb-1">Estado de la cotización:</span>
                        <span class="badge badge-phoenix badge-phoenix-{{ $colorBadge }} fs-0">{{ $textoEstado }}</span>
                    </div>

                    <div class="mb-3">
                        <span class="text-700 fs--1 d-block">Fecha de emisión:</span>
                        <span class="fw-bold">{{ $cotizacion->fecha ? $cotizacion->fecha->translatedFormat('d \d\e F \d\e Y') : '—' }}</span>
                    </div>

                    <div class="mb-3">
                        <span class="text-700 fs--1 d-block">Vigencia:</span>
                        <span class="fw-bold">{{ $cotizacion->vigencia_dias }} días (Vence: {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }})</span>
                    </div>

                    @if ($cotizacion->cliente)
                        <div class="mb-3">
                            <span class="text-700 fs--1 d-block">Cliente vinculado:</span>
                            <a href="{{ route('admin.clientes.edit', $cotizacion->cliente) }}" class="fw-bold">{{ $cotizacion->cliente->empresa }}</a>
                        </div>
                    @endif

                    @if ($cotizacion->sistema)
                        <div class="mb-3">
                            <span class="text-700 fs--1 d-block">Sistema referenciado:</span>
                            <span class="fw-bold">{{ $cotizacion->sistema->nombre }}</span>
                        </div>
                    @endif

                    <hr>

                    <form action="{{ route('admin.cotizaciones.enviar', $cotizacion) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fs--1">Enviar por correo a:</label>
                            <input class="form-control" type="email" name="correo" value="{{ $cotizacion->cliente_correo }}" placeholder="correo@cliente.com" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <span class="fa-solid fa-paper-plane me-2"></span>Marcar como enviada
                        </button>
                    </form>

                    <hr>

                    <form action="{{ route('admin.cotizaciones.destroy', $cotizacion) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta cotización?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link text-danger w-100 p-0 fs--1" type="submit">
                            <span class="fa-solid fa-trash me-1"></span>Eliminar esta cotización
                        </button>
                    </form>
                </div>
            </div>

            @if($cotizacion->notas_internas)
                <div class="card">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h6 class="mb-0">Notas internas</h6>
                    </div>
                    <div class="card-body">
                        <p class="fs--1 text-700 mb-0">{{ $cotizacion->notas_internas }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
