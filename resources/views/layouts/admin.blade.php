<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
@php
    $mensajesNuevos = \App\Models\MensajeContacto::query()->where('estado', 'nuevo')->count();
    $menu = [
        ['admin.inicio', 'admin.inicio', 'pie-chart', 'Inicio'],
        ['admin.mensajes.index', 'admin.mensajes.*', 'mail', 'Mensajes'],
        ['admin.sistemas.index', 'admin.sistemas.*', 'grid', 'Sistemas'],
        ['admin.servicios.index', 'admin.servicios.*', 'tag', 'Servicios'],
        ['admin.clientes.index', 'admin.clientes.*', 'users', 'Clientes'],
        ['admin.direcciones.index', 'admin.direcciones.*', 'map-pin', 'Direcciones'],
        ['admin.sitio.edit', 'admin.sitio.*', 'sliders', 'Datos del sitio'],
        ['admin.correos.index', 'admin.correos.*', 'send', 'Correos'],
        ['admin.usuarios.index', 'admin.usuarios.*', 'shield', 'Usuarios'],
    ];
@endphp
<main class="main" id="top">
    <nav class="navbar navbar-vertical navbar-expand-lg">
        <script>
            var navbarStyle = window.config.config.phoenixNavbarStyle;
            if (navbarStyle && navbarStyle !== 'transparent') {
                document.querySelector('body').classList.add(`navbar-${navbarStyle}`);
            }
        </script>
        <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content">
                <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                    <li class="nav-item">
                        <p class="navbar-vertical-label">Panel</p>
                        <hr class="navbar-vertical-line" />
                        @foreach ($menu as [$ruta, $patron, $icono, $texto])
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs($patron) ? 'active' : '' }}" href="{{ route($ruta) }}" role="button">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span data-feather="{{ $icono }}"></span></span>
                                        <span class="nav-link-text-wrapper"><span class="nav-link-text">{{ $texto }}</span></span>
                                        @if ($ruta === 'admin.mensajes.index' && $mensajesNuevos > 0)
                                            <span class="badge ms-2 badge-phoenix badge-phoenix-primary nav-link-badge">{{ $mensajesNuevos }}</span>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </li>
                    <li class="nav-item">
                        <p class="navbar-vertical-label">Sitio</p>
                        <hr class="navbar-vertical-line" />
                        <div class="nav-item-wrapper">
                            <a class="nav-link label-1" href="{{ route('inicio') }}" target="_blank" rel="noopener" role="button">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span data-feather="external-link"></span></span>
                                    <span class="nav-link-text-wrapper"><span class="nav-link-text">Ver sitio público</span></span>
                                </div>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="navbar-vertical-footer">
            <button class="btn navbar-vertical-toggle border-0 fw-semi-bold w-100 white-space-nowrap d-flex align-items-center">
                <span class="uil uil-left-arrow-to-left fs-0"></span><span class="uil uil-arrow-from-right fs-0"></span>
                <span class="navbar-vertical-footer-text ms-2">Contraer menú</span>
            </button>
        </div>
    </nav>

    <nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault">
        <div class="collapse navbar-collapse justify-content-between">
            <div class="navbar-logo">
                <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Abrir menú">
                    <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
                </button>
                <a class="navbar-brand me-1 me-sm-3" href="{{ route('admin.inicio') }}">
                    <div class="d-flex align-items-center">
                        @include('partials.logo', ['claseTexto' => 'd-none d-sm-block'])
                    </div>
                </a>
            </div>
            <ul class="navbar-nav navbar-nav-icons flex-row">
                <li class="nav-item">
                    <div class="theme-control-toggle fa-icon-wait px-2">
                        <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle" />
                        <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" title="Modo oscuro"><span class="icon" data-feather="moon"></span></label>
                        <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" title="Modo claro"><span class="icon" data-feather="sun"></span></label>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.mensajes.index', ['estado' => 'nuevo']) }}" title="Mensajes nuevos">
                        <span data-feather="bell" style="height:20px;width:20px;"></span>
                        @if ($mensajesNuevos > 0)<span class="badge rounded-pill bg-danger fs--2 position-absolute" style="margin-left:-8px;margin-top:-4px;">{{ $mensajesNuevos }}</span>@endif
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar avatar-l">
                            <div class="avatar-name rounded-circle"><span>{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span></div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border border-300" aria-labelledby="navbarDropdownUser">
                        <div class="card position-relative border-0">
                            <div class="card-body p-0">
                                <div class="text-center pt-4 pb-3">
                                    <h6 class="mt-2 text-1000 mb-0">{{ auth()->user()->name }}</h6>
                                    <p class="fs--1 text-700 mb-0">{{ auth()->user()->email }}</p>
                                </div>
                            </div>
                            <ul class="nav d-flex flex-column mb-2 pb-1">
                                <li class="nav-item"><a class="nav-link px-3" href="{{ route('admin.cuenta') }}"><span class="me-2 text-900" data-feather="lock"></span>Seguridad de mi cuenta</a></li>
                            </ul>
                            <div class="card-footer p-0 border-top">
                                <div class="px-3 my-3">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-phoenix-secondary d-flex flex-center w-100"><span class="me-2" data-feather="log-out"></span>Cerrar sesión</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content">
        @include('partials.alertas')
        @yield('contenido')
        <footer class="footer position-absolute">
            <div class="row g-0 justify-content-between align-items-center h-100">
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 mt-2 mt-sm-0 text-900">{{ \App\Support\Sitio::nombre() }} &copy; {{ date('Y') }}</p>
                </div>
            </div>
        </footer>
    </div>
</main>
@include('partials.scripts')
</body>
</html>
