{{-- Tarjetas con ícono o imagen, título y texto (servicios, valores, beneficios…). --}}
@php $col = ['2' => 'col-md-6', '3' => 'col-md-6 col-lg-4', '4' => 'col-sm-6 col-lg-3'][$s->opcion('columnas', '3')] ?? 'col-md-6 col-lg-4'; @endphp
<section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        @include('publico.bloques._titulo')
        <div class="row g-4 justify-content-center">
            @foreach ($s->elementos as $e)
                <div class="{{ $col }}">
                    <div class="card h-100 border-0 shadow-sm sgt-tarjeta">
                        <div class="card-body p-5">
                            <div class="mb-4">@include('publico.bloques._icono', ['e' => $e])</div>
                            @if ($e->titulo)<h4 class="mb-3 text-1000">{{ $e->titulo }}</h4>@endif
                            @if ($e->texto)<p class="text-700 mb-0" style="white-space: pre-line">{{ $e->texto }}</p>@endif
                            @if ($e->enlace && $e->enlace_texto)
                                <a class="btn btn-link px-0 mt-3 fs--1 fw-bold" href="{{ $e->enlace }}">{{ $e->enlace_texto }}<span class="fa-solid fa-angle-right ms-2"></span></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
