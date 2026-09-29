{{--
    Laravel Echo + pusher-js (compilados en public/vendors, sin npm) conectados a Laravel Reverb.
    Define window.sgtCrearEcho(authEndpoint): devuelve una instancia de Echo o null si el tiempo real
    no está configurado. Solo se publica la llave pública; el secreto nunca sale del servidor.
--}}
@once
    <script src="{{ asset('vendors/pusher/pusher.min.js') }}"></script>
    <script src="{{ asset('vendors/echo/echo.iife.js') }}"></script>
    <script>
        window.sgtTiempoReal = @json(\App\Services\Chat::configEcho());
        window.sgtCrearEcho = function (authEndpoint) {
            var cfg = window.sgtTiempoReal;
            if (!cfg.activo || !cfg.host || !window.Echo || !window.Pusher) return null;
            try {
                return new window.Echo.default({
                    broadcaster: 'reverb',
                    key: cfg.key,
                    wsHost: cfg.host,
                    wsPort: cfg.port,
                    wssPort: cfg.port,
                    forceTLS: cfg.tls,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: authEndpoint,
                    auth: { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } },
                });
            } catch (e) {
                console.warn('[chat] Sin tiempo real:', e);
                return null;
            }
        };
        // fetch con CSRF, JSON y el socket de Echo (para que el evento no le vuelva a quien lo envía: toOthers).
        window.sgtFetch = function (url, opciones, echo) {
            opciones = opciones || {};
            var headers = Object.assign({
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }, opciones.headers || {});
            if (opciones.body && !(opciones.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
                opciones.body = JSON.stringify(opciones.body);
            }
            if (echo && echo.socketId()) headers['X-Socket-ID'] = echo.socketId();
            return fetch(url, Object.assign({}, opciones, { headers: headers, credentials: 'same-origin' })).then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (datos) {
                    if (!r.ok) { var err = new Error(datos.message || 'Error ' + r.status); err.datos = datos; err.status = r.status; throw err; }
                    return datos;
                });
            });
        };
        window.sgtEscapar = function (texto) {
            var div = document.createElement('div');
            div.textContent = texto == null ? '' : String(texto);
            return div.innerHTML;
        };
    </script>
@endonce
