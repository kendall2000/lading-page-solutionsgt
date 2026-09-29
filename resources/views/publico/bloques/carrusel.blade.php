{{-- Fotos grandes que pasan solas (Bootstrap carousel), con título, texto y botón sobre la foto. --}}
@php $fotos = $s->elementos->filter(fn ($e) => $e->imagen)->values(); @endphp
@if ($fotos->isNotEmpty())
    @php $alto = ['normal' => '480px', 'alta' => '620px', 'pantalla' => 'calc(100vh - 72px)'][$s->opcion('altura', 'normal')] ?? '480px'; @endphp
    <section class="p-0 {{ $fondo }}" id="s{{ $s->id }}">
        <div class="carousel slide carousel-fade sgt-carrusel" id="carrusel{{ $s->id }}" data-bs-ride="carousel" data-bs-interval="6000" style="--sgt-alto: {{ $alto }}">
            @if ($fotos->count() > 1)
                <div class="carousel-indicators">
                    @foreach ($fotos as $foto)
                        <button class="{{ $loop->first ? 'active' : '' }}" type="button" data-bs-target="#carrusel{{ $s->id }}" data-bs-slide-to="{{ $loop->index }}" aria-label="Foto {{ $loop->iteration }}" @if ($loop->first) aria-current="true" @endif></button>
                    @endforeach
                </div>
            @endif
            <div class="carousel-inner">
                @foreach ($fotos as $foto)
                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        <img src="{{ $foto->url() }}" alt="{{ $foto->titulo }}" @unless ($loop->first) loading="lazy" @endunless />
                        @if ($foto->titulo || $foto->texto || $foto->enlace_texto)
                            <div class="carousel-caption">
                                <div class="container-small px-4 px-lg-7 px-xxl-3 w-100">
                                    <div style="max-width: 40rem">
                                        @if ($foto->titulo)<h2 class="text-white fs-3 fs-md-5 fw-black mb-3">{{ $foto->titulo }}</h2>@endif
                                        @if ($foto->texto)<p class="text-white fs-0 fs-md-1 mb-4" style="opacity: .9">{{ $foto->texto }}</p>@endif
                                        @if ($foto->enlace_texto && $foto->enlace)
                                            <a class="btn btn-lg btn-primary rounded-pill" href="{{ $foto->enlace }}">{{ $foto->enlace_texto }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($fotos->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#carrusel{{ $s->id }}" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#carrusel{{ $s->id }}" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button>
            @endif
        </div>
    </section>
@endif
