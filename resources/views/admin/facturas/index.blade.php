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

    {{-- Tarjetas de métricas o resumen rápido --}}
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
                    <p class="text-600 fs--2 mt-2 mb-0">Facturación libre con o sin desglose según convenga.</p>
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

    {{-- Últimas facturas libres emitidas --}}
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
                                <th>Condición</th>
                                <th>Ítems</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                                <th class="pe-3 text-end">Formatos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($facturas as $f)
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-primary">{{ $f->numero_interno }}</td>
                                    <td>{{ $f->fecha_emision ? $f->fecha_emision->format('d/m/Y') : $f->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $f->nombre_receptor }}</div>
                                        <div class="text-600 fs--2">NIT: {{ $f->nit_receptor ?: 'CF' }}</div>
                                    </td>
                                    <td><span class="badge badge-phoenix badge-phoenix-secondary">{{ ucfirst($f->condicion_pago) }}</span></td>
                                    <td>{{ $f->items->count() }} línea(s)</td>
                                    <td class="text-end fw-bolder">
                                        {{ $f->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($f->total, 2) }} {{ $f->moneda }}
                                    </td>
                                    <td><span class="badge badge-phoenix badge-phoenix-success">Emitida</span></td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.facturas.documento.imprimir', $f) }}?formato=factura" target="_blank" class="btn btn-xs btn-phoenix-primary" title="Ver Formato A4">
                                                <span class="fa-solid fa-file-invoice me-1"></span>A4
                                            </a>
                                            <a href="{{ route('admin.facturas.documento.imprimir', $f) }}?formato=ticket" target="_blank" class="btn btn-xs btn-phoenix-secondary" title="Ver Formato Ticket">
                                                <span class="fa-solid fa-receipt me-1"></span>Ticket
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
