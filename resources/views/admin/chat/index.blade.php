@extends('layouts.admin', ['titulo' => 'Chat en vivo'])

{{-- Bandeja del chat (plantilla apps/chat.html): conversaciones a la izquierda, mensajes a la derecha. --}}
@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
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

    <div class="row g-3">
        {{-- Conversaciones --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-body p-2 scrollbar" style="max-height: 70vh">
                    <ul class="nav chat-thread-tab flex-column" id="sgtListaConversaciones">
                        @forelse ($conversaciones as $c)
                            <li class="nav-item" data-conversacion="{{ $c->id }}">
                                <a class="nav-link d-flex align-items-center p-2 rounded-2 {{ $actual?->id === $c->id ? 'active bg-soft-primary' : '' }}" href="{{ route('admin.chat.index', ['c' => $c->id, 'estado' => $estado]) }}">
                                    <div class="avatar avatar-xl me-2"><div class="avatar-name rounded-circle"><span>{{ $c->iniciales() }}</span></div></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="text-900 fw-{{ $c->no_leidos_admin ? 'bold' : 'normal' }} mb-0 text-truncate">{{ $c->nombre }}</h5>
                                            <p class="fs--2 text-600 mb-0 text-nowrap ms-2">{{ $c->ultimo_mensaje_en?->diffForHumans(short: true) }}</p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="fs--1 mb-0 text-600 text-truncate sgt-ultimo">{{ $c->ultimoMensaje?->autor === 'admin' ? 'Tú: ' : '' }}{{ $c->ultimoMensaje?->cuerpo }}</p>
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

        {{-- Conversación seleccionada --}}
        <div class="col-lg-8 col-xl-9">
            @if ($actual)
                <div class="card h-100 d-flex flex-column" id="sgtConversacion" data-id="{{ $actual->id }}" data-canal="{{ $actual->canal() }}"
                     data-mensajes="{{ route('admin.chat.mensajes', $actual) }}" data-responder="{{ route('admin.chat.responder', $actual) }}">
                    <div class="card-header p-3 d-flex flex-between-center gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-xl"><div class="avatar-name rounded-circle"><span>{{ $actual->iniciales() }}</span></div></div>
                            <div>
                                <h5 class="mb-0 text-1000">{{ $actual->nombre }}
                                    @if ($actual->estado === 'cerrada')<span class="badge badge-phoenix badge-phoenix-secondary fs--2 ms-1">Cerrada</span>@endif
                                </h5>
                                <p class="fs--1 text-700 mb-0">
                                    <a href="mailto:{{ $actual->correo }}">{{ $actual->correo }}</a>
                                    @if ($actual->telefono) · {{ $actual->telefono }}@endif
                                    @if ($actual->pagina) · <span title="Página donde empezó">{{ $actual->pagina }}</span>@endif
                                </p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('admin.chat.cerrar', $actual) }}">
                                @csrf
                                <button class="btn btn-phoenix-secondary btn-sm" type="submit">{{ $actual->estado === 'cerrada' ? 'Reabrir' : 'Cerrar conversación' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.chat.destroy', $actual) }}" onsubmit="return confirm('¿Eliminar esta conversación y sus mensajes?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-phoenix-danger btn-sm" type="submit" title="Eliminar"><span class="fa-solid fa-trash"></span></button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-3 p-sm-4 scrollbar flex-1" id="sgtMensajes" style="height: 55vh; overflow-y: auto;"></div>
                    <div class="card-footer p-3">
                        <form class="d-flex gap-2 align-items-end" id="sgtResponder">
                            <textarea class="form-control" name="cuerpo" rows="2" maxlength="2000" placeholder="Escribe tu respuesta… (Enter envía, Shift+Enter hace salto de línea)" aria-label="Respuesta" required></textarea>
                            <button class="btn btn-primary" type="submit"><span class="fa-solid fa-paper-plane"></span></button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card h-100"><div class="card-body d-flex flex-center text-700 py-10">Elige una conversación.</div></div>
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
                var ultimoId = 0, vistos = {};
                var iniciales = @json($actual->iniciales());

                function agregar(m) {
                    if (!m || vistos[m.id]) return;
                    vistos[m.id] = true;
                    ultimoId = Math.max(ultimoId, m.id);
                    var mio = m.autor === 'admin';
                    var html = mio
                        ? '<div class="d-flex chat-message"><div class="d-flex mb-2 justify-content-end flex-1"><div class="w-100 w-xxl-75"><div class="d-flex flex-end-center"><div class="chat-message-content me-2"><div class="mb-1 sent-message-content light bg-primary rounded-2 p-3 text-white"><p class="mb-0" style="white-space:pre-line">' + sgtEscapar(m.cuerpo) + '</p></div></div></div><div class="text-end"><p class="mb-0 fs--2 text-600 fw-semi-bold">' + sgtEscapar((m.nombre ? m.nombre + ' · ' : '') + m.hora) + '</p></div></div></div></div>'
                        : '<div class="d-flex chat-message"><div class="d-flex mb-2 flex-1"><div class="w-100 w-xxl-75"><div class="d-flex"><div class="avatar avatar-m me-3 flex-shrink-0"><div class="avatar-name rounded-circle"><span>' + sgtEscapar(iniciales) + '</span></div></div><div class="chat-message-content received me-2"><div class="mb-1 received-message-content border rounded-2 p-3"><p class="mb-0" style="white-space:pre-line">' + sgtEscapar(m.cuerpo) + '</p></div></div></div><p class="mb-0 fs--2 text-600 fw-semi-bold ms-7">' + sgtEscapar(m.hora) + '</p></div></div></div>';
                    lista.insertAdjacentHTML('beforeend', html);
                    lista.scrollTop = lista.scrollHeight;
                }

                @json($mensajes).forEach(agregar);

                // Mensajes nuevos de ESTA conversación en vivo (el aviso general del panel se encarga del resto).
                document.addEventListener('sgt:mensaje', function (e) {
                    if (e.detail.mensaje.conversacion_id === Number(caja.dataset.id)) {
                        agregar(e.detail.mensaje);
                        sgtFetch(caja.dataset.mensajes + '?despues=' + ultimoId).catch(function () {}); // la marca como leída
                    }
                });
                // Respaldo sin tiempo real.
                setInterval(function () {
                    if (window.sgtPanelEcho && window.sgtPanelEcho.connector.pusher.connection.state === 'connected') return;
                    sgtFetch(caja.dataset.mensajes + '?despues=' + ultimoId).then(function (d) { (d.mensajes || []).forEach(agregar); }).catch(function () {});
                }, 6000);

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
                        .then(function (d) { agregar(d.mensaje); })
                        .catch(function () { campo.value = texto; alert('No se pudo enviar la respuesta.'); });
                });
                campo.focus();
            })();
        </script>
    @endpush
@endif
