@extends('publico.cuenta.marco', ['tituloMarco' => 'Activa tu cuenta', 'iconoMarco' => 'fa-envelope-open-text'])

@section('marco')
    @if ($cuenta)
        <p class="text-700 text-center mb-4">Hola, <strong>{{ $cuenta->nombre }}</strong>. Crea tu contraseña para entrar con <strong>{{ $cuenta->correo }}</strong>.</p>
        <form method="POST" action="{{ route('cuenta.invitacion.aceptar', $token) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" autofocus />
                <div class="form-text">Mínimo 10 caracteres, con letras y números.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">Repítela</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </div>
            <button class="btn btn-primary w-100" type="submit">Activar mi cuenta</button>
        </form>
    @else
        <p class="text-700 text-center mb-4">Esta invitación ya se usó o venció. Si ya activaste tu cuenta, entra normalmente; si no, entra con un enlace por correo o pídenos otra invitación.</p>
        <a class="btn btn-phoenix-primary w-100" href="{{ route('cuenta.entrar') }}">Ir a entrar</a>
    @endif
@endsection
