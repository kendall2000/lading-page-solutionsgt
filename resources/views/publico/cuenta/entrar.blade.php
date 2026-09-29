@extends('publico.cuenta.marco', ['tituloMarco' => 'Entrar a mi cuenta', 'subtituloMarco' => 'Tus sistemas, solicitudes, conversaciones y manuales.', 'iconoMarco' => 'fa-right-to-bracket'])

@section('marco')
    <ul class="nav nav-underline justify-content-center mb-4" role="tablist">
        <li class="nav-item"><a class="nav-link {{ old('modo') === 'enlace' ? '' : 'active' }}" data-bs-toggle="tab" href="#conClave" role="tab">Con contraseña</a></li>
        <li class="nav-item"><a class="nav-link {{ old('modo') === 'enlace' ? 'active' : '' }}" data-bs-toggle="tab" href="#conEnlace" role="tab">Con enlace por correo</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade {{ old('modo') === 'enlace' ? '' : 'show active' }}" id="conClave" role="tabpanel">
            <form method="POST" action="{{ route('cuenta.login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="correo">Correo</label>
                    <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" required autocomplete="email" maxlength="150" autofocus />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password" />
                </div>
                <div class="d-flex flex-between-center mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" id="recordar" name="recordar" type="checkbox" value="1" />
                        <label class="form-check-label mb-0" for="recordar">Recordarme</label>
                    </div>
                    <a class="fs--1 fw-semi-bold" href="#conEnlace" data-bs-toggle="tab">¿Olvidaste tu contraseña?</a>
                </div>
                <button class="btn btn-primary w-100" type="submit">Entrar</button>
            </form>
        </div>
        <div class="tab-pane fade {{ old('modo') === 'enlace' ? 'show active' : '' }}" id="conEnlace" role="tabpanel">
            <p class="fs--1 text-700">Escribe tu correo y te enviamos un enlace para entrar sin contraseña. Sirve una sola vez y vence en {{ \App\Services\Cuentas::MINUTOS_ENLACE }} minutos. También sirve si olvidaste tu contraseña.</p>
            <form method="POST" action="{{ route('cuenta.enlace') }}">
                @csrf
                <input type="hidden" name="modo" value="enlace" />
                <div class="mb-3">
                    <label class="form-label" for="correo_enlace">Correo</label>
                    <input class="form-control" id="correo_enlace" name="correo" type="email" value="{{ old('correo') }}" required autocomplete="email" maxlength="150" />
                </div>
                <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>Enviarme el enlace</button>
            </form>
        </div>
    </div>
@endsection

@section('pie')
    ¿Todavía no tienes cuenta? <a class="fw-bold" href="{{ route('cuenta.registro') }}">Crea una</a>
@endsection
