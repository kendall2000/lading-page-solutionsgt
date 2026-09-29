@extends('layouts.admin', ['titulo' => 'Mensaje de '.$mensaje->nombre])

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.mensajes.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Mensajes</a>
    <h2 class="mt-2 mb-4 text-1100">Mensaje de {{ $mensaje->nombre }}</h2>

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
