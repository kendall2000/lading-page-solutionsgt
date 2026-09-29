@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Conversación'])

{{-- Historial y respuesta desde el portal (sirve también para chats empezados en otro equipo). Se actualiza sola cada pocos segundos. --}}
@section('portal')
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0">Conversación del {{ $conversacion->created_at->translatedFormat('j \d\e F Y') }}</h5>
                <p class="fs--1 text-700 mb-0">Empezó en {{ $conversacion->pagina ?: 'el sitio' }}</p>
            </div>
            <span class="badge badge-phoenix badge-phoenix-{{ $conversacion->estado === 'cerrada' ? 'secondary' : 'success' }}" id="portalEstado">{{ $conversacion->estado === 'cerrada' ? 'Cerrada' : 'Abierta' }}</span>
        </div>
        <div class="card-body">
            <div class="sgt-hilo scrollbar" id="portalMensajes" style="max-height: 55vh; overflow-y: auto;"></div>
        </div>
        <div class="card-footer">
            <div class="text-center fs--1 text-700 {{ $conversacion->estado === 'cerrada' ? '' : 'd-none' }}" id="portalCerrada">
                Esta conversación se cerró. <a class="fw-bold" href="{{ route('cuenta.conversaciones') }}#nueva">Empieza una nueva</a> si necesitas algo más.
            </div>
            <form class="d-flex gap-2 {{ $conversacion->estado === 'cerrada' ? 'd-none' : '' }}" id="portalForm">
                <textarea class="form-control" name="cuerpo" rows="2" maxlength="2000" required placeholder="Escribe tu mensaje…" aria-label="Mensaje"></textarea>
                <button class="btn btn-primary" type="submit" aria-label="Enviar"><span class="fa-solid fa-paper-plane"></span></button>
            </form>
            <div class="text-danger fs--1 mt-2 d-none" id="portalError"></div>
        </div>
    </div>
@endsection

@push('estilos')
    <style>
        .sgt-hilo { display: flex; flex-direction: column; gap: .5rem; }
        .sgt-hilo .burbuja { max-width: 80%; padding: .55rem .85rem; border-radius: 1rem; font-size: .875rem; line-height: 1.35; white-space: pre-line; word-wrap: break-word; }
        .sgt-hilo .burbuja small { display: block; font-size: .7rem; opacity: .75; margin-top: .15rem; }
        .sgt-hilo .yo { align-self: flex-end; background: var(--phoenix-primary); color: #fff; border-bottom-right-radius: .25rem; }
        .sgt-hilo .yo small { text-align: right; }
        .sgt-hilo .otro { align-self: flex-start; background: var(--phoenix-gray-100); color: var(--phoenix-gray-1000); border-bottom-left-radius: .25rem; }
        .sgt-hilo .sistema { align-self: center; font-size: .75rem; color: var(--phoenix-gray-700); background: var(--phoenix-gray-100); border-radius: 1rem; padding: .25rem .75rem; }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            var urlMensajes = @json(route('cuenta.conversacion.mensajes', $conversacion));
            var urlResponder = @json(route('cuenta.conversacion.responder', $conversacion));
            var mensajes = @json($mensajes);
            var leidoHasta = 0;
            var ultimo = 0;
            var lista = document.getElementById('portalMensajes');
            var form = document.getElementById('portalForm');
            var error = document.getElementById('portalError');
            var token = document.querySelector('meta[name="csrf-token"]').content;

            function pintar(m) {
                var div = document.createElement('div');
                if (m.autor === 'sistema') {
                    div.className = 'sistema';
                    div.textContent = m.cuerpo;
                } else {
                    var mio = m.autor === 'visitante';
                    div.className = 'burbuja ' + (mio ? 'yo' : 'otro');
                    div.dataset.id = m.id;
                    div.textContent = m.cuerpo;
                    var pie = document.createElement('small');
                    pie.textContent = (mio ? '' : (m.nombre || 'Soporte') + ' · ') + m.hora;
                    if (mio) {
                        var marca = document.createElement('span');
                        marca.className = 'marca ms-1';
                        pie.appendChild(marca);
                    }
                    div.appendChild(pie);
                }
                lista.appendChild(div);
                ultimo = Math.max(ultimo, m.id);
            }
            function marcas() {
                lista.querySelectorAll('.yo').forEach(function (b) {
                    var marca = b.querySelector('.marca');
                    if (marca) marca.textContent = Number(b.dataset.id) <= leidoHasta ? '✓✓ Visto' : '✓';
                });
            }
            function agregar(nuevos) {
                var abajo = lista.scrollHeight - lista.scrollTop - lista.clientHeight < 60;
                nuevos.forEach(function (m) { if (m.id > ultimo) pintar(m); });
                marcas();
                if (abajo || nuevos.length) lista.scrollTop = lista.scrollHeight;
            }
            function cerrada() {
                form.classList.add('d-none');
                document.getElementById('portalCerrada').classList.remove('d-none');
                var estado = document.getElementById('portalEstado');
                estado.textContent = 'Cerrada';
                estado.className = 'badge badge-phoenix badge-phoenix-secondary';
            }
            function consultar() {
                var leer = document.visibilityState === 'visible' ? '&leer=1' : '';
                fetch(urlMensajes + '?despues=' + ultimo + leer, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) {
                        if (!d) return;
                        leidoHasta = d.leidoHasta;
                        agregar(d.mensajes);
                        if (d.estado === 'cerrada') cerrada();
                    }).catch(function () {});
            }

            agregar(mensajes);
            lista.scrollTop = lista.scrollHeight;
            consultar();
            setInterval(consultar, 5000);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                error.classList.add('d-none');
                var campo = form.querySelector('textarea');
                var texto = campo.value.trim();
                if (!texto) return;
                var boton = form.querySelector('button');
                boton.disabled = true;
                fetch(urlResponder, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ cuerpo: texto })
                }).then(function (r) {
                    return r.json().then(function (d) { return { ok: r.ok, status: r.status, d: d }; });
                }).then(function (res) {
                    if (res.ok) {
                        campo.value = '';
                        agregar([res.d.mensaje]);
                        lista.scrollTop = lista.scrollHeight;
                    } else if (res.status === 409) {
                        cerrada();
                    } else {
                        error.textContent = res.d.errors ? Object.values(res.d.errors).flat().join(' ') : (res.d.message || 'No se pudo enviar.');
                        error.classList.remove('d-none');
                    }
                }).catch(function () {
                    error.textContent = 'No se pudo enviar. Revisa tu conexión.';
                    error.classList.remove('d-none');
                }).finally(function () { boton.disabled = false; });
            });
            form.querySelector('textarea').addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
            });
        })();
    </script>
@endpush
