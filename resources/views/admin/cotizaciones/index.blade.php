@extends('layouts.admin', ['titulo' => 'Cotizaciones'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Cotizaciones</h2>
            <p class="text-700 mb-0">Propuestas comerciales de soporte, mantenimiento y desarrollo de software.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.cotizaciones.create', ['tipo' => 'planes']) }}">
                <span class="fa-solid fa-table-columns me-2"></span>Cotización de Soporte (Planes)
            </a>
            <a class="btn btn-primary" href="{{ route('admin.cotizaciones.create', ['tipo' => 'items']) }}">
                <span class="fa-solid fa-plus me-2"></span>Nueva Cotización
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form class="row g-3 mb-4" method="GET" action="{{ route('admin.cotizaciones.index') }}">
                <div class="col-md-5">
                    <div class="position-relative">
                        <input class="form-control" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por número, cliente o título..." />
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="estado" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $clave => [$nombre, $badgeColor])
                            <option value="{{ $clave }}" {{ $estado === $clave ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary w-100" type="submit">Filtrar</button>
                    @if ($buscar !== '' || $estado !== '')
                        <a class="btn btn-subtle-secondary" href="{{ route('admin.cotizaciones.index') }}">Limpiar</a>
                    @endif
                </div>
            </form>

            @if ($cotizaciones->isEmpty())
                <div class="text-center py-5">
                    <span class="fa-regular fa-file-lines fs-4 text-400 mb-2 d-block"></span>
                    <p class="text-700 mb-0">{{ $buscar !== '' || $estado !== '' ? 'No se encontraron cotizaciones con los filtros aplicados.' : 'Todavía no hay cotizaciones registradas.' }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                        <tr>
                            <th class="ps-0">Número</th>
                            <th>Cliente</th>
                            <th>Título</th>
                            <th>Fecha & Vigencia</th>
                            <th>Estado</th>
                            <th class="text-end pe-0">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($cotizaciones as $c)
                            @php [$textoEstado, $colorBadge] = $c->badgeEstado(); @endphp
                            <tr>
                                <td class="ps-0 fw-bold">
                                    <a href="{{ route('admin.cotizaciones.show', $c) }}">{{ $c->numero }}</a>
                                    <div class="text-muted fs--2">{{ $c->tipo === 'planes' ? 'Tabla de Planes' : 'Por Módulos' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-1000">{{ $c->cliente_empresa }}</div>
                                    @if ($c->cliente_atencion)
                                        <div class="text-muted fs--2">Atención: {{ $c->cliente_atencion }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;">{{ $c->titulo }}</div>
                                    @if ($c->subtitulo)
                                        <div class="text-muted fs--2 text-truncate" style="max-width: 250px;">{{ $c->subtitulo }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $c->fecha ? $c->fecha->format('d/m/Y') : '—' }}</div>
                                    <div class="fs--2 text-muted">Vence: {{ $c->fecha_vencimiento->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-{{ $colorBadge }}">{{ $textoEstado }}</span>
                                </td>
                                <td class="text-end pe-0">
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-subtle-success" href="{{ route('admin.facturas.create', ['cotizacion' => $c->id]) }}" title="Generar Factura">
                                            <span class="fa-solid fa-file-invoice"></span>
                                        </a>
                                        <a class="btn btn-subtle-primary" href="{{ route('admin.cotizaciones.show', $c) }}" title="Ver detalles">
                                            <span class="fa-solid fa-eye"></span>
                                        </a>
                                        <a class="btn btn-subtle-secondary" href="{{ route('admin.cotizaciones.descargar', $c) }}" title="Descargar PDF">
                                            <span class="fa-solid fa-file-pdf"></span>
                                        </a>
                                        <a class="btn btn-subtle-info" href="{{ route('admin.cotizaciones.edit', $c) }}" title="Editar">
                                            <span class="fa-solid fa-pen-to-square"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $cotizaciones->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
