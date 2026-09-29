{{-- Manuales agrupados por sistema. --}}
@php
    $lista = $datos['manuales']->when($s->opcion('sistema_id'), fn ($c) => $c->where('sistema_id', (int) $s->opcion('sistema_id')));
    $porSistema = $lista->groupBy(fn ($m) => $m->sistema?->nombre ?? 'Generales');
@endphp
<section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        @include('publico.bloques._titulo')
        @forelse ($porSistema as $nombreSistema => $manuales)
            @if ($porSistema->count() > 1)
                <h4 class="mb-3 mt-5 d-flex align-items-center"><span class="fa-solid fa-folder-open text-primary me-2 fs-0"></span>{{ $nombreSistema }}</h4>
            @endif
            <div class="row g-3 mb-4">
                @foreach ($manuales as $m)
                    <div class="col-md-6 col-lg-4">
                        <a class="card h-100 border-0 shadow-sm sgt-tarjeta text-decoration-none" href="{{ route('manual', $m->slug) }}">
                            <div class="card-body d-flex gap-3 p-4">
                                <span class="sgt-icono sgt-icono-sm"><span class="fa-solid fa-book"></span></span>
                                <div class="flex-1">
                                    <h5 class="text-1000 mb-1">{{ $m->titulo }}</h5>
                                    @if ($m->resumen)<p class="text-700 fs--1 mb-2">{{ \Illuminate\Support\Str::limit($m->resumen, 110) }}</p>@endif
                                    <div class="d-flex flex-wrap gap-1">
                                        @if ($m->solo_clientes)<span class="badge badge-phoenix badge-phoenix-warning"><span class="fa-solid fa-lock me-1"></span>Solo clientes</span>@endif
                                        @if ($m->contenido)<span class="badge badge-phoenix badge-phoenix-secondary"><span class="fa-solid fa-file-lines me-1"></span>Guía</span>@endif
                                        @if ($m->archivo)<span class="badge badge-phoenix badge-phoenix-danger"><span class="fa-solid fa-file-pdf me-1"></span>PDF</span>@endif
                                        @if ($m->video_url)<span class="badge badge-phoenix badge-phoenix-info"><span class="fa-solid fa-play me-1"></span>Video</span>@endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @empty
            <p class="text-center text-700">Todavía no hay manuales publicados.</p>
        @endforelse
    </div>
</section>
