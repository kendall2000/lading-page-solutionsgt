<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
@php
    $mensajesNuevos = \App\Models\MensajeContacto::query()->where('estado', 'nuevo')->count();
    $chatSinLeer = (int) \App\Models\Conversacion::query()->sum('no_leidos_admin');
    $menu = [
        'Panel' => [
            ['admin.inicio', 'admin.inicio', 'pie-chart', 'Inicio'],
            ['admin.chat.index', 'admin.chat.*', 'message-circle', 'Chat en vivo'],
            ['admin.mensajes.index', 'admin.mensajes.*', 'inbox', 'Solicitudes y mensajes'],
        ],
        'Sitio web' => [
            ['admin.paginas.index', ['admin.paginas.*', 'admin.secciones.*'], 'layout', 'Páginas y menú'],
            ['admin.sistemas.index', 'admin.sistemas.*', 'package', 'Software'],
            ['admin.manuales.index', 'admin.manuales.*', 'book-open', 'Manuales'],
            ['admin.servicios.index', 'admin.servicios.*', 'tag', 'Planes y precios'],
            ['admin.clientes.index', 'admin.clientes.*', 'users', 'Clientes'],
            ['admin.direcciones.index', 'admin.direcciones.*', 'map-pin', 'Direcciones'],
            ['admin.sitio.edit', 'admin.sitio.*', 'sliders', 'Datos del sitio'],
        ],
        'Configuración' => [
            ['admin.correos.index', 'admin.correos.*', 'send', 'Correos'],
            ['admin.usuarios.index', 'admin.usuarios.*', 'shield', 'Usuarios'],
        ],
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
                    @foreach ($menu as $grupoMenu => $itemsMenu)
                    <li class="nav-item">
                        <p class="navbar-vertical-label">{{ $grupoMenu }}</p>
                        <hr class="navbar-vertical-line" />
                        @foreach ($itemsMenu as [$ruta, $patron, $icono, $texto])
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs(...(array) $patron) ? 'active' : '' }}" href="{{ route($ruta) }}" role="button">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span data-feather="{{ $icono }}"></span></span>
                                        <span class="nav-link-text-wrapper"><span class="nav-link-text">{{ $texto }}</span></span>
                                        @if ($ruta === 'admin.mensajes.index' && $mensajesNuevos > 0)
                                            <span class="badge ms-2 badge-phoenix badge-phoenix-primary nav-link-badge">{{ $mensajesNuevos }}</span>
                                        @endif
                                        @if ($ruta === 'admin.chat.index')
                                            <span class="badge ms-2 badge-phoenix badge-phoenix-danger nav-link-badge {{ $chatSinLeer ? '' : 'd-none' }}" id="sgtBadgeChat">{{ $chatSinLeer }}</span>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </li>
                    @endforeach
                    <li class="nav-item">
                        <p class="navbar-vertical-label">Público</p>
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
{{-- Avisos del chat en vivo: toast + contador en cualquier pantalla del panel. --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090" id="sgtToasts"></div>
@include('partials.echo')
<script>
    (function () {
        var badge = document.getElementById('sgtBadgeChat');
        var estado = document.getElementById('sgtEstadoConexion');
        var total = {{ $chatSinLeer }};
        var urlChat = @json(route('admin.chat.index'));
        var urlResumen = @json(route('admin.chat.resumen'));
        var abierta = document.getElementById('sgtConversacion');

        function ponerTotal(n) {
            total = Math.max(0, n);
            badge.textContent = total;
            badge.classList.toggle('d-none', total === 0);
        }
        function avisar(nombre, texto, id) {
            var t = document.createElement('div');
            t.className = 'toast align-items-center border-0 shadow';
            t.setAttribute('role', 'alert');
            t.innerHTML = '<div class="toast-header"><span class="fa-solid fa-comment-dots text-primary me-2"></span><strong class="me-auto">' + sgtEscapar(nombre) + '</strong><small>ahora</small>'
                + '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>'
                + '<a class="toast-body d-block text-900 text-decoration-none" href="' + urlChat + '?c=' + id + '">' + sgtEscapar(texto) + '</a>';
            document.getElementById('sgtToasts').appendChild(t);
            new bootstrap.Toast(t, { delay: 8000 }).show();
            t.addEventListener('hidden.bs.toast', function () { t.remove(); });
        }
        function marcarConexion(enVivo) {
            if (!estado) return;
            estado.className = 'badge badge-phoenix ms-1 badge-phoenix-' + (enVivo ? 'success' : 'warning');
            estado.textContent = enVivo ? 'En vivo' : 'Sin tiempo real: se actualiza cada pocos segundos';
        }

        // Tiempo real: canal privado del panel (autorizado con la sesión en /broadcasting/auth).
        var echo = window.sgtPanelEcho = sgtCrearEcho(@json(url('/broadcasting/auth')));
        if (echo) {
            echo.private('chat.panel').listen('.mensaje.enviado', function (e) {
                document.dispatchEvent(new CustomEvent('sgt:mensaje', { detail: e }));
                var viendola = abierta && Number(abierta.dataset.id) === e.conversacion.id;
                if (!viendola) {
                    ponerTotal(total + 1);
                    avisar(e.conversacion.nombre, e.mensaje.cuerpo, e.conversacion.id);
                }
                var item = document.querySelector('[data-conversacion="' + e.conversacion.id + '"]');
                if (item) {
                    item.querySelector('.sgt-ultimo').textContent = e.mensaje.cuerpo;
                    var nl = item.querySelector('.sgt-no-leidos');
                    if (!viendola) { nl.textContent = e.conversacion.no_leidos_admin; nl.classList.remove('d-none'); }
                    item.parentNode.prepend(item);
                }
            });
            var conexion = echo.connector.pusher.connection;
            conexion.bind('state_change', function (s) { marcarConexion(s.current === 'connected'); });
            marcarConexion(conexion.state === 'connected');
        } else {
            marcarConexion(false);
        }

        // Respaldo sin tiempo real: se consulta el total sin leer cada 20 segundos.
        setInterval(function () {
            if (echo && echo.connector.pusher.connection.state === 'connected') return;
            sgtFetch(urlResumen).then(function (d) {
                if (d.total > total && d.conversaciones.length && !abierta) {
                    avisar(d.conversaciones[0].nombre, 'Tienes mensajes nuevos en el chat.', d.conversaciones[0].id);
                }
                ponerTotal(d.total);
            }).catch(function () {});
        }, 20000);
    })();
</script>
@include('partials.scripts')
</body>
</html>
