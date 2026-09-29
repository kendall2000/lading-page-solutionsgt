{{-- Chat en vivo para visitantes: widget flotante de la plantilla (landing/alternate «support-chat»). --}}
@php
    $cfgChat = \App\Support\Sitio::config();
    $tituloChat = $cfgChat->chat_titulo ?: 'Chatea con nosotros';
    $bienvenida = $cfgChat->chat_bienvenida ?: 'Escríbenos y te respondemos aquí mismo. Si no estamos conectados, te contestamos por correo.';
@endphp
<style>
    .sgt-burbujas { display: flex; flex-direction: column; gap: .5rem; }
    .sgt-burbuja { max-width: 80%; padding: .55rem .85rem; border-radius: 1rem; font-size: .875rem; line-height: 1.35; white-space: pre-line; word-wrap: break-word; }
    .sgt-burbuja small { display: block; font-size: .7rem; opacity: .7; margin-top: .15rem; }
    .sgt-burbuja-yo { align-self: flex-end; background: var(--phoenix-primary); color: #fff; border-bottom-right-radius: .25rem; }
    .sgt-burbuja-otro { align-self: flex-start; background: var(--phoenix-gray-100); color: var(--phoenix-gray-1000); border-bottom-left-radius: .25rem; }
    .sgt-chat-punto { position: absolute; top: .35rem; right: .6rem; width: .7rem; height: .7rem; border-radius: 50%; background: var(--phoenix-danger); display: none; }
    .btn-support-chat.sgt-nuevo .sgt-chat-punto { display: block; }
</style>
<div class="support-chat-container show" id="sgtChat" data-estado="{{ route('chat.estado') }}" data-iniciar="{{ route('chat.iniciar') }}"
     data-mensajes="{{ route('chat.mensajes') }}" data-enviar="{{ route('chat.enviar') }}" data-auth="{{ route('chat.auth') }}">
    <div class="container-fluid support-chat">
        <div class="card bg-white">
            <div class="card-header d-flex flex-between-center px-4 py-3 border-bottom">
                <h5 class="mb-0 d-flex align-items-center gap-2">{{ $tituloChat }}<span class="fa-solid fa-circle text-success fs--3"></span></h5>
                <button class="btn btn-link p-0 text-900 btn-support-chat-cerrar" type="button" aria-label="Cerrar chat"><span class="fa-solid fa-xmark"></span></button>
            </div>
            <div class="card-body chat p-0">
                <div class="d-flex flex-column scrollbar h-100 p-3" id="sgtChatCuerpo">
                    <div class="text-center mb-3">
                        <div class="avatar avatar-3xl status-online mx-auto">
                            @if ($cfgChat->favicon)
                                <img class="rounded-circle border border-3 border-white" src="{{ $cfgChat->url('favicon') }}" alt="" />
                            @else
                                <div class="avatar-name rounded-circle border border-3 border-white"><span><span class="fa-solid fa-headset"></span></span></div>
                            @endif
                        </div>
                        <h5 class="mt-2 mb-2">{{ \App\Support\Sitio::nombre() }}</h5>
                        <p class="text-center text-700 fs--1 mb-0">{{ $bienvenida }}</p>
                    </div>

                    {{-- Formulario para empezar (sin conversación) --}}
                    <form class="mt-auto" id="sgtChatInicio" novalidate>
                        <div style="position:absolute; left:-10000px;" aria-hidden="true"><input type="text" name="empresa_web" tabindex="-1" autocomplete="off" /></div>
                        <input class="form-control form-control-sm mb-2" name="nombre" placeholder="Tu nombre *" maxlength="120" required aria-label="Nombre" />
                        <input class="form-control form-control-sm mb-2" name="correo" type="email" placeholder="Tu correo *" maxlength="150" required aria-label="Correo" />
                        <textarea class="form-control form-control-sm mb-2" name="mensaje" rows="3" placeholder="¿En qué te ayudamos? *" maxlength="2000" required aria-label="Mensaje"></textarea>
                        <div class="text-danger fs--1 mb-2 d-none" id="sgtChatError"></div>
                        <button class="btn btn-primary btn-sm w-100" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>Empezar chat</button>
                    </form>

                    {{-- Mensajes (con conversación) --}}
                    <div class="sgt-burbujas mt-auto d-none" id="sgtChatMensajes"></div>
                </div>
            </div>
            <form class="card-footer d-flex align-items-center gap-2 border-top ps-3 pe-4 py-3 d-none" id="sgtChatEnvio">
                <div class="d-flex align-items-center flex-1 gap-3 border rounded-pill px-4">
                    <input class="form-control outline-none border-0 flex-1 fs--1 px-0" name="cuerpo" type="text" placeholder="Escribe un mensaje" maxlength="2000" autocomplete="off" aria-label="Mensaje" />
                </div>
                <button class="btn p-0 border-0 send-btn" type="submit" aria-label="Enviar"><span class="fa-solid fa-paper-plane fs--1"></span></button>
            </form>
        </div>
    </div>
    <button class="btn p-0 border border-200 btn-support-chat position-fixed" type="button" aria-label="Abrir chat">
        <span class="sgt-chat-punto"></span>
        <span class="fs-0 btn-text text-primary text-nowrap">Chat en vivo</span><span class="fa-solid fa-circle text-success fs--1 ms-2"></span><span class="fa-solid fa-chevron-down text-primary fs-1"></span>
    </button>
</div>

@include('partials.echo')
<script>
    (function () {
        var raiz = document.getElementById('sgtChat');
        if (!raiz) return;
        var url = raiz.dataset;
        var cuerpo = document.getElementById('sgtChatCuerpo');
        var lista = document.getElementById('sgtChatMensajes');
        var inicio = document.getElementById('sgtChatInicio');
        var envio = document.getElementById('sgtChatEnvio');
        var error = document.getElementById('sgtChatError');
        var boton = raiz.querySelector('.btn-support-chat');
        var ventana = raiz.querySelector('.support-chat');
        var conversacion = null, ultimoId = 0, echo = null, vistos = {}, sondeo = null;

        function abierto() { return ventana.classList.contains('show-chat'); }
        function alFinal() { cuerpo.scrollTop = cuerpo.scrollHeight; }

        function agregar(m) {
            if (!m || vistos[m.id]) return;
            vistos[m.id] = true;
            ultimoId = Math.max(ultimoId, m.id);
            var div = document.createElement('div');
            div.className = 'sgt-burbuja ' + (m.autor === 'visitante' ? 'sgt-burbuja-yo' : 'sgt-burbuja-otro');
            div.innerHTML = sgtEscapar(m.cuerpo) + '<small>' + (m.autor === 'admin' && m.nombre ? sgtEscapar(m.nombre) + ' · ' : '') + sgtEscapar(m.hora) + '</small>';
            lista.appendChild(div);
            if (m.autor === 'admin' && !abierto()) boton.classList.add('sgt-nuevo');
            alFinal();
        }

        function mostrarConversacion(datos) {
            conversacion = datos.conversacion;
            if (!conversacion) return;
            inicio.classList.add('d-none');
            lista.classList.remove('d-none');
            envio.classList.remove('d-none');
            (datos.mensajes || []).forEach(agregar);
            conectar();
        }

        // Tiempo real: canal privado de la conversación (autorizado con la cookie del visitante).
        function conectar() {
            if (echo || !conversacion) return;
            echo = sgtCrearEcho(url.auth);
            if (echo) {
                echo.private(conversacion.canal).listen('.mensaje.enviado', function (e) { agregar(e.mensaje); });
            }
            // Respaldo: si no hay conexión en vivo, se consultan los mensajes nuevos cada cierto tiempo.
            clearInterval(sondeo);
            sondeo = setInterval(function () {
                var enVivo = echo && echo.connector.pusher.connection.state === 'connected';
                if (enVivo) return;
                sgtFetch(url.mensajes + '?despues=' + ultimoId).then(function (d) { (d.mensajes || []).forEach(agregar); }).catch(function () {});
            }, 8000);
        }

        function mostrarError(err) {
            var errores = err && err.datos && err.datos.errors ? Object.values(err.datos.errors).flat() : [err && err.status === 429 ? 'Demasiados intentos. Espera un momento.' : 'No se pudo enviar. Intenta de nuevo.'];
            error.textContent = errores.join(' ');
            error.classList.remove('d-none');
        }

        inicio.addEventListener('submit', function (e) {
            e.preventDefault();
            error.classList.add('d-none');
            var f = new FormData(inicio);
            var datos = { nombre: f.get('nombre'), correo: f.get('correo'), mensaje: f.get('mensaje'), pagina: location.pathname };
            if (f.get('empresa_web')) datos.empresa_web = f.get('empresa_web');
            var btn = inicio.querySelector('button');
            btn.disabled = true;
            sgtFetch(url.iniciar, { method: 'POST', body: datos }).then(mostrarConversacion).catch(mostrarError).finally(function () { btn.disabled = false; });
        });

        envio.addEventListener('submit', function (e) {
            e.preventDefault();
            var campo = envio.querySelector('input[name="cuerpo"]');
            var texto = campo.value.trim();
            if (!texto) return;
            campo.value = '';
            sgtFetch(url.enviar, { method: 'POST', body: { cuerpo: texto } }, echo)
                .then(function (d) { agregar(d.mensaje); })
                .catch(function (err) { campo.value = texto; alert(err.status === 429 ? 'Vas muy rápido: espera unos segundos.' : 'No se pudo enviar el mensaje.'); });
        });

        // Abrir / cerrar (la plantilla alterna .show-chat con .btn-support-chat).
        boton.addEventListener('click', function () {
            setTimeout(function () { if (abierto()) { boton.classList.remove('sgt-nuevo'); alFinal(); } }, 50);
        });
        raiz.querySelector('.btn-support-chat-cerrar').addEventListener('click', function () { boton.click(); });

        // Si el visitante ya chateó antes (cookie), se carga su conversación.
        sgtFetch(url.estado).then(mostrarConversacion).catch(function () {});
    })();
</script>
