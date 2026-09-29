{{-- Galería de fotos (isotope + BigPicture de la plantilla), con filtros si las fotos tienen grupo. --}}
@php
    $fotos = $s->elementos->filter(fn ($e) => $e->imagen);
    $grupos = $fotos->pluck('grupo')->filter()->unique()->values();
    $claseGrupo = fn ($g) => 'g'.$s->id.'-'.\Illuminate\Support\Str::slug($g);
@endphp
@if ($fotos->isNotEmpty())
    <section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
        <div class="container-small px-lg-7 px-xxl-3">
            @include('publico.bloques._titulo')
            @if ($grupos->count() > 1)
                <ul class="nav font-sans-serif mb-6 mx-auto flex-wrap justify-content-center">
                    <li class="nav-item"><a class="isotope-nav cursor-pointer active" data-sgt-filtro="#galeria{{ $s->id }}" data-valor="*">Todas</a></li>
                    @foreach ($grupos as $g)
                        <li class="nav-item"><a class="isotope-nav cursor-pointer" data-sgt-filtro="#galeria{{ $s->id }}" data-valor=".{{ $claseGrupo($g) }}">{{ $g }}</a></li>
                    @endforeach
                </ul>
            @endif
            <div class="row g-3" id="galeria{{ $s->id }}" data-sl-isotope='{"layoutMode":"packery"}'>
                @foreach ($fotos as $foto)
                    <div class="col-6 col-md-4 px-2 isotope-item {{ $foto->grupo ? $claseGrupo($foto->grupo) : '' }}">
                        <a href="#!" data-bigpicture='{"gallery":"#galeria{{ $s->id }}"}' data-bp="{{ $foto->url() }}" title="{{ $foto->titulo }}">
                            <img class="rounded img-fluid w-100" src="{{ $foto->url() }}" alt="{{ $foto->titulo }}" loading="lazy" />
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
