@extends('layouts.admin', ['titulo' => 'Manuales'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Manuales</h2>
            <p class="text-700 mb-0">Guías de uso de tus sistemas: texto, PDF descargable y/o video. Se muestran en la sección «Manuales» y en la página de cada sistema.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.manuales.create', ['sistema' => $sistema]) }}"><span class="fa-solid fa-plus me-2"></span>Nuevo manual</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form class="mb-3" method="GET">
                <select class="form-select form-select-sm" name="sistema" onchange="this.form.submit()" style="max-width: 300px" aria-label="Filtrar por sistema">
                    <option value="">Todos los sistemas</option>
                    @foreach ($sistemas as $id => $nombre)
                        <option value="{{ $id }}" @selected($sistema === $id)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </form>
            @if ($manuales->isEmpty())
                <p class="text-700 mb-0">Todavía no hay manuales.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                        <tr><th class="ps-0">Manual</th><th>Sistema</th><th>Incluye</th><th class="text-center">Visitas</th><th>Estado</th><th class="text-end pe-0"></th></tr>
                        </thead>
                        <tbody>
                        @foreach ($manuales as $m)
                            <tr>
                                <td class="ps-0">
                                    <a class="fw-bold text-1000" href="{{ route('admin.manuales.edit', $m) }}">{{ $m->titulo }}</a>
                                    @if ($m->resumen)<div class="fs--2 text-700">{{ \Illuminate\Support\Str::limit($m->resumen, 90) }}</div>@endif
                                </td>
                                <td>{{ $m->sistema?->nombre ?? 'General' }}</td>
                                <td class="text-nowrap">
                                    @if ($m->contenido)<span class="badge badge-phoenix badge-phoenix-secondary">Guía</span>@endif
                                    @if ($m->archivo)<span class="badge badge-phoenix badge-phoenix-danger">PDF</span>@endif
                                    @if ($m->video_url)<span class="badge badge-phoenix badge-phoenix-info">Video</span>@endif
                                </td>
                                <td class="text-center">{{ number_format($m->visitas) }}</td>
                                <td><span class="badge badge-phoenix badge-phoenix-{{ $m->visible ? 'success' : 'secondary' }}">{{ $m->visible ? 'Publicado' : 'Oculto' }}</span>@if ($m->solo_clientes)<span class="badge badge-phoenix badge-phoenix-warning ms-1"><span class="fa-solid fa-lock me-1"></span>Clientes</span>@endif</td>
                                <td class="text-end pe-0 text-nowrap">
                                    @if ($m->visible)
                                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('manual', $m->slug) }}" target="_blank" rel="noopener" title="Ver"><span class="fa-solid fa-eye"></span></a>
                                    @endif
                                    <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.manuales.edit', $m) }}">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
