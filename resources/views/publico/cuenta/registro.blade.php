@extends('publico.cuenta.marco', ['tituloMarco' => 'Crear mi cuenta', 'subtituloMarco' => 'Sigue tus solicitudes, pruebas, sistemas y conversaciones en un solo lugar.', 'iconoMarco' => 'fa-user-plus'])

@section('marco')
    @if (session('registrado'))
        <div class="text-center">
            <span class="fa-solid fa-envelope-circle-check text-success fs-4 mb-3 d-block"></span>
            <h5 class="mb-2">Revisa tu correo</h5>
            <p class="text-700 fs--1 mb-4">Te enviamos un correo para confirmar tu cuenta (o, si ya tenías una, un enlace para entrar). Si no llega en unos minutos, revisa la carpeta de spam.</p>
            <a class="btn btn-phoenix-primary" href="{{ route('cuenta.entrar') }}">Ir a entrar</a>
        </div>
    @else
        <form method="POST" action="{{ route('cuenta.registrar') }}">
            @csrf
            {{-- Campo trampa y marca de llegada (antispam). --}}
            <div style="position:absolute; left:-10000px;" aria-hidden="true"><input type="text" name="empresa_web" tabindex="-1" autocomplete="off" /></div>
            <input type="hidden" name="llegada" value="{{ \App\Support\Antispam::marca() }}" />
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre *</label>
                <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="120" autocomplete="name" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="correo">Correo *</label>
                <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" required maxlength="150" autocomplete="email" />
                <div class="form-text">Si ya nos escribiste o chateaste con este correo, lo verás en tu cuenta.</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label" for="empresa">Empresa o negocio</label>
                    <input class="form-control" id="empresa" name="empresa" value="{{ old('empresa') }}" maxlength="150" autocomplete="organization" />
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="telefono">Teléfono / WhatsApp</label>
                    <input class="form-control" id="telefono" name="telefono" type="tel" value="{{ old('telefono') }}" maxlength="30" autocomplete="tel" />
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <label class="form-label" for="password">Contraseña *</label>
                    <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" />
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="password_confirmation">Repítela *</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                </div>
                <div class="col-12"><div class="form-text mt-0">Mínimo 10 caracteres, con letras y números.</div></div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Crear mi cuenta</button>
            @include('publico.partes.aviso-privacidad')
        </form>
    @endif
@endsection

@section('pie')
    ¿Ya tienes cuenta? <a class="fw-bold" href="{{ route('cuenta.entrar') }}">Entra aquí</a>
@endsection
