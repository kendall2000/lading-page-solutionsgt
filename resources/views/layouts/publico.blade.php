<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    @php
        $cfg = \App\Support\Sitio::config();
        $menuSitio = \App\Support\Sitio::menu();
        $enlaceContacto = \App\Support\Sitio::enlaceContacto();
        $descripcion = ($descripcion ?? null) ?: ($cfg->meta_descripcion ?: $cfg->eslogan);
        $whatsapp = $cfg->enlaceWhatsapp('Hola, vi tu sitio web y quiero información sobre tus sistemas.');
        $actual = url()->current();
        // Sin imagen propia, al compartir el enlace se muestra el logo.
        $imagenOg = ($imagenOg ?? null) ?: $cfg->url('logo');
        // Datos de la empresa para Google (nombre, logo, contacto y redes).
        $organizacion = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => \App\Support\Sitio::nombre(),
            'url' => route('inicio'),
            'logo' => $cfg->url('logo'),
            'description' => $cfg->meta_descripcion ?: $cfg->eslogan,
            'email' => $cfg->correo,
            'telephone' => $cfg->telefono ?: $cfg->whatsapp,
            'sameAs' => array_values(array_column($cfg->redes(), 0)) ?: null,
        ]);
    @endphp
    <meta name="description" content="{{ $descripcion }}">
    <link rel="canonical" href="{{ $actual }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_GT">
    <meta property="og:site_name" content="{{ \App\Support\Sitio::nombre() }}">
    <meta property="og:title" content="{{ isset($titulo) ? $titulo.' · ' : '' }}{{ \App\Support\Sitio::nombre() }}">
    <meta property="og:description" content="{{ $descripcion }}">
    <meta property="og:url" content="{{ $actual }}">
    @if ($imagenOg)
        <meta property="og:image" content="{{ $imagenOg }}">
    @endif
    <meta name="twitter:card" content="{{ $imagenOg ? 'summary_large_image' : 'summary' }}">
    <script type="application/ld+json">{!! json_encode($organizacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @stack('datos_estructurados')
    <style>
        .sgt-whatsapp { position: fixed; left: 1.5rem; bottom: 2.5rem; z-index: 1030; width: 3.5rem; height: 3.5rem; border-radius: 50%; background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.2); transition: transform .2s; }
        .sgt-whatsapp:hover { color: #fff; transform: scale(1.08); }
        .sgt-icono { width: 3.5rem; height: 3.5rem; border-radius: 1rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; background: rgba(var(--phoenix-primary-rgb), .1); color: var(--phoenix-primary); flex-shrink: 0; }
        .sgt-icono-sm { width: 2.5rem; height: 2.5rem; border-radius: .75rem; font-size: 1.1rem; }
        .sgt-captura { border-radius: .75rem; box-shadow: 0 1.5rem 3rem -1rem rgba(36, 40, 46, .25); }
        .sgt-logo-cliente { max-height: 56px; max-width: 100%; filter: grayscale(1); opacity: .75; transition: all .2s; }
        .sgt-logo-cliente:hover { filter: none; opacity: 1; }
        .sgt-mapa { width: 100%; height: 381px; border: 0; border-radius: 1.5rem; }
        .sgt-tarjeta { transition: transform .2s, box-shadow .2s; }
        .sgt-tarjeta:hover { transform: translateY(-4px); box-shadow: 0 1rem 2rem -1rem rgba(36, 40, 46, .3); }
        .sgt-carrusel .carousel-item { height: var(--sgt-alto, 560px); }
        .sgt-carrusel .carousel-item > img { width: 100%; height: 100%; object-fit: cover; }
        .sgt-carrusel .carousel-caption { left: 0; right: 0; bottom: 0; top: 0; display: flex; align-items: center; background: linear-gradient(90deg, rgba(0,0,0,.65) 0%, rgba(0,0,0,.15) 70%); text-align: left; padding: 0; }
        .sgt-encabezado { background: linear-gradient(135deg, rgba(var(--phoenix-primary-rgb), .12), rgba(56, 171, 255, .08)); }
        .sgt-encabezado-img { background-size: cover; background-position: center; position: relative; }
        .sgt-encabezado-img::before { content: ''; position: absolute; inset: 0; background: rgba(0, 0, 0, .55); }
        .sgt-contenido p:last-child { margin-bottom: 0; }
        .sgt-contenido img { max-width: 100%; border-radius: .5rem; }
        .sgt-seccion-oscura, .sgt-seccion-oscura h1, .sgt-seccion-oscura h2, .sgt-seccion-oscura h3, .sgt-seccion-oscura h4, .sgt-seccion-oscura h5 { color: #fff; }
        .sgt-seccion-oscura p, .sgt-seccion-oscura .text-700 { color: rgba(255, 255, 255, .75) !important; }
        @media (max-width: 767.98px) { .sgt-carrusel .carousel-item { height: 420px; } }
    </style>
</head>
<body>
<main class="alternate-landing" style="--phoenix-scroll-margin-top: 1.2rem;">
    <div class="bg-white sticky-top landing-navbar" data-navbar-shadow-on-scroll="data-navbar-shadow-on-scroll">
        <nav class="navbar navbar-expand-lg container-small px-3 px-lg-7 px-xxl-3">
            <a class="navbar-brand flex-1 flex-lg-grow-0" href="{{ route('inicio') }}">
                <div class="d-flex align-items-center">@include('partials.logo', ['alto' => 30])</div>
            </a>
            <div class="d-lg-none">
                <div class="theme-control-toggle fa-icon-wait px-2">
                    <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggleSm" />
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggleSm" title="Modo oscuro"><span class="icon" data-feather="moon"></span></label>
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggleSm" title="Modo claro"><span class="icon" data-feather="sun"></span></label>
                </div>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @foreach ($menuSitio as $item)
                        @php $activo = $actual === $item->enlace() || $item->hijas->contains(fn ($h) => $actual === $h->enlace()); @endphp
                        @if ($item->hijas->isEmpty())
                            <li class="nav-item border-bottom border-bottom-lg-0">
                                <a class="nav-link lh-1 fs--1 fw-bold py-3 {{ $activo ? 'active' : '' }}" href="{{ $item->enlace() }}" @if ($activo) aria-current="page" @endif>{{ $item->nombreMenu() }}</a>
                            </li>
                        @else
                            <li class="nav-item dropdown border-bottom border-bottom-lg-0">
                                <a class="nav-link dropdown-toggle lh-1 fs--1 fw-bold py-3 {{ $activo ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $item->nombreMenu() }}</a>
                                <ul class="dropdown-menu border border-300 shadow-sm py-2">
                                    <li><a class="dropdown-item fs--1" href="{{ $item->enlace() }}">{{ $item->nombreMenu() }}</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    @foreach ($item->hijas as $hija)
                                        <li><a class="dropdown-item fs--1 {{ $actual === $hija->enlace() ? 'active' : '' }}" href="{{ $hija->enlace() }}">{{ $hija->nombreMenu() }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="d-grid d-lg-flex align-items-center">
                    <div class="nav-item d-flex align-items-center d-none d-lg-block pe-2">
                        <div class="theme-control-toggle fa-icon-wait px-2">
                            <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle" />
                            <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" title="Modo oscuro"><span class="icon" data-feather="moon"></span></label>
                            <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" title="Modo claro"><span class="icon" data-feather="sun"></span></label>
                        </div>
                    </div>
                    <a class="btn btn-phoenix-primary order-0 my-3 my-lg-0" href="{{ $enlaceContacto }}"><span class="fw-bold">Solicitar una demo</span></a>
                </div>
            </div>
        </nav>
    </div>

    @yield('contenido')

    {{-- Pie de página --}}
    <section class="bg-1100 dark__bg-1000 py-7">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="row gx-xxl-8 gy-5 align-items-center mb-5">
                <div class="col-xl-auto text-center">
                    <a class="d-inline-flex align-items-center text-white text-decoration-none fw-bolder fs-2" href="{{ route('inicio') }}">
                        @if ($cfg->logo_oscuro)
                            <img src="{{ $cfg->url('logo_oscuro') }}" alt="{{ \App\Support\Sitio::nombre() }}" style="max-height: 44px;" />
                        @else
                            <span class="fa-solid fa-code text-primary me-2"></span>{{ \App\Support\Sitio::nombre() }}
                        @endif
                    </a>
                </div>
                <div class="col-xl-auto flex-1">
                    <ul class="list-unstyled d-flex justify-content-center flex-wrap mb-0 border-end-xl border-dashed border-800 gap-3 gap-xl-6 pe-xl-5 pe-xxl-8 w-75 w-md-100 mx-auto">
                        @foreach ($menuSitio as $item)
                            <li><a class="text-300 dark__text-300" href="{{ $item->enlace() }}">{{ $item->nombreMenu() }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-xl-auto">
                    <div class="d-flex align-items-center justify-content-center gap-6">
                        @foreach ($cfg->redes() as $red => [$enlace, $icono])
                            <a class="text-white dark__text-white fs-1" href="{{ $enlace }}" target="_blank" rel="noopener" title="{{ ucfirst($red) }}"><span class="{{ $icono }}"></span></a>
                        @endforeach
                    </div>
                </div>
            </div>
            <hr class="text-800" />
            <div class="d-sm-flex flex-between-center text-center">
                <p class="text-600 mb-0">&copy; {{ date('Y') }} {{ \App\Support\Sitio::nombre() }}. Todos los derechos reservados.</p>
                <p class="text-600 mb-0">
                    @if ($cfg->eslogan){{ $cfg->eslogan }} · @endif
                    @if ($privacidad = \App\Support\Sitio::enlacePrivacidad())<a class="text-600" href="{{ $privacidad }}">Privacidad</a> · @endif
                    <a class="text-600" href="{{ route('login') }}" rel="nofollow">Acceso</a>
                </p>
            </div>
        </div>
    </section>
</main>

@if ($cfg->chat_activo)
    @include('publico.partes.chat')
@endif

@if ($whatsapp)
    <a class="sgt-whatsapp" href="{{ $whatsapp }}" target="_blank" rel="noopener" title="Escríbeme por WhatsApp" aria-label="WhatsApp"><span class="fa-brands fa-whatsapp"></span></a>
@endif

@push('vendors')
    <script src="{{ asset('vendors/isotope-layout/isotope.pkgd.min.js') }}"></script>
    <script src="{{ asset('vendors/isotope-packery/packery-mode.pkgd.min.js') }}"></script>
    <script src="{{ asset('vendors/bigpicture/BigPicture.js') }}"></script>
    <script src="{{ asset('vendors/lottie/lottie.min.js') }}"></script>
    <script src="{{ asset('vendors/countup/countUp.umd.js') }}"></script>
@endpush
@include('partials.scripts')
<script>
    // Filtros de galerías y del catálogo: cada botón dice a qué contenedor filtra (data-sgt-filtro) y con qué selector (data-valor).
    document.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-sgt-filtro]');
        if (!boton) return;
        e.preventDefault();
        var contenedor = document.querySelector(boton.dataset.sgtFiltro);
        if (!contenedor) return;
        var valor = boton.dataset.valor || '*';
        var iso = window.Isotope && window.Isotope.data(contenedor);
        if (iso) {
            iso.arrange({ filter: valor });
        } else {
            contenedor.querySelectorAll('[data-sgt-item]').forEach(function (item) {
                item.classList.toggle('d-none', valor !== '*' && !item.matches(valor));
            });
        }
        boton.closest('ul, .sgt-filtros').querySelectorAll('[data-sgt-filtro]').forEach(function (b) { b.classList.remove('active'); });
        boton.classList.add('active');
    });
</script>
</body>
</html>
