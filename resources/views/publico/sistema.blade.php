@extends('layouts.publico', ['titulo' => $sistema->nombre, 'descripcion' => $sistema->resumen, 'imagenOg' => $sistema->url('imagen')])

@section('contenido')
    {{-- Encabezado del sistema --}}
    <section class="pb-8 overflow-hidden">
        <div class="hero-header-container-alternate position-relative">
            <div class="container-small px-lg-7 px-xxl-3">
                <nav class="mb-4 pt-6" aria-label="Ruta">
                    <ol class="breadcrumb mb-0 fs--1">
                        <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ \App\Support\Sitio::enlaceBloque('sistemas') }}">Software</a></li>
                        @if ($sistema->categoria)<li class="breadcrumb-item">{{ $sistema->categoria->nombre }}</li>@endif
                        <li class="breadcrumb-item active" aria-current="page">{{ $sistema->nombre }}</li>
                    </ol>
                </nav>
                <div class="row align-items-center g-6">
                    <div class="col-lg-6 text-center text-lg-start">
                        <span class="sgt-icono mb-4"><span class="{{ $sistema->icono }}"></span></span>
                        <h1 class="fs-4 fs-md-5 fs-xl-6 fw-black mb-3">{{ $sistema->nombre }}</h1>
                        <div class="mb-4">@include('publico.partes.insignias', ['sis' => $sistema])</div>
                        <p class="fs-0 mb-5">{{ $sistema->resumen }}</p>
                        @if ($sistema->listaTecnologias())
                            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start mb-5">
                                @foreach ($sistema->listaTecnologias() as $tec)
                                    <span class="badge badge-phoenix badge-phoenix-secondary">{{ $tec }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($sistema->ofrecePrueba())
                            <a class="btn btn-lg btn-success rounded-pill me-2 mb-2" href="?tipo=prueba#solicitar"><span class="fa-solid fa-flask me-2"></span>Probar {{ $sistema->dias_prueba }} días gratis</a>
                        @endif
                        @if ($sistema->ofreceDemo())
                            <a class="btn btn-lg btn-primary rounded-pill me-2 mb-2" href="?tipo=demo#solicitar">Solicitar una demo</a>
                        @endif
                        @if ($sistema->url_demo)
                            <a class="btn btn-link fs-0 p-0 mb-2" href="{{ $sistema->url_demo }}" target="_blank" rel="noopener">{{ $sistema->modalidad === 'gratis' ? 'Usar gratis' : 'Ver demo en línea' }}<span class="fa-solid fa-arrow-up-right-from-square ms-2 fs--1"></span></a>
                        @endif
                    </div>
                    <div class="col-lg-6 text-center">
                        @if ($sistema->imagen)
                            <img class="w-100 sgt-captura {{ $sistema->imagen_oscura ? 'd-dark-none' : '' }}" src="{{ $sistema->url('imagen') }}" alt="{{ $sistema->nombre }}" />
                            @if ($sistema->imagen_oscura)
                                <img class="w-100 sgt-captura d-light-none" src="{{ $sistema->url('imagen_oscura') }}" alt="{{ $sistema->nombre }}" />
                            @endif
                        @else
                            <img class="w-75 d-dark-none" src="{{ asset('assets/img/spot-illustrations/34.png') }}" alt="" />
                            <img class="w-75 d-light-none" src="{{ asset('assets/img/spot-illustrations/34_2.png') }}" alt="" />
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Descripción y características --}}
    <section class="pt-8 pb-10">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="row g-8">
                @if ($sistema->descripcion)
                    <div class="{{ $sistema->listaCaracteristicas() ? 'col-lg-5' : 'col-12' }}">
                        <h5 class="text-info mb-3">¿Qué es?</h5>
                        @foreach (\App\Support\Imagenes::lineas($sistema->descripcion) as $parrafo)
                            <p class="text-800">{{ $parrafo }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($sistema->listaCaracteristicas())
                    <div class="{{ $sistema->descripcion ? 'col-lg-7' : 'col-12' }}">
                        <h5 class="text-info mb-3">¿Qué incluye?</h5>
                        <div class="row g-3">
                            @foreach ($sistema->listaCaracteristicas() as $caract)
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-start">
                                        <span class="fa-solid fa-circle-check text-success mt-1 me-2"></span>
                                        <span class="text-900">{{ $caract }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Capturas --}}
    @if ($sistema->imagenes->isNotEmpty())
        <section class="gallery pt-0">
            <div class="container-small position-relative px-lg-7 px-xxl-3">
                <div class="text-center mb-7">
                    <h5 class="text-info mb-3">Capturas</h5>
                    <h2 class="mb-2">Así se ve por dentro</h2>
                </div>
                <div class="row g-3" id="galeria_sistema">
                    @foreach ($sistema->imagenes as $img)
                        <div class="col-6 col-md-4">
                            <a href="#!" data-bigpicture='{"gallery":"#galeria_sistema"}' data-bp="{{ $img->url() }}" title="{{ $img->titulo }}">
                                <img class="rounded img-fluid w-100 sgt-captura" src="{{ $img->url() }}" alt="{{ $img->titulo ?: $sistema->nombre }}" loading="lazy" />
                            </a>
                            @if ($img->titulo)<p class="fs--1 text-700 text-center mt-2 mb-0">{{ $img->titulo }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Testimonios de clientes de este sistema --}}
    @if ($clientes->isNotEmpty())
        <section class="pb-10">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="text-center mb-7">
                    <h5 class="text-info mb-3">Clientes</h5>
                    <h2 class="mb-2">Quiénes ya lo usan</h2>
                </div>
                <div class="row g-4 justify-content-center">
                    @foreach ($clientes as $t)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    @for ($e = 1; $e <= 5; $e++)
                                        <span class="{{ $e <= $t->calificacion ? 'fa fa-star text-warning' : 'fa-regular fa-star text-warning-300' }} fs--1"></span>
                                    @endfor
                                    <p class="fst-italic mt-3">“{{ $t->testimonio }}”</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar avatar-l">
                                            @if ($t->foto)
                                                <img class="rounded-circle" src="{{ $t->url('foto') }}" alt="" />
                                            @else
                                                <div class="avatar-name rounded-circle"><span>{{ $t->iniciales() }}</span></div>
                                            @endif
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $t->contacto ?: $t->empresa }}</h6>
                                            <p class="fs--1 text-700 mb-0">{{ collect([$t->cargo, $t->contacto ? $t->empresa : null])->filter()->implode(' · ') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Manuales del sistema --}}
    @if ($sistema->manuales->isNotEmpty())
        <section class="pb-10">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="text-center mb-7">
                    <h5 class="text-info mb-3">Manuales</h5>
                    <h2 class="mb-2">Aprende a usarlo</h2>
                </div>
                <div class="row g-3 justify-content-center">
                    @foreach ($sistema->manuales as $m)
                        <div class="col-md-6 col-lg-4">
                            <a class="card h-100 border-0 shadow-sm sgt-tarjeta text-decoration-none" href="{{ route('manual', $m->slug) }}">
                                <div class="card-body d-flex gap-3 p-4">
                                    <span class="sgt-icono sgt-icono-sm"><span class="fa-solid fa-book"></span></span>
                                    <div>
                                        <h5 class="text-1000 mb-1">{{ $m->titulo }}</h5>
                                        @if ($m->resumen)<p class="text-700 fs--1 mb-0">{{ \Illuminate\Support\Str::limit($m->resumen, 100) }}</p>@endif
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Otros sistemas --}}
    @if ($otros->isNotEmpty())
        <section class="bg-soft-primary dark__bg-1100 py-10">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="text-center mb-7">
                    <h5 class="text-info mb-3">Más sistemas</h5>
                    <h2 class="mb-2">También te puede interesar</h2>
                </div>
                <div class="row g-4 justify-content-center">
                    @foreach ($otros as $o)
                        <div class="col-md-6 col-lg-4">
                            <a class="card h-100 text-decoration-none hover-actions-trigger" href="{{ route('sistema', $o->slug) }}">
                                <div class="card-body">
                                    <span class="sgt-icono mb-3"><span class="{{ $o->icono }}"></span></span>
                                    <h4 class="text-1000 mb-2">{{ $o->nombre }}</h4>
                                    <p class="text-700 fs--1 mb-3">{{ \Illuminate\Support\Str::limit($o->resumen, 140) }}</p>
                                    <span class="fs--1 fw-bold text-primary">Ver detalle<span class="fa-solid fa-angle-right ms-2"></span></span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Solicitar demostración o prueba --}}
    @if ($sistema->ofreceDemo() || $sistema->ofrecePrueba() || $sistema->proximamente)
        <section class="py-10" id="solicitar">
            <div class="container-small px-lg-7 px-xxl-3">
                <div class="row g-6 align-items-center">
                    <div class="col-lg-5 text-center text-lg-start">
                        @if ($sistema->proximamente)
                            <h5 class="text-info mb-3">Próximamente</h5>
                            <h2 class="mb-3">Estamos terminando este sistema</h2>
                            <p class="text-800 mb-5">Déjanos tus datos y te avisamos en cuanto esté disponible, con una demostración para tu institución.</p>
                        @else
                        <h5 class="text-info mb-3">¿Te gustaría verlo funcionando?</h5>
                        <h2 class="mb-3">{{ $sistema->ofrecePrueba() ? 'Pruébalo o pide una demostración' : 'Pide una demostración' }}</h2>
                        <ul class="list-unstyled text-800 mb-5">
                            @if ($sistema->ofreceDemo())
                                <li class="mb-2"><span class="fa-solid fa-display text-primary me-2"></span><strong>Demostración:</strong> te mostramos el sistema en una llamada y resolvemos tus dudas.</li>
                            @endif
                            @if ($sistema->ofrecePrueba())
                                <li class="mb-2"><span class="fa-solid fa-flask text-success me-2"></span><strong>Prueba de {{ $sistema->dias_prueba }} días:</strong> te enviamos por correo un usuario y contraseña para que lo uses tú mismo.</li>
                            @endif
                            <li class="mb-2"><span class="fa-solid fa-circle-check text-success me-2"></span>Sin compromiso de compra.</li>
                        </ul>
                        @endif
                        @if ($wa = $cfg->enlaceWhatsapp('Hola, quiero información de '.$sistema->nombre.'.'))
                            <a class="btn btn-success" href="{{ $wa }}" target="_blank" rel="noopener"><span class="fa-brands fa-whatsapp me-2"></span>Prefiero WhatsApp</a>
                        @endif
                    </div>
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-5">
                                @include('publico.partes.formulario', ['sistemaFijo' => $sistema, 'ancla' => 'solicitar'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Ficha del sistema para Google. El precio es texto libre: solo se publica el de los gratuitos. --}}
    @push('datos_estructurados')
        @php
            $ficha = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => $sistema->nombre,
                'description' => $sistema->resumen,
                'url' => route('sistema', $sistema->slug),
                'image' => $sistema->url('imagen'),
                'applicationCategory' => 'BusinessApplication',
                'applicationSubCategory' => $sistema->categoria?->nombre,
                'operatingSystem' => 'Web',
                'offers' => $sistema->modalidad === 'gratis' ? ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'GTQ'] : null,
                'publisher' => ['@type' => 'Organization', 'name' => \App\Support\Sitio::nombre(), 'url' => route('inicio')],
            ]);
        @endphp
        <script type="application/ld+json">{!! json_encode($ficha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush
@endsection
