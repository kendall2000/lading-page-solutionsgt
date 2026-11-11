@extends('layouts.admin', ['titulo' => 'Emitir factura'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Emitir factura manual</h2>
            <p class="text-700 mb-0">Facturación directa y externa para ventas fuera del sitio web o servicios a la medida.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.facturacion.index') }}" class="btn btn-phoenix-secondary">
                <span class="fa-solid fa-list me-1"></span>Ver todas las facturas
            </a>
            <a href="{{ route('admin.facturas.create') }}" class="btn btn-primary">
                <span class="fa-solid fa-plus me-1"></span>Crear nueva factura
            </a>
        </div>
    </div>

    {{-- Métricas rápidas --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xxl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-700 mb-1">Régimen Emisor</h6>
                            <h4 class="mb-0 text-primary">Pequeño Contribuyente</h4>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-primary fs--2">SAT</span>
                    </div>
                    <p class="text-600 fs--2 mt-2 mb-0">Facturación libre sin desglose forzado de IVA.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-700 mb-1">Clasificación SAT</h6>
                            <h4 class="mb-0 text-success">Bien (B) y Servicio (S)</h4>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-success fs--2">Líneas</span>
                    </div>
                    <p class="text-600 fs--2 mt-2 mb-0">Identifica cada ítem para cumplimiento fiscal.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-700 mb-1">Formatos Disponibles</h6>
                            <h4 class="mb-0 text-info">A4 y Ticket 80mm</h4>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-info fs--2">Impresión</span>
                    </div>
                    <p class="text-600 fs--2 mt-2 mb-0">Impresión rápida y descarga directa en PDF.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-700 mb-1">Validación Digital</h6>
                            <h4 class="mb-0 text-warning">Código QR Propio</h4>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-warning fs--2">Seguro</span>
                    </div>
                    <p class="text-600 fs--2 mt-2 mb-0">Verificación inmediata en su propio portal.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Facturas Emitidas con opción para VER CONSUMO DETALLADO --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Historial de facturas directas</h5>
            <a href="{{ route('admin.facturas.create') }}" class="btn btn-sm btn-primary">
                <span class="fa-solid fa-plus me-1"></span>Nueva factura
            </a>
        </div>
        <div class="card-body p-0">
            @if ($facturas->isEmpty())
                <div class="p-4 text-center text-700">
                    <p class="mb-2">Aún no ha emitido ninguna factura libre o directa.</p>
                    <a href="{{ route('admin.facturas.create') }}" class="btn btn-sm btn-outline-primary">
                        Comenzar a emitir la primera
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">No. Factura</th>
                                <th>Fecha</th>
                                <th>Cliente / Receptor</th>
                                <th>Consumo / Conceptos</th>
                                <th>Condición</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                                <th class="pe-3 text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($facturas as $f)
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold">
                                        <a href="{{ route('admin.facturas.show', $f) }}" class="text-primary text-decoration-none">
                                            {{ $f->numero_interno }}
                                        </a>
                                        @if (! $f->esFactura())
                                            <div class="fs--2 text-600 fw-normal font-sans-serif">{{ $f->nombreTipo() }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $f->fecha_emision ? $f->fecha_emision->format('d/m/Y') : $f->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $f->nombre_receptor }}</div>
                                        <div class="text-600 fs--2">NIT: {{ $f->nit_receptor ?: 'CF' }}</div>
                                    </td>
                                    <td>
                                        {{-- Visualización rápida del consumo --}}
                                        <div class="text-truncate" style="max-width: 260px;" title="{{ $f->items->pluck('descripcion')->join(', ') }}">
                                            @foreach($f->items->take(2) as $it)
                                                <span class="badge {{ $it->tipo === 'B' ? 'badge-phoenix-primary' : 'badge-phoenix-info' }} fs--2 me-1">{{ $it->tipo }}</span>
                                                <span class="text-800">{{ $it->descripcion }}</span><br>
                                            @endforeach
                                            @if($f->items->count() > 2)
                                                <span class="text-muted fs--2">+{{ $f->items->count() - 2 }} más...</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td><span class="badge badge-phoenix badge-phoenix-secondary">{{ ucfirst($f->condicion_pago) }}</span></td>
                                    <td class="text-end fw-bolder font-monospace">
                                        {{ $f->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($f->total, 2) }} {{ $f->moneda }}
                                    </td>
                                    <td>@include('admin.facturas.partes.estado', ['d' => $f])</td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="btn-group">
                                            {{-- Botón para ver el detalle de lo que consumió --}}
                                            <a href="{{ route('admin.facturas.show', $f) }}" class="btn btn-xs btn-phoenix-primary" title="Ver consumo y detalle de factura">
                                                <span class="fa-solid fa-eye me-1"></span>Ver
                                            </a>
                                            <a href="{{ route('admin.facturas.documento.imprimir', $f) }}?formato=factura" target="_blank" class="btn btn-xs btn-phoenix-secondary" title="Ver Formato A4">
                                                <span class="fa-solid fa-file-invoice"></span>
                                            </a>
                                            <a href="{{ route('admin.facturas.documento.imprimir', $f) }}?formato=ticket" target="_blank" class="btn btn-xs btn-phoenix-secondary" title="Ver Formato Ticket">
                                                <span class="fa-solid fa-receipt"></span>
                                            </a>
                                            <a href="{{ route('admin.facturas.documento.descargar-pdf', $f) }}?formato=factura" class="btn btn-xs btn-phoenix-info" title="Descargar PDF">
                                                <span class="fa-solid fa-download"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $facturas->links() }}</div>
            @endif
        </div>
    </div>
@endsection
