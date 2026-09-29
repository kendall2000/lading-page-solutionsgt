@extends('layouts.admin', ['titulo' => 'Sistemas'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Sistemas</h2>
            <p class="text-700 mb-0">Los sistemas que muestras en el sitio. El orden define cómo aparecen.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.sistemas.create') }}"><span class="fa-solid fa-plus me-2"></span>Nuevo sistema</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($sistemas->isEmpty())
                <p class="text-700 mb-0">Todavía no hay sistemas. Crea el primero.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                        <tr>
                            <th class="ps-0" style="width: 60px">Orden</th>
                            <th>Sistema</th>
                            <th class="text-center">Capturas</th>
                            <th class="text-center">Clientes</th>
                            <th class="text-center">Visitas</th>
                            <th>Estado</th>
                            <th class="text-end pe-0"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($sistemas as $s)
                            <tr>
                                <td class="ps-0">{{ $s->orden }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="sgt-icono-admin me-3 text-primary fs-1"><span class="{{ $s->icono }}"></span></span>
                                        <div>
                                            <a class="fw-bold text-1000" href="{{ route('admin.sistemas.edit', $s) }}">{{ $s->nombre }}</a>
                                            <div class="text-700 fs--2">{{ \Illuminate\Support\Str::limit($s->resumen, 90) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">{{ $s->imagenes_count }}</td>
                                <td class="text-center">{{ $s->clientes_count }}</td>
                                <td class="text-center">{{ number_format($s->visitas) }}</td>
                                <td>
                                    @if ($s->visible)
                                        <span class="badge badge-phoenix badge-phoenix-success">Visible</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-secondary">Oculto</span>
                                    @endif
                                    @if ($s->destacado)<span class="badge badge-phoenix badge-phoenix-warning">Destacado</span>@endif
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    @if ($s->visible)
                                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('sistema', $s->slug) }}" target="_blank" rel="noopener" title="Ver en el sitio"><span class="fa-solid fa-eye"></span></a>
                                    @endif
                                    <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.sistemas.edit', $s) }}">Editar</a>
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
