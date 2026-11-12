@extends('layouts.admin', ['titulo' => 'Chat en vivo'])

{{-- Bandeja del chat optimizada con componentes Phoenix y responsive fluido --}}
@section('contenido')
<style>
    /* Altura fluida y contenida para la bandeja de chat */
    .sgt-chat-container {
        height: calc(100vh - 12rem);
        min-height: 520px;
    }

    @media (max-width: 991.98px) {
        .sgt-chat-container {
            /* En móvil usa dvh dinámico respetando el teclado virtual */
            height: calc(100dvh - 9.5rem);
            min-height: 440px;
        }
    }

    /* Feedback de hover táctil refinado en la lista de conversaciones (Emil Kowalski) */
    @media (hover: hover) {
        .chat-thread-tab .nav-link {
            transition: background-color 140ms ease, transform 140ms cubic-bezier(0.23, 1, 0.32, 1);
        }
        .chat-thread-tab .nav-link:hover {
            transform: translateX(3px);
        }
    }

    .chat-thread-tab .nav-link:active {
        transform: scale(0.99);
    }

    /* Animación sutil de entrada para mensajes nuevos */
    @media (prefers-reduced-motion: no-preference) {
        @keyframes chatMsgIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .chat-msg-animate {
            animation: chatMsgIn 140ms cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }
    }
