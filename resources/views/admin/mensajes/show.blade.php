@extends('layouts.admin', ['titulo' => 'Mensaje de '.$mensaje->nombre])

@section('contenido')
    @php [$textoTipo, $colorTipo, $tituloTipo] = $mensaje->tipoInfo(); @endphp
    <a class="fs--1 fw-bold" href="{{ route('admin.mensajes.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Solicitudes y mensajes</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $tituloTipo }} de {{ $mensaje->nombre }} <span class="badge badge-phoenix badge-phoenix-{{ $colorTipo }} fs--1 align-middle">{{ $textoTipo }}</span>
        @if ($mensaje->cuenta_id)<a class="badge badge-phoenix badge-phoenix-success fs--1 align-middle" href="{{ route('admin.cuentas.edit', $mensaje->cuenta_id) }}"><span class="fa-solid fa-user-check me-1"></span>Tiene cuenta</a>@endif</h2>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <p class="text-700 fs--1 mb-3">Recibido el {{ $mensaje->created_at->format('d/m/Y \a \l\a\s H:i') }}</p>
                    <div class="fs-0 text-900" style="white-space: pre-line">{{ $mensaje->mensaje }}</div>
                    <hr />
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-sm" href="mailto:{{ $mensaje->correo }}?subject={{ rawurlencode('Re: tu mensaje a '.\App\Support\Sitio::nombre()) }}"><span class="fa-solid fa-reply me-2"></span>Responder por correo</a>
                        @if ($mensaje->telefono)
                            @php $tel = preg_replace('/\D/', '', $mensaje->telefono); $tel = strlen($tel) === 8 ? '502'.$tel : $tel; @endphp
                            <a class="btn btn-success btn-sm" href="https://wa.me/{{ $tel }}" target="_blank" rel="noopener"><span class="fa-brands fa-whatsapp me-2"></span>WhatsApp</a>
                            <a class="btn btn-phoenix-secondary btn-sm" href="tel:{{ $mensaje->telefono }}"><span class="fa-solid fa-phone me-2"></span>Llamar</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Acceso de prueba: se crea el usuario en el sistema correspondiente y aquí se envían los datos. --}}
            @if ($mensaje->tipo === 'prueba' || $mensaje->usuario_prueba)
                <div class="card mt-4 border border-success">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h4 class="mb-0"><span class="fa-solid fa-key text-success me-2"></span>Acceso de prueba</h4>
                            @if ($mensaje->credenciales_enviadas_en)
                                <span class="badge badge-phoenix badge-phoenix-success">Enviado el {{ $mensaje->credenciales_enviadas_en->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                        <p class="text-700 fs--1">
                            1. Crea un usuario de prueba en <strong>{{ $mensaje->sistema?->nombre ?? 'el sistema' }}</strong>.
                            2. Escribe aquí sus datos y envíalos: le llegan al visitante con la plantilla «Credenciales de prueba» (Correos).
                            @if ($mensaje->vence_el)
                                <br><strong>Vence el {{ $mensaje->vence_el->format('d/m/Y') }}</strong>{{ $mensaje->vence_el->isPast() ? ' (ya venció)' : ' (faltan '.now()->startOfDay()->diffInDays($mensaje->vence_el).' días)' }}: recuerda desactivar el usuario después.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('admin.mensajes.credenciales', $mensaje) }}" autocomplete="off">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label" for="url_acceso">Dirección para entrar</label>
                                    <input class="form-control" id="url_acceso" name="url_acceso" type="url" required maxlength="255" value="{{ old('url_acceso', $mensaje->url_acceso ?? $mensaje->sistema?->url_demo) }}" placeholder="https://demo.tusistema.com/login" />
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="usuario_prueba">Usuario</label>
                                    <input class="form-control" id="usuario_prueba" name="usuario_prueba" required maxlength="150" value="{{ old('usuario_prueba', $mensaje->usuario_prueba ?? $mensaje->correo) }}" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="clave_prueba">Contraseña</label>
                                    <input class="form-control" id="clave_prueba" name="clave_prueba" type="text" maxlength="150" autocomplete="off"
                                           placeholder="{{ $mensaje->clave_prueba ? 'guardada (vacío = igual)' : '' }}" {{ $mensaje->clave_prueba ? '' : 'required' }} />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="dias">Días</label>
                                    <input class="form-control" id="dias" name="dias" type="number" min="1" max="365" required value="{{ old('dias', $mensaje->sistema?->dias_prueba ?? 15) }}" />
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button class="btn btn-success" type="submit" name="enviar" value="1"><span class="fa-solid fa-paper-plane me-2"></span>Guardar y enviar por correo</button>
                                <button class="btn btn-phoenix-secondary" type="submit" name="enviar" value="0">Solo guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Datos</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700 fw-semi-bold">Nombre</dt><dd class="col-7">{{ $mensaje->nombre }}</dd>
                        <dt class="col-5 text-700 fw-semi-bold">Empresa</dt><dd class="col-7">{{ $mensaje->empresa ?: '—' }}</dd>
                        <dt class="col-5 text-700 fw-semi-bold">Correo</dt><dd class="col-7 text-break"><a href="mailto:{{ $mensaje->correo }}">{{ $mensaje->correo }}</a></dd>
                        <dt class="col-5 text-700 fw-semi-bold">Teléfono</dt><dd class="col-7">{{ $mensaje->telefono ?: '—' }}</dd>
                        <dt class="col-5 text-700 fw-semi-bold">Sistema</dt><dd class="col-7">{{ $mensaje->sistema?->nombre ?? '—' }}</dd>
                        <dt class="col-5 text-700 fw-semi-bold">IP</dt><dd class="col-7 mb-0">{{ $mensaje->ip ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Seguimiento</h5>
                    <form method="POST" action="{{ route('admin.mensajes.update', $mensaje) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                @foreach (\App\Models\MensajeContacto::ESTADOS as $clave => [$texto])
                                    <option value="{{ $clave }}" @selected($mensaje->estado === $clave)>{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="notas">Notas internas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="4" maxlength="3000">{{ old('notas', $mensaje->notas) }}</textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Guardar</button>
                    </form>
                    <form class="mt-3" method="POST" action="{{ route('admin.mensajes.destroy', $mensaje) }}" onsubmit="return confirm('¿Eliminar este mensaje?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar mensaje</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
