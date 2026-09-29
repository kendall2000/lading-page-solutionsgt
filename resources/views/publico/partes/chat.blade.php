{{-- Chat en vivo para visitantes: widget flotante de la plantilla (landing/alternate «support-chat»). --}}
@php
    $cfgChat = \App\Support\Sitio::config();
    $tituloChat = $cfgChat->chat_titulo ?: 'Chatea con nosotros';
    $bienvenida = $cfgChat->chat_bienvenida ?: 'Escríbenos y te respondemos aquí mismo. Si no estamos conectados, te contestamos por correo.';
@endphp
<style>
    .sgt-burbujas { display: flex; flex-direction: column; gap: .5rem; }
    .sgt-burbuja { max-width: 80%; padding: .55rem .85rem; border-radius: 1rem; font-size: .875rem; line-height: 1.35; white-space: pre-line; word-wrap: break-word; }
    .sgt-burbuja small { display: block; font-size: .7rem; opacity: .75; margin-top: .15rem; }
    .sgt-burbuja-yo { align-self: flex-end; background: var(--phoenix-primary); color: #fff; border-bottom-right-radius: .25rem; }
    .sgt-burbuja-yo small { text-align: right; }
    .sgt-burbuja-otro { align-self: flex-start; background: var(--phoenix-gray-100); color: var(--phoenix-gray-1000); border-bottom-left-radius: .25rem; }
    .sgt-aviso-sistema { align-self: center; font-size: .75rem; color: var(--phoenix-gray-700); background: var(--phoenix-gray-100); border-radius: 1rem; padding: .25rem .75rem; }
    .sgt-chat-punto { position: absolute; top: .35rem; right: .6rem; width: .7rem; height: .7rem; border-radius: 50%; background: var(--phoenix-danger); display: none; }
    .btn-support-chat.sgt-nuevo .sgt-chat-punto { display: block; }
</style>
<div class="support-chat-container show" id="sgtChat" data-estado="{{ route('chat.estado') }}" data-iniciar="{{ route('chat.iniciar') }}"
     data-mensajes="{{ route('chat.mensajes') }}" data-enviar="{{ route('chat.enviar') }}" data-auth="{{ route('chat.auth') }}"
     data-leer="{{ route('chat.leer') }}" data-copia="{{ route('chat.copia') }}" data-salir="{{ route('chat.salir') }}">
    <div class="container-fluid support-chat">
        <div class="card bg-white">
            <div class="card-header d-flex flex-between-center px-4 py-3 border-bottom">
                <h5 class="mb-0 d-flex align-items-center gap-2">{{ $tituloChat }}<span class="fa-solid fa-circle text-success fs--3"></span></h5>
                <button class="btn btn-link p-0 text-900 btn-support-chat-cerrar" type="button" aria-label="Cerrar ventana del chat"><span class="fa-solid fa-xmark"></span></button>
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
                        <input type="hidden" name="llegada" value="{{ \App\Support\Antispam::marca() }}" />
                        <input class="form-control form-control-sm mb-2" name="nombre" placeholder="Tu nombre *" maxlength="120" required aria-label="Nombre" />
                        <input class="form-control form-control-sm mb-2" name="correo" type="email" placeholder="Tu correo *" maxlength="150" required aria-label="Correo" />
                        <textarea class="form-control form-control-sm mb-2" name="mensaje" rows="3" placeholder="¿En qué te ayudamos? *" maxlength="2000" required aria-label="Mensaje"></textarea>
                        <div class="text-danger fs--1 mb-2 d-none" id="sgtChatError"></div>
                        <button class="btn btn-primary btn-sm w-100" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>Empezar chat</button>
                        @include('publico.partes.aviso-privacidad')
                    </form>

                    {{-- Mensajes (con conversación) --}}
                    <div class="sgt-burbujas mt-auto d-none" id="sgtChatMensajes"></div>

                    {{-- Aviso de conversación cerrada por soporte --}}
                    <div class="border border-300 rounded-3 p-3 mt-3 text-center d-none" id="sgtChatCerrada">
                        <p class="fw-bold mb-1"><span class="fa-solid fa-circle-check text-success me-2"></span>La conversación fue cerrada</p>
                        <p class="fs--1 text-700 mb-3">Gracias por escribirnos. Como no tienes una cuenta, esta conversación no se guardará en este navegador. ¿Quieres una copia por correo?</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-phoenix-primary btn-sm" type="button" id="sgtChatCopia"><span class="fa-solid fa-envelope me-2"></span>Enviarme una copia por correo</button>
                            <button class="btn btn-primary btn-sm" type="button" id="sgtChatNuevo"><span class="fa-solid fa-comment-medical me-2"></span>Empezar un chat nuevo</button>
                        </div>
                        <p class="fs--1 mt-2 mb-0 d-none" id="sgtChatCopiaResultado"></p>
                    </div>
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
        var $ = function (id) { return document.getElementById(id); };
        var cuerpo = $('sgtChatCuerpo'), lista = $('sgtChatMensajes'), inicio = $('sgtChatInicio'), envio = $('sgtChatEnvio');
        var error = $('sgtChatError'), cerradaBox = $('sgtChatCerrada');
        var boton = raiz.querySelector('.btn-support-chat');
        var ventana = raiz.querySelector('.support-chat');
        var conversacion = null, ultimoId = 0, leidoHasta = 0, echo = null, vistos = {}, sondeo = null, porLeer = false, cerrada = false;

        function abierto() { return ventana.classList.contains('show-chat') && document.visibilityState === 'visible'; }
        function alFinal() { cuerpo.scrollTop = cuerpo.scrollHeight; }

        // ✓ Enviado / ✓✓ Visto en mis mensajes, según hasta dónde leyó soporte.
        function marcas() {
            lista.querySelectorAll('[data-mio]').forEach(function (el) {
                el.textContent = Number(el.dataset.mio) <= leidoHasta ? '✓✓ Visto' : '✓ Enviado';
            });
        }

        // Solo se marca como leído lo que el visitante tiene a la vista (ventana abierta y pestaña visible).
        function leer() {
            if (!conversacion || !porLeer || !abierto()) return;
            porLeer = false;
            boton.classList.remove('sgt-nuevo');
            sgtFetch(url.leer, { method: 'POST' }, echo).catch(function () { porLeer = true; });
        }

        function agregar(m) {
            if (!m || vistos[m.id]) return;
            vistos[m.id] = true;
            ultimoId = Math.max(ultimoId, m.id);
            var div = document.createElement('div');
            if (m.autor === 'sistema') {
                div.className = 'sgt-aviso-sistema';
                div.textContent = m.cuerpo;
            } else {
                var mio = m.autor === 'visitante';
                div.className = 'sgt-burbuja ' + (mio ? 'sgt-burbuja-yo' : 'sgt-burbuja-otro');
                div.innerHTML = sgtEscapar(m.cuerpo) + '<small>' + (!mio && m.nombre ? sgtEscapar(m.nombre) + ' · ' : '') + sgtEscapar(m.hora)
                    + (mio ? ' · <span data-mio="' + m.id + '"></span>' : '') + '</small>';
                if (!mio && !m.leido) {
                    porLeer = true;
                    if (!abierto()) boton.classList.add('sgt-nuevo');
                }
            }
            lista.appendChild(div);
            marcas();
            alFinal();
            leer();
        }

        function aplicar(d) {
            (d.mensajes || []).forEach(agregar);
            if (typeof d.leido_hasta === 'number' && d.leido_hasta > leidoHasta) { leidoHasta = d.leido_hasta; marcas(); }
            if (d.estado === 'cerrada' || (d.conversacion && d.conversacion.estado === 'cerrada')) mostrarCerrada();
        }

        function mostrarConversacion(d) {
            conversacion = d.conversacion;
            if (!conversacion) return;
            inicio.classList.add('d-none');
            lista.classList.remove('d-none');
            envio.classList.remove('d-none');
            aplicar(d);
            conectar();
        }

        // Soporte cerró: se ocultan los campos y se ofrecen la copia por correo y un chat nuevo.
        function mostrarCerrada() {
            if (cerrada) return;
            cerrada = true;
            envio.classList.add('d-none');
            cerradaBox.classList.remove('d-none');
            if (!abierto()) boton.classList.add('sgt-nuevo');
            alFinal();
        }

        function reiniciar() {
            if (echo && conversacion) echo.leave(conversacion.canal);
            clearInterval(sondeo);
            conversacion = null; ultimoId = 0; leidoHasta = 0; vistos = {}; porLeer = false; cerrada = false;
            lista.innerHTML = '';
            lista.classList.add('d-none');
            envio.classList.add('d-none');
            cerradaBox.classList.add('d-none');
            $('sgtChatCopiaResultado').classList.add('d-none');
            $('sgtChatCopia').disabled = false;
            inicio.querySelector('textarea').value = '';
            inicio.classList.remove('d-none');
        }

        // Tiempo real: canal privado de la conversación (autorizado con la cookie del visitante).
        function conectar() {
            if (!conversacion) return;
            if (!echo) echo = sgtCrearEcho(url.auth);
            if (echo) {
                echo.private(conversacion.canal)
                    .listen('.mensaje.enviado', function (e) { agregar(e.mensaje); })
                    .listen('.mensajes.leidos', function (e) {
                        if (e.lector === 'admin' && e.hasta_id > leidoHasta) { leidoHasta = e.hasta_id; marcas(); }
                    })
                    .listen('.conversacion.cerrada', mostrarCerrada);
            }
            // Respaldo: si no hay conexión en vivo, se consulta cada cierto tiempo (mensajes, lectura y cierre).
            clearInterval(sondeo);
            sondeo = setInterval(function () {
                var enVivo = echo && echo.connector.pusher.connection.state === 'connected';
                if (enVivo || !conversacion || cerrada) return;
                sgtFetch(url.mensajes + '?despues=' + ultimoId).then(aplicar).catch(function () {});
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
            var datos = { nombre: f.get('nombre'), correo: f.get('correo'), mensaje: f.get('mensaje'), pagina: location.pathname, llegada: f.get('llegada') };
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
                .catch(function (err) {
                    if (err.status === 409) { mostrarCerrada(); return; }
                    campo.value = texto;
                    alert(err.status === 429 ? 'Vas muy rápido: espera unos segundos.' : 'No se pudo enviar el mensaje.');
                });
        });

        $('sgtChatCopia').addEventListener('click', function () {
            var btn = this, res = $('sgtChatCopiaResultado');
            btn.disabled = true;
            sgtFetch(url.copia, { method: 'POST' })
                .then(function (d) { res.className = 'fs--1 mt-2 mb-0 text-success'; res.textContent = d.message; })
                .catch(function (err) {
                    btn.disabled = false;
                    res.className = 'fs--1 mt-2 mb-0 text-danger';
                    res.textContent = err.status === 429 ? 'Ya pediste varias copias; intenta más tarde.' : (err.message || 'No se pudo enviar la copia.');
                });
        });
        $('sgtChatNuevo').addEventListener('click', function () {
            sgtFetch(url.salir, { method: 'POST' }).finally(reiniciar);
        });

        // Abrir / cerrar la ventana (la plantilla alterna .show-chat con .btn-support-chat).
        boton.addEventListener('click', function () {
            setTimeout(function () { if (abierto()) { boton.classList.remove('sgt-nuevo'); alFinal(); leer(); } }, 50);
        });
        raiz.querySelector('.btn-support-chat-cerrar').addEventListener('click', function () { boton.click(); });
        document.addEventListener('visibilitychange', leer);

        // Si el visitante ya chateó antes (cookie) y la conversación sigue abierta, se carga.
        sgtFetch(url.estado).then(mostrarConversacion).catch(function () {});
    })();
</script>