</style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="mb-1 text-1100">Chat en vivo</h2>
            <p class="text-700 mb-0 fs--1">
                Conversaciones con los visitantes del sitio.
                <span id="sgtEstadoConexion" class="badge badge-phoenix badge-phoenix-secondary ms-1">Conectando…</span>
            </p>
        </div>
        <ul class="nav nav-phoenix-pills">
            <li class="nav-item"><a class="nav-link {{ $estado === 'abierta' ? 'active' : '' }}" href="{{ route('admin.chat.index') }}">Abiertas ({{ $abiertas }})</a></li>
            <li class="nav-item"><a class="nav-link {{ $estado === 'cerrada' ? 'active' : '' }}" href="{{ route('admin.chat.index', ['estado' => 'cerrada']) }}">Cerradas ({{ $cerradas }})</a></li>
        </ul>
    </div>

    <div class="row g-3 sgt-chat-container">
        {{-- ========================================================
             COLUMNA IZQUIERDA: Lista de Conversaciones
             En móvil (<992px): Si hay conversación activa se oculta (d-none d-lg-block).
             Si no hay conversación activa, ocupa el 100% (col-12).
             ======================================================== --}}
        <div class="col-12 col-lg-4 col-xl-3 h-100 {{ $actual ? 'd-none d-lg-block' : '' }}">
            <div class="card h-100 border border-200">
                <div class="card-header border-bottom border-200 p-3">
                    <span class="text-uppercase fs--2 fw-bold text-600">Bandeja de Mensajes</span>
                </div>
                <div class="card-body p-2 scrollbar" style="overflow-y: auto;">
                    <ul class="nav chat-thread-tab flex-column" id="sgtListaConversaciones">
                        @forelse ($conversaciones as $c)
                            <li class="nav-item" data-conversacion="{{ $c->id }}">
                                <a class="nav-link d-flex align-items-center p-2 rounded-2 {{ $actual?->id === $c->id ? 'active bg-soft-primary' : 'hover-bg-100' }}" href="{{ route('admin.chat.index', ['c' => $c->id, 'estado' => $estado]) }}">
                                    <div class="avatar avatar-xl me-2 flex-shrink-0">
                                        <div class="avatar-name rounded-circle border border-2 border-white">
                                            <span>{{ $c->iniciales() }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="text-900 fw-{{ $c->no_leidos_admin ? 'bold' : 'normal' }} mb-0 text-truncate fs--1">{{ $c->nombre }}</h5>
                                            <p class="fs--2 text-600 mb-0 text-nowrap ms-2">{{ $c->ultimo_mensaje_en?->diffForHumans(short: true) }}</p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <p class="fs--2 mb-0 text-600 text-truncate sgt-ultimo">{{ $c->ultimoMensaje?->autor === 'admin' ? 'Tú: ' : '' }}{{ $c->ultimoMensaje?->cuerpo }}</p>
                                            <span class="badge rounded-pill bg-primary ms-2 sgt-no-leidos {{ $c->no_leidos_admin ? '' : 'd-none' }}">{{ $c->no_leidos_admin }}</span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="text-center text-700 fs--1 p-4">
                                <span class="fa-regular fa-comments fs-3 d-block mb-2 text-400"></span>
                                {{ $estado === 'abierta' ? 'Todavía no hay conversaciones. Cuando un visitante escriba en el chat del sitio aparecerá aquí.' : 'No hay conversaciones cerradas.' }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- ========================================================
             COLUMNA DERECHA: Conversación activa o placeholder
             En móvil (<992px): Si hay conversación activa ocupa el 100% (col-12).
             Si no hay conversación activa, en móvil se oculta (d-none d-lg-block).
             ======================================================== --}}
        <div class="col-12 col-lg-8 col-xl-9 h-100 {{ $actual ? '' : 'd-none d-lg-block' }}">
            @if ($actual)
                <div class="card h-100 d-flex flex-column border border-200 overflow-hidden" id="sgtConversacion" data-id="{{ $actual->id }}" data-canal="{{ $actual->canal() }}"
                     data-mensajes="{{ route('admin.chat.mensajes', $actual) }}" data-responder="{{ route('admin.chat.responder', $actual) }}">
                    
                    {{-- Header de la conversación con botón Volver para móvil --}}
                    <div class="card-header p-3 border-bottom border-200 d-flex justify-content-between align-items-center flex-wrap gap-2 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2">
                            {{-- Botón Volver (solo visible en móvil <992px) --}}
                            <a href="{{ route('admin.chat.index', ['estado' => $estado]) }}" class="btn btn-phoenix-secondary btn-sm d-lg-none px-2 me-1" title="Volver a la lista">
                                <span class="fa-solid fa-arrow-left"></span>
                            </a>
                            
                            <div class="avatar avatar-xl flex-shrink-0">
                                <div class="avatar-name rounded-circle border border-2 border-white">
                                    <span>{{ $actual->iniciales() }}</span>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0 text-1000 fs-0">{{ $actual->nombre }}
                                    @if ($actual->estado === 'cerrada')<span class="badge badge-phoenix badge-phoenix-secondary fs--2 ms-1">Cerrada</span>@endif
                                    @if ($actual->cuenta_id)<a class="badge badge-phoenix badge-phoenix-success fs--2 ms-1" href="{{ route('admin.cuentas.edit', $actual->cuenta_id) }}" title="La ve en «Mi cuenta»">Tiene cuenta</a>@endif
                                </h5>
                                <p class="fs--2 text-700 mb-0">
                                    <a href="mailto:{{ $actual->correo }}">{{ $actual->correo }}</a>
                                    @if ($actual->telefono) &middot; {{ $actual->telefono }}@endif
                                    @if ($actual->pagina) &middot; <span title="Página donde empezó">{{ $actual->pagina }}</span>@endif
                                </p>
                            </div>
                        </div>

                        {{-- Acciones del header --}}
                        <div class="d-flex align-items-center gap-2">
                            @if ($actual->estado === 'abierta')
                                <form method="POST" action="{{ route('admin.chat.cerrar', $actual) }}" onsubmit="return confirm('¿Cerrar la conversación? Al visitante le aparecerá el aviso y podrá pedir una copia por correo; su navegador la olvidará.')">
                                    @csrf
                                    <button class="btn btn-phoenix-secondary btn-sm" type="submit">
                                        <span class="fa-solid fa-circle-xmark me-1"></span>Cerrar
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.chat.destroy', $actual) }}" onsubmit="return confirm('¿Eliminar esta conversación y sus mensajes?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-phoenix-danger btn-sm" type="submit" title="Eliminar">
                                    <span class="fa-solid fa-trash"></span>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Cuerpo de Mensajes con flex-grow fluido y scrollbar --}}
                    <div class="card-body p-3 p-sm-4 scrollbar flex-grow-1" id="sgtMensajes" style="overflow-y: auto; overscroll-behavior: contain;"></div>

                    {{-- Footer de Escritura / Notificación de Cierre --}}
                    <div class="card-footer p-3 border-top border-200 bg-body flex-shrink-0">
                        @if ($actual->estado === 'cerrada')
                            <p class="text-700 fs--1 mb-0 text-center"><span class="fa-solid fa-lock me-1"></span>Conversación cerrada el {{ $actual->cerrada_en?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}. Queda como historial; el visitante ya no la ve.</p>
                        @else
                            <form class="d-flex gap-2 align-items-end" id="sgtResponder">
                                <textarea class="form-control" name="cuerpo" rows="2" maxlength="2000" placeholder="Escribe tu respuesta… (Enter envía, Shift+Enter hace salto de línea)" aria-label="Respuesta" required></textarea>
                                <button class="btn btn-primary px-3 py-2 flex-shrink-0" type="submit">
                                    <span class="fa-solid fa-paper-plane"></span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="card h-100 border border-200 d-flex flex-center text-700 py-10">
                    <div class="text-center">
                        <span class="fa-regular fa-comment-dots fs-4 d-block mb-2 text-400"></span>
                        <p class="fs-0 mb-0">Elige una conversación para comenzar a chatear.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@if ($actual)
    @push('scripts')
        <script>
            (function () {
                var caja = document.getElementById('sgtConversacion');
                var lista = document.getElementById('sgtMensajes');
                var form = document.getElementById('sgtResponder');
                var ultimoId = 0, leidoHasta = {{ (int) $leidoHasta }}, vistos = {};
                var iniciales = @json($actual->iniciales());

                // ✓ Enviado / ✓✓ Visto en mis respuestas, según hasta dónde leyó el visitante.
                function marcas() {
                    lista.querySelectorAll('[data-mio]').forEach(function (el) {
                        var visto = Number(el.dataset.mio) <= leidoHasta;
                        el.innerHTML = visto ? '<span class="text-primary">✓✓ Visto</span>' : '✓ Enviado';
                    });
                }

                // Auto-scroll inteligente: baja automáticamente solo si el usuario ya estaba cerca del fondo
                function autoScroll(forzar) {
                    if (forzar) {
                        lista.scrollTop = lista.scrollHeight;
                        return;
                    }
                    var distanciaAlFondo = lista.scrollHeight - lista.scrollTop - lista.clientHeight;
                    if (distanciaAlFondo < 120) {
                        lista.scrollTop = lista.scrollHeight;
                    }
                }

                function agregar(m, esCargaInicial) {
                    if (!m || vistos[m.id]) return;
                    vistos[m.id] = true;
                    ultimoId = Math.max(ultimoId, m.id);

                    // Solo los mensajes nuevos en vivo llevan animación (no la carga inicial masiva)
                    var animClase = esCargaInicial ? '' : ' chat-msg-animate';
                    var html;

                    if (m.autor === 'sistema') {
                        html = '<div class="text-center my-3' + animClase + '"><span class="badge badge-phoenix badge-phoenix-secondary fs--2 px-3 py-2">' + sgtEscapar(m.cuerpo) + ' · ' + sgtEscapar(m.hora) + '</span></div>';
                    } else if (m.autor === 'admin') {
                        html = '<div class="d-flex chat-message' + animClase + '"><div class="d-flex mb-2 justify-content-end flex-1"><div class="w-100 w-xxl-75"><div class="d-flex flex-end-center"><div class="chat-message-content me-2"><div class="mb-1 sent-message-content bg-primary rounded-3 p-3 text-white"><p class="mb-0" style="white-space:pre-line">' + sgtEscapar(m.cuerpo) + '</p></div></div></div><div class="text-end"><p class="mb-0 fs--2 text-600 fw-semi-bold">' + sgtEscapar((m.nombre ? m.nombre + ' · ' : '') + m.hora) + ' · <span data-mio="' + m.id + '"></span></p></div></div></div></div>';
                    } else {
                        html = '<div class="d-flex chat-message' + animClase + '"><div class="d-flex mb-2 flex-1"><div class="w-100 w-xxl-75"><div class="d-flex"><div class="avatar avatar-m me-3 flex-shrink-0"><div class="avatar-name rounded-circle border border-2 border-white"><span>' + sgtEscapar(iniciales) + '</span></div></div><div class="chat-message-content received me-2"><div class="mb-1 received-message-content border border-200 rounded-3 p-3 bg-body"><p class="mb-0" style="white-space:pre-line">' + sgtEscapar(m.cuerpo) + '</p></div></div></div><p class="mb-0 fs--2 text-600 fw-semi-bold ms-7">' + sgtEscapar(m.hora) + '</p></div></div></div>';
                    }

                    lista.insertAdjacentHTML('beforeend', html);
                    marcas();
                    autoScroll(esCargaInicial);
                }

                function aplicar(d) {
                    (d.mensajes || []).forEach(function (m) { agregar(m, false); });
                    if (typeof d.leido_hasta === 'number' && d.leido_hasta > leidoHasta) { leidoHasta = d.leido_hasta; marcas(); }
                }

                // Solo se marca como leído si esta pestaña del navegador está a la vista.
                function consultar() {
                    var leer = document.visibilityState === 'visible' ? '&leer=1' : '';
                    return sgtFetch(caja.dataset.mensajes + '?despues=' + ultimoId + leer).then(aplicar).catch(function () {});
                }

                // Carga inicial de mensajes existentes (sin animación de entrada en lote)
                @json($mensajes).forEach(function (m) { agregar(m, true); });

                // Mensajes nuevos de ESTA conversación en vivo (el aviso general del panel se encarga del resto).
                document.addEventListener('sgt:mensaje', function (e) {
                    if (e.detail.mensaje.conversacion_id === Number(caja.dataset.id)) {
                        agregar(e.detail.mensaje, false);
                        consultar();
                    }
                });

                // Cuando el visitante lee mis respuestas: ✓✓ Visto al instante.
                if (window.sgtPanelEcho) {
                    window.sgtPanelEcho.private(caja.dataset.canal).listen('.mensajes.leidos', function (e) {
                        if (e.lector === 'visitante' && e.hasta_id > leidoHasta) { leidoHasta = e.hasta_id; marcas(); }
                    });
                }

                // Al volver a esta pestaña, lo pendiente queda leído.
                document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') consultar(); });

                // Respaldo sin tiempo real.
                setInterval(function () {
                    if (window.sgtPanelEcho && window.sgtPanelEcho.connector.pusher.connection.state === 'connected') return;
                    consultar();
                }, 6000);

                if (!form) return; // conversación cerrada: solo lectura
                var campo = form.querySelector('textarea');
                campo.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
                });

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var texto = campo.value.trim();
                    if (!texto) return;
                    campo.value = '';
                    sgtFetch(caja.dataset.responder, { method: 'POST', body: { cuerpo: texto } }, window.sgtPanelEcho)
                        .then(function (d) { 
                            agregar(d.mensaje, false); 
                            autoScroll(true);
                        })
                        .catch(function (err) { campo.value = texto; alert(err.status === 409 ? 'La conversación está cerrada.' : 'No se pudo enviar la respuesta.'); });
                });

                campo.focus();
            })();
        </script>
    @endpush
@endif
