<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    @php
        $cfg = \App\Support\Sitio::config();
        $descripcion = $descripcion ?? ($cfg->meta_descripcion ?: $cfg->eslogan);
        // En la portada los enlaces del menú son anclas; en otras páginas llevan a la portada.
        $base = request()->routeIs('inicio') ? '' : route('inicio');
        $whatsapp = $cfg->enlaceWhatsapp('Hola, vi tu sitio web y quiero información sobre tus sistemas.');
    @endphp
    <meta name="description" content="{{ $descripcion }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ \App\Support\Sitio::nombre() }}">
    <meta property="og:title" content="{{ isset($titulo) ? $titulo.' · ' : '' }}{{ \App\Support\Sitio::nombre() }}">
    <meta property="og:description" content="{{ $descripcion }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($imagenOg = ($imagenOg ?? $cfg->url('imagen_hero')))
        <meta property="og:image" content="{{ $imagenOg }}">
    @endif
    <style>
        .sgt-whatsapp { position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 1030; width: 3.5rem; height: 3.5rem; border-radius: 50%; background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.2); transition: transform .2s; }
        .sgt-whatsapp:hover { color: #fff; transform: scale(1.08); }
        .sgt-icono-sistema { width: 3.5rem; height: 3.5rem; border-radius: 1rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; background: rgba(var(--phoenix-primary-rgb), .1); color: var(--phoenix-primary); }
        .sgt-captura { border-radius: .75rem; box-shadow: 0 1.5rem 3rem -1rem rgba(36, 40, 46, .25); }
        .sgt-foto { width: 100%; max-width: 380px; aspect-ratio: 4 / 5; object-fit: cover; border-radius: 1.5rem; box-shadow: 0 1.5rem 3rem -1rem rgba(36, 40, 46, .35); }
        .sgt-logo-cliente { max-height: 56px; max-width: 100%; filter: grayscale(1); opacity: .75; transition: all .2s; }
        .sgt-logo-cliente:hover { filter: none; opacity: 1; }
        .sgt-mapa { width: 100%; height: 381px; border: 0; border-radius: 1.5rem; }
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
                    <li class="nav-item border-bottom border-bottom-lg-0"><a class="nav-link lh-1 fs--1 fw-bold py-3" href="{{ $base }}#inicio">Inicio</a></li>
                    <li class="nav-item border-bottom border-bottom-lg-0"><a class="nav-link lh-1 fs--1 fw-bold py-3" href="{{ $base }}#sistemas">Sistemas</a></li>
                    <li class="nav-item border-bottom border-bottom-lg-0"><a class="nav-link lh-1 fs--1 fw-bold py-3" href="{{ $base }}#sobre-mi">Sobre mí</a></li>
                    <li class="nav-item border-bottom border-bottom-lg-0"><a class="nav-link lh-1 fs--1 fw-bold py-3" href="{{ $base }}#servicios">Servicios</a></li>
                    <li class="nav-item"><a class="nav-link lh-1 fs--1 fw-bold py-3" href="{{ $base }}#contacto">Contacto</a></li>
                </ul>
                <div class="d-grid d-lg-flex align-items-center">
                    <div class="nav-item d-flex align-items-center d-none d-lg-block pe-2">
                        <div class="theme-control-toggle fa-icon-wait px-2">
                            <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle" />
                            <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" title="Modo oscuro"><span class="icon" data-feather="moon"></span></label>
                            <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" title="Modo claro"><span class="icon" data-feather="sun"></span></label>
                        </div>
                    </div>
                    <a class="btn btn-phoenix-primary order-0 my-3 my-lg-0" href="{{ $base }}#contacto"><span class="fw-bold">Solicitar una demo</span></a>
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
                    <ul class="list-unstyled d-flex justify-content-center flex-wrap mb-0 border-end-xl border-dashed border-800 gap-3 gap-xl-8 pe-xl-5 pe-xxl-8 w-75 w-md-100 mx-auto">
                        <li><a class="text-300 dark__text-300" href="{{ $base }}#sistemas">Sistemas</a></li>
                        <li><a class="text-300 dark__text-300" href="{{ $base }}#sobre-mi">Sobre mí</a></li>
                        <li><a class="text-300 dark__text-300" href="{{ $base }}#servicios">Servicios</a></li>
                        <li><a class="text-300 dark__text-300" href="{{ $base }}#contacto">Contacto</a></li>
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
                    <a class="text-600" href="{{ route('login') }}" rel="nofollow">Acceso</a>
                </p>
            </div>
        </div>
    </section>
</main>

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
</body>
</html>
