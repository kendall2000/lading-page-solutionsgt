@extends('layouts.admin', ['titulo' => 'Direcciones'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Direcciones</h2>
            <p class="text-700 mb-0">Oficinas o puntos de atención. La principal se muestra en el mapa de «Contacto».</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.direcciones.create') }}"><span class="fa-solid fa-plus me-2"></span>Nueva dirección</a>
    </div>

    <div class="row g-3">
        @forelse ($direcciones as $d)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <iframe src="{{ $d->mapaEmbebido() }}" loading="lazy" style="width:100%; height:160px; border:0; border-radius: .5rem .5rem 0 0;" title="Mapa"></iframe>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                            <h5 class="mb-0">{{ $d->nombre }}</h5>
                            <div class="text-nowrap">
                                @if ($d->principal)<span class="badge badge-phoenix badge-phoenix-primary">Principal</span>@endif
                                @unless ($d->visible)<span class="badge badge-phoenix badge-phoenix-secondary">Oculta</span>@endunless
                            </div>
                        </div>
                        <p class="fs--1 text-800 mb-1">{{ $d->direccion }}{{ $d->ciudad ? ', '.$d->ciudad : '' }}</p>
                        <p class="fs--1 text-700 mb-3">{{ collect([$d->telefono, $d->horario])->filter()->implode(' · ') }}</p>
                        <div class="mt-auto"><a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.direcciones.edit', $d) }}">Editar</a></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-700">Todavía no hay direcciones.</div></div></div>
        @endforelse
    </div>
@endsection
