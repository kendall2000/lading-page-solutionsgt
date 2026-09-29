@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Manuales'])

@section('portal')
    @if ($manuales->isEmpty())
        <div class="card"><div class="card-body text-center py-6">
            <span class="fa-solid fa-book fs-3 text-400 mb-3 d-block"></span>
            <p class="text-700 mb-0">Todavía no hay manuales para tus sistemas. Cuando contrates un sistema, aquí verás sus guías, incluidas las exclusivas para clientes.</p>
        </div></div>
    @else
        @foreach ($manuales as $grupo => $lista)
            <h4 class="mb-3 {{ $loop->first ? '' : 'mt-5' }}">{{ $grupo }}</h4>
            <div class="row g-3">
                @foreach ($lista as $m)
                    <div class="col-md-6 col-xl-4">
                        <a class="card h-100 text-decoration-none sgt-tarjeta" href="{{ route('manual', $m->slug) }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="sgt-icono sgt-icono-sm"><span class="fa-solid {{ $m->video_url ? 'fa-circle-play' : ($m->archivo ? 'fa-file-pdf' : 'fa-book-open') }}"></span></span>
                                    @if ($m->solo_clientes)<span class="badge badge-phoenix badge-phoenix-warning"><span class="fa-solid fa-lock me-1"></span>Solo clientes</span>@endif
                                </div>
                                <h5 class="text-1000 mb-1">{{ $m->titulo }}</h5>
                                @if ($m->resumen)<p class="fs--1 text-700 mb-0">{{ \Illuminate\Support\Str::limit($m->resumen, 110) }}</p>@endif
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
@endsection
