@extends('layouts.admin', ['titulo' => 'Servicios'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Servicios</h2>
            <p class="text-700 mb-0">Planes o servicios que ofreces. Si no hay ninguno visible, la sección no aparece.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.servicios.create') }}"><span class="fa-solid fa-plus me-2"></span>Nuevo servicio</a>
    </div>

    <div class="row g-3">
        @forelse ($servicios as $s)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 {{ $s->destacado ? 'border border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="mb-0">{{ $s->nombre }}</h4>
                            <span class="badge badge-phoenix badge-phoenix-{{ $s->visible ? 'success' : 'secondary' }}">{{ $s->visible ? 'Visible' : 'Oculto' }}</span>
                        </div>
                        @if ($s->precio)<p class="fs-1 fw-bold mb-1">{{ $s->precio }} <span class="fs--1 fw-normal text-700">{{ $s->periodo }}</span></p>@endif
                        <p class="text-700 fs--1">{{ $s->descripcion }}</p>
                        <ul class="fs--1 ps-3 mb-3">
                            @foreach (array_slice($s->listaIncluye(), 0, 5) as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="fs--2 text-600">Orden {{ $s->orden }}{{ $s->destacado ? ' · Destacado' : '' }}</span>
                            <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.servicios.edit', $s) }}">Editar</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-700">Todavía no hay servicios.</div></div></div>
        @endforelse
    </div>
@endsection
