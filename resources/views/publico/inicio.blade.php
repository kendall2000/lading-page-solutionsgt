@extends('layouts.publico')

@php
    // Ilustraciones de la plantilla para los sistemas que aún no tienen captura.
    $ilustraciones = ['34', '35', '36', '37'];
    $capturas = $sistemas->flatMap(fn ($s) => $s->imagenes->map(fn ($i) => ['sistema' => $s, 'imagen' => $i]));
    $fortalezas = [
        ['lightning-speed', 'Entrega ágil', 'Sistemas funcionando en semanas, no en meses.'],
        ['best-statistics', 'Reportes claros', 'Información de tu negocio lista para decidir.'],
        ['all-night', 'Seguridad', 'Accesos por rol, verificación en dos pasos y respaldos.'],
        ['editable-features', 'A tu medida', 'Se adapta a cómo trabaja tu empresa.'],
    ];
@endphp

@section('contenido')
    {{-- ============ Portada ============ --}}
    <section class="pb-8 overflow-hidden" id="inicio">
        <div class="hero-header-container-alternate position-relative">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6 pt-8 pb-6 position-relative z-index-5 text-center text-lg-start">
                        @if ($cfg->eslogan)
                            <p class="text-primary fw-bold text-uppercase fs--1 mb-3" style="letter-spacing: .08em">{{ $cfg->eslogan }}</p>
                        @endif
                        <h1 class="fs-5 fs-md-6 fs-xl-7 fw-black mb-4">
                            <span class="text-gradient-info me-2">{{ $cfg->hero_resaltado ?: 'Sistemas' }}</span>{{ $cfg->hero_titulo ?: 'a la medida de tu negocio' }}
                        </h1>
                        <p class="mb-5 pe-xl-10">{{ $cfg->hero_texto }}</p>
                        <a class="btn btn-lg btn-primary rounded-pill me-3 mb-2" href="#sistemas" role="button">Ver mis sistemas</a>
                        <a class="btn btn-link me-2 fs-0 p-0 mb-2" href="#contacto" role="button">Hablemos<span class="fa-solid fa-angle-right ms-2 fs--1"></span></a>
                    </div>
                    <div class="col-12 col-lg-auto d-none d-lg-block">
                        <div class="hero-image-container position-absolute h-100 end-0 d-flex align-items-center">
                            <div class="position-relative">
                                <div class="position-absolute end-0 hero-image-container-overlay" style="transform: skewY(-8deg);"></div>
                                <img class="position-absolute end-0 hero-image-container-bg" src="{{ asset('assets/img/bg/bg-36.png') }}" alt="" />
                                @include('publico.partes.imagen-hero', ['clase' => 'w-100'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-small px-md-8 mb-8 d-lg-none">
                <div class="position-relative">
                    <div class="position-absolute end-0 hero-image-container-overlay"></div>
                    <img class="position-absolute top-50 hero-image-container-bg" src="{{ asset('assets/img/bg/bg-39.png') }}" alt="" />
                    @include('publico.partes.imagen-hero', ['clase' => 'img-fluid ms-auto'])
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Logos de clientes ============ --}}
    @if ($logos->isNotEmpty())
        <section class="py-5">
            <div class="container-small px-lg-7 px-xxl-3">
                <p class="text-center text-700 fw-semi-bold mb-4">Empresas que confían en mi trabajo</p>
                <div class="row g-0 justify-content-center">
                    @foreach ($logos as $cliente)
                        <div class="col-6 col-md-3">
                            <div class="p-3 p-lg-5 d-flex flex-center h-100 border-1 border-dashed border-bottom border-end">
                                @if ($cliente->sitio_web)<a href="{{ $cliente->sitio_web }}" target="_blank" rel="noopener">@endif
                                <img class="sgt-logo-cliente" src="{{ $cliente->url('logo') }}" alt="{{ $cliente->empresa }}" title="{{ $cliente->empresa }}" loading="lazy" />
                                @if ($cliente->sitio_web)</a>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Sistemas ============ --}}
    <section class="pt-13 pb-10" id="sistemas">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="text-center mb-10 mb-md-9">
                <h5 class="text-info mb-3">Sistemas</h5>
                <h2 class="mb-3 lh-base">Soluciones que ya funcionan<br class="d-none d-sm-block" /> en negocios reales</h2>
                <p class="mb-0">Cada sistema se instala en la nube, se adapta a tu empresa y viene con capacitación y soporte.</p>
            </div>
            @forelse ($sistemas as $i => $sistema)
                <div class="row flex-between-center px-xl-11 {{ $loop->last ? '' : 'mb-10 mb-md-9' }}">
                    <div class="col-md-6 order-1 {{ $i % 2 ? 'order-md-1' : 'order-md-0' }} text-center text-md-start">
                        <span class="sgt-icono-sistema mb-3"><span class="{{ $sistema->icono }}"></span></span>
                        <h3 class="mb-3">
                            {{ $sistema->nombre }}
                            @if ($sistema->destacado)<span class="badge badge-phoenix badge-phoenix-warning fs--2 align-middle ms-1">Destacado</span>@endif
                        </h3>
                        <p class="mb-4">{{ $sistema->resumen }}</p>
                        @if ($sistema->listaTecnologias())
                            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start mb-4">
                                @foreach ($sistema->listaTecnologias() as $tec)
                                    <span class="badge badge-phoenix badge-phoenix-secondary">{{ $tec }}</span>
                                @endforeach
                            </div>
                        @endif
                        <a class="btn btn-outline-primary btn-sm me-2" href="{{ route('sistema', $sistema->slug) }}">Ver detalle<span class="fa-solid fa-angle-right ms-2"></span></a>
                        @if ($sistema->url_demo)
                            <a class="btn btn-link p-0 fs--1" href="{{ $sistema->url_demo }}" target="_blank" rel="noopener">Ver demo<span class="fa-solid fa-arrow-up-right-from-square ms-2"></span></a>
                        @endif
                    </div>
                    <div class="col-md-5 mb-5 mb-md-0 text-center {{ $i % 2 ? 'order-md-0' : 'order-md-1' }}">
                        @if ($sistema->imagen)
                            <a href="{{ route('sistema', $sistema->slug) }}">
                                <img class="w-100 sgt-captura {{ $sistema->imagen_oscura ? 'd-dark-none' : '' }}" src="{{ $sistema->url('imagen') }}" alt="{{ $sistema->nombre }}" loading="lazy" />
                                @if ($sistema->imagen_oscura)
                                    <img class="w-100 sgt-captura d-light-none" src="{{ $sistema->url('imagen_oscura') }}" alt="{{ $sistema->nombre }}" loading="lazy" />
                                @endif
                            </a>
                        @else
                            @php $n = $ilustraciones[$i % count($ilustraciones)]; @endphp
                            <img class="w-75 w-md-100 d-dark-none" src="{{ asset("assets/img/spot-illustrations/{$n}.png") }}" alt="" loading="lazy" />
                            <img class="w-75 w-md-100 d-light-none" src="{{ asset("assets/img/spot-illustrations/{$n}_2.png") }}" alt="" loading="lazy" />
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-700">Muy pronto publicaré aquí mis sistemas.</p>
            @endforelse
        </div>
    </section>

    {{-- ============ Galería de capturas ============ --}}
    @if ($capturas->isNotEmpty())
        <section class="gallery">
            <div class="position-absolute left-0 w-100 gallery-overlay"></div>
            <div class="bg-holder d-none d-xl-block" style="background-image:url({{ asset('assets/img/bg/bg-left-26.png') }});background-size:auto;background-position:left 65%;"></div>
            <div class="bg-holder d-none d-xl-block" style="background-image:url({{ asset('assets/img/bg/bg-right-26.png') }});background-size:auto;background-position:right 62%;"></div>
            <div class="container-small position-relative px-lg-7 px-xxl-3">
                <div class="text-center mb-7">
                    <h5 class="text-info mb-3">Galería</h5>
                    <h2 class="mb-2">Así se ven por dentro</h2>
                </div>
                <ul class="nav font-sans-serif mb-6 w-max-content mx-auto flex-wrap justify-content-center" data-filter-nav="data-filter-nav" style="max-width: 100%">
                    <li class="nav-item"><a class="isotope-nav cursor-pointer active" data-filter="*">Todos</a></li>
                    @foreach ($capturas->pluck('sistema')->unique('id') as $s)
                        <li class="nav-item"><a class="isotope-nav cursor-pointer" data-filter=".sis-{{ $s->id }}">{{ $s->nombre }}</a></li>
                    @endforeach
                </ul>
                <div class="row g-3" id="image_gallery" data-sl-isotope='{"layoutMode":"packery"}'>
                    @foreach ($capturas as ['sistema' => $s, 'imagen' => $img])
                        <div class="col-6 col-md-4 px-2 isotope-item sis-{{ $s->id }}">
                            <a href="#!" data-bigpicture='{"gallery":"#image_gallery"}' data-bp="{{ $img->url() }}" title="{{ $img->titulo }}">
                                <img class="rounded img-fluid w-100" src="{{ $img->url() }}" alt="{{ $img->titulo ?: $s->nombre }}" loading="lazy" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Sobre mí ============ --}}
    <section class="overflow-hidden rotating-earth-container pb-5 pb-md-0 pt-12" id="sobre-mi">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center text-lg-start">
                    <h5 class="text-info mb-3">Sobre mí</h5>
                    <h2 class="mb-2 lh-base">{{ $cfg->sobre_titulo ?: 'Hola, soy' }}</h2>
                    @if ($cfg->propietario)
                        <h1 class="fs-4 fs-sm-5 mb-1 text-gradient-info fw-black">{{ $cfg->propietario }}</h1>
                    @endif
                    @if ($cfg->cargo)
                        <p class="fw-bold text-700 mb-4">{{ $cfg->cargo }}</p>
                    @endif
                    <div class="mb-8">
                        @foreach (\App\Support\Imagenes::lineas($cfg->sobre_texto) as $parrafo)
                            <p>{{ $parrafo }}</p>
                        @endforeach
                    </div>
                    <div class="row gy-6 mb-8">
                        @foreach ($fortalezas as [$fIcono, $fTitulo, $fTexto])
                            <div class="col-sm-6 text-center text-lg-start">
                                <img class="mb-4 d-dark-none" src="{{ asset("assets/img/icons/{$fIcono}.png") }}" alt="" />
                                <img class="mb-4 d-light-none" src="{{ asset("assets/img/icons/{$fIcono}-dark.png") }}" alt="" />
                                <h4 class="mb-2">{{ $fTitulo }}</h4>
                                <p>{{ $fTexto }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    @if ($cfg->foto)
                        <img class="sgt-foto mb-8" src="{{ $cfg->url('foto') }}" alt="{{ $cfg->propietario }}" loading="lazy" />
                    @else
                        <div class="position-relative rotating-earth mx-auto">
                            <div class="lottie d-dark-none" data-options='{"path":"{{ asset('assets/img/animated-icons/rotating-earth.json') }}"}'></div>
                            <div class="lottie d-light-none" data-options='{"path":"{{ asset('assets/img/animated-icons/rotating-earth-dark.json') }}"}'></div>
                            <img class="position-absolute d-dark-none" src="{{ asset('assets/img/spot-illustrations/earth-plane.png') }}" alt="" />
                            <img class="position-absolute d-light-none" src="{{ asset('assets/img/spot-illustrations/earth-plane-dark.png') }}" alt="" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Cifras ============ --}}
    @php
        $cifras = array_filter([
            [$cfg->anios_experiencia, '+', 'Años de experiencia'],
            [$sistemas->count(), '', 'Sistemas propios'],
            [$totalClientes, '+', 'Clientes'],
            [$cfg->proyectos_entregados, '+', 'Proyectos entregados'],
        ], fn ($c) => $c[0] > 0);
    @endphp
    @if ($cifras)
        <section class="counter-container">
            <div class="position-absolute start-0 end-0 w-100 counter-overlay" style="transform: skewY(-8deg)"></div>
            <div class="bg-holder d-none d-lg-block" style="background-image:url({{ asset('assets/img/bg/bg-left-25.png') }});background-size:auto;background-position:left center;"></div>
            <div class="bg-holder d-none d-lg-block" style="background-image:url({{ asset('assets/img/bg/bg-right-25.png') }});background-size:auto;background-position:right center;"></div>
            <div class="container-small position-relative">
                <div class="row gx-0 gy-8 justify-content-center">
                    @foreach (array_values($cifras) as [$valor, $sufijo, $texto])
                        <div class="col-sm-6 col-md-auto {{ $loop->last ? '' : 'me-md-5 pe-md-5 border-end-md border-dashed' }} text-center">
                            <h1 class="fs-5 fs-lg-7 fw-bolder text-info mb-3" data-countup='{"endValue":{{ (int) $valor }},"duration":3,"suffix":"{{ $sufijo }}"}'>{{ $valor }}{{ $sufijo }}</h1>
                            <h4>{{ $texto }}</h4>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Servicios ============ --}}
    @if ($servicios->isNotEmpty())
        <section class="position-static pt-15 pt-md-5 pt-lg-2" id="servicios">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="text-center mb-3 mb-lg-7">
                    <h5 class="text-info mb-3">Servicios</h5>
                    <h2 class="mb-2">¿Cómo puedo ayudarte?</h2>
                </div>
                <div class="row g-3 mb-7 mb-lg-11 justify-content-center">
                    @foreach ($servicios as $servicio)
                        <div class="col-md-6 col-lg-4">
                            <div class="pricing-card h-100">
                                <div class="card bg-transparent h-100 {{ $servicio->destacado ? 'border border-2 border-info rounded-4' : 'border-0' }}">
                                    <div class="card-body p-6 p-lg-7 d-flex flex-column">
                                        <h3 class="mb-2">{{ $servicio->nombre }}</h3>
                                        @if ($servicio->descripcion)<p class="text-700 fs--1 mb-4">{{ $servicio->descripcion }}</p>@endif
                                        @if ($servicio->precio)
                                            <h1 class="fs-4 d-flex align-items-center gap-1 mb-4">{{ $servicio->precio }}@if ($servicio->periodo)<span class="fs-0 fw-normal">{{ $servicio->periodo }}</span>@endif</h1>
                                        @endif
                                        <a class="btn btn-lg w-100 mb-6 {{ $servicio->destacado ? 'btn-primary' : 'btn-outline-primary' }}" href="#contacto">Me interesa</a>
                                        @if ($servicio->listaIncluye())
                                            <h5 class="mb-4">Incluye</h5>
                                            <ul class="fa-ul ps-4 m-0 pricing">
                                                @foreach ($servicio->listaIncluye() as $item)
                                                    <li class="d-flex align-items-center mb-3"><span class="fa-li"><span class="fas fa-check text-primary"></span></span><p class="mb-0">{{ $item }}</p></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card rounded-4 border-0 offer-card mb-10">
                    <div class="card-body d-md-flex align-items-center gap-4 py-5">
                        <img class="mb-4 mb-md-0 d-dark-none" src="{{ asset('assets/img/spot-illustrations/air-plane.png') }}" width="155" alt="" />
                        <img class="mb-4 mb-md-0 d-light-none" src="{{ asset('assets/img/spot-illustrations/air-plane-dark.png') }}" width="155" alt="" />
                        <div>
                            <p class="fw-bold mb-2">¿Necesitas algo diferente?</p>
                            <p class="mb-4">También desarrollo sistemas nuevos desde cero, integraciones (facturación electrónica FEL, pagos, apps de delivery) y mejoras a sistemas que ya tienes.</p>
                            <a class="btn btn-link me-2 p-0 fs--1" href="#contacto" role="button">Cuéntame tu proyecto<i class="fa-solid fa-angle-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Testimonios ============ --}}
    @if ($testimonios->isNotEmpty())
        <section class="pb-14 overflow-x-hidden" id="clientes">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="text-center mb-5 position-relative">
                    <h5 class="text-info mb-3">Clientes</h5>
                    <h2 class="mb-2 lh-base">Lo que dicen quienes ya trabajan conmigo</h2>
                </div>
                <div class="carousel testimonial-carousel slide position-relative dark__bg-1100" id="carruselTestimonios" data-bs-ride="carousel">
                    <div class="bg-holder d-none d-md-block" style="background-image:url({{ asset('assets/img/bg/39.png') }});background-size:186px;background-position:top 20px right 20px;"></div>
                    <img class="position-absolute d-none d-lg-block" src="{{ asset('assets/img/bg/bg-left-22.png') }}" width="150" alt="" style="top: -100px; left: -70px" />
                    <img class="position-absolute d-none d-lg-block" src="{{ asset('assets/img/bg/bg-right-22.png') }}" width="150" alt="" style="bottom: -80px; right: -80px" />
                    <div class="carousel-inner">
                        @foreach ($testimonios as $t)
                            <div class="carousel-item text-center py-8 px-5 px-xl-15 {{ $loop->first ? 'active' : '' }}">
                                @for ($e = 1; $e <= 5; $e++)
                                    <span class="{{ $e <= $t->calificacion ? 'fa fa-star text-warning' : 'fa-regular fa-star text-warning-300' }}"></span>
                                @endfor
                                <h3 class="fw-semi-bold fst-italic mt-3 mb-8 w-xl-70 mx-auto lh-base">“{{ $t->testimonio }}”</h3>
                                <div class="d-flex align-items-center justify-content-center gap-3 mx-auto">
                                    <div class="avatar avatar-3xl">
                                        @if ($t->foto)
                                            <img class="rounded-circle border border-2 border-primary" src="{{ $t->url('foto') }}" alt="{{ $t->contacto }}" />
                                        @else
                                            <div class="avatar-name rounded-circle border border-2 border-primary"><span>{{ $t->iniciales() }}</span></div>
                                        @endif
                                    </div>
                                    <div class="text-start">
                                        <h5>{{ $t->contacto ?: $t->empresa }}</h5>
                                        <p class="mb-0">{{ collect([$t->cargo, $t->contacto ? $t->empresa : null])->filter()->implode(' · ') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($testimonios->count() > 1)
                        <div class="carousel-indicators">
                            @foreach ($testimonios as $t)
                                <button class="{{ $loop->first ? 'active' : '' }}" type="button" data-bs-target="#carruselTestimonios" data-bs-slide-to="{{ $loop->index }}" aria-label="Testimonio {{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Contacto ============ --}}
    @include('publico.partes.contacto')

    {{-- ============ Llamado final ============ --}}
    <section class="bg-soft-primary dark__bg-1000 pb-10 overflow-hidden">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="position-absolute w-100 h-100 start-0 end-0" style="bottom: -350px; transform: skewY(-8deg); background: linear-gradient(102.27deg, #38ABFF 4.69%, var(--phoenix-primary) 106.27%)"></div>
            <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/bg-left-24.png') }});background-size:auto;background-position:left center;"></div>
            <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/bg-right-24.png') }});background-size:auto;background-position:right center;"></div>
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="card py-md-9 px-md-13 border-0 z-index-1 shadow-lg">
                        <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/bg-38.png') }});background-position:center;background-size:100%;"></div>
                        <div class="card-body position-relative">
                            <img class="img-fluid mb-5 d-dark-none" src="{{ asset('assets/img/spot-illustrations/37.png') }}" width="220" alt="" />
                            <img class="img-fluid mb-5 d-light-none" src="{{ asset('assets/img/spot-illustrations/37_2.png') }}" width="220" alt="" />
                            <p class="fw-bold">Demostración sin compromiso <span class="text-primary fs-2">.</span> Te muestro el sistema funcionando</p>
                            <h1 class="fs-2 fs-sm-4 fs-lg-6 fw-bolder lh-sm mb-5">¿Listo para <span class="gradient-text-primary mx-1">ordenar</span> tu negocio?</h1>
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                @if ($wa = $cfg->enlaceWhatsapp('Hola, quiero agendar una demostración.'))
                                    <a class="btn btn-lg btn-success" href="{{ $wa }}" target="_blank" rel="noopener"><span class="fa-brands fa-whatsapp me-2"></span>Escríbeme por WhatsApp</a>
                                @endif
                                <a class="btn btn-lg btn-primary" href="#contacto"><span class="fa-solid fa-envelope me-2"></span>Enviar un mensaje</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
