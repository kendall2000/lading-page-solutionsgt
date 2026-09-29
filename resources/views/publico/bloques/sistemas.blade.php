{{-- Catálogo de software: tarjetas o filas, filtros por categoría y tipo, y botones de demo / prueba. --}}
@php
    $lista = $datos['sistemas']
        ->when($s->opcion('categoria_id'), fn ($c) => $c->where('categoria_id', (int) $s->opcion('categoria_id')))
        ->when($s->opcion('solo_destacados'), fn ($c) => $c->where('destacado', true))
        ->when((int) $s->opcion('limite') > 0, fn ($c) => $c->take((int) $s->opcion('limite')))
        ->values();
    $filas = $s->opcion('estilo') === 'filas';
    $cats = $datos['categorias']->whereIn('id', $lista->pluck('categoria_id')->filter()->unique());
    $modalidades = $lista->pluck('modalidad')->unique();
    $conFiltros = $s->opcion('filtros') && ! $filas && ($cats->count() > 1 || $modalidades->count() > 1 || $lista->contains(fn ($x) => $x->ofrecePrueba()));
    $ilustraciones = ['34', '35', '36', '37'];
@endphp
<section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        @include('publico.bloques._titulo')

        @if ($lista->isEmpty())
            <p class="text-center text-700">Muy pronto publicaré aquí mis sistemas.</p>
        @elseif ($filas)
            @foreach ($lista as $i => $sis)
                <div class="row flex-between-center px-xl-11 {{ $loop->last ? '' : 'mb-10 mb-md-9' }}">
                    <div class="col-md-6 order-1 {{ $i % 2 ? 'order-md-1' : 'order-md-0' }} text-center text-md-start">
                        <span class="sgt-icono mb-3"><span class="{{ $sis->icono }}"></span></span>
                        <h3 class="mb-2">{{ $sis->nombre }}</h3>
                        @include('publico.partes.insignias', ['sis' => $sis])
                        <p class="mb-4 mt-3">{{ $sis->resumen }}</p>
                        @include('publico.partes.botones-sistema', ['sis' => $sis])
                    </div>
                    <div class="col-md-5 mb-5 mb-md-0 text-center {{ $i % 2 ? 'order-md-0' : 'order-md-1' }}">
                        @if ($sis->imagen)
                            <a href="{{ route('sistema', $sis->slug) }}"><img class="w-100 sgt-captura" src="{{ $sis->url('imagen') }}" alt="{{ $sis->nombre }}" loading="lazy" /></a>
                        @else
                            @php $n = $ilustraciones[$i % 4]; @endphp
                            <img class="w-75 w-md-100 d-dark-none" src="{{ asset("assets/img/spot-illustrations/{$n}.png") }}" alt="" loading="lazy" />
                            <img class="w-75 w-md-100 d-light-none" src="{{ asset("assets/img/spot-illustrations/{$n}_2.png") }}" alt="" loading="lazy" />
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            @if ($conFiltros)
                <ul class="nav nav-pills justify-content-center flex-wrap gap-2 mb-6 sgt-filtros">
                    <li class="nav-item"><a class="nav-link rounded-pill px-4 active" href="#" data-sgt-filtro="#catalogo{{ $s->id }}" data-valor="*">Todos</a></li>
                    @foreach ($cats as $cat)
                        <li class="nav-item"><a class="nav-link rounded-pill px-4" href="#" data-sgt-filtro="#catalogo{{ $s->id }}" data-valor=".cat-{{ $cat->id }}">@if ($cat->icono)<span class="{{ $cat->icono }} me-2"></span>@endif{{ $cat->nombre }}</a></li>
                    @endforeach
                    @foreach ($modalidades as $mod)
                        <li class="nav-item"><a class="nav-link rounded-pill px-4" href="#" data-sgt-filtro="#catalogo{{ $s->id }}" data-valor=".mod-{{ $mod }}">{{ \App\Models\Sistema::MODALIDADES[$mod][0] ?? $mod }}</a></li>
                    @endforeach
                    @if ($lista->contains(fn ($x) => $x->ofrecePrueba()))
                        <li class="nav-item"><a class="nav-link rounded-pill px-4" href="#" data-sgt-filtro="#catalogo{{ $s->id }}" data-valor=".con-prueba">Con prueba gratis</a></li>
                    @endif
                </ul>
            @endif
            <div class="row g-4" id="catalogo{{ $s->id }}">
                @foreach ($lista as $i => $sis)
                    <div class="col-md-6 col-lg-4 cat-{{ $sis->categoria_id ?: 0 }} mod-{{ $sis->modalidad }} {{ $sis->ofrecePrueba() ? 'con-prueba' : '' }}" data-sgt-item>
                        <div class="card h-100 border-0 shadow-sm sgt-tarjeta overflow-hidden">
                            <a class="d-block bg-soft-primary dark__bg-1100 text-center" href="{{ route('sistema', $sis->slug) }}" style="aspect-ratio: 16/10; overflow: hidden;">
                                @if ($sis->imagen)
                                    <img class="w-100 h-100" src="{{ $sis->url('imagen') }}" alt="{{ $sis->nombre }}" style="object-fit: cover;" loading="lazy" />
                                @else
                                    @php $n = $ilustraciones[$i % 4]; @endphp
                                    <img class="h-100 p-4 d-dark-none" src="{{ asset("assets/img/spot-illustrations/{$n}.png") }}" alt="" style="object-fit: contain;" loading="lazy" />
                                    <img class="h-100 p-4 d-light-none" src="{{ asset("assets/img/spot-illustrations/{$n}_2.png") }}" alt="" style="object-fit: contain;" loading="lazy" />
                                @endif
                            </a>
                            <div class="card-body d-flex flex-column p-5">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="sgt-icono sgt-icono-sm"><span class="{{ $sis->icono }}"></span></span>
                                    <div>
                                        <h4 class="mb-0"><a class="text-1000" href="{{ route('sistema', $sis->slug) }}">{{ $sis->nombre }}</a></h4>
                                        @if ($sis->categoria)<span class="fs--1 text-600">{{ $sis->categoria->nombre }}</span>@endif
                                    </div>
                                </div>
                                @include('publico.partes.insignias', ['sis' => $sis])
                                <p class="text-700 fs--1 mt-3 mb-4">{{ $sis->resumen }}</p>
                                <div class="mt-auto">@include('publico.partes.botones-sistema', ['sis' => $sis, 'chico' => true])</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
