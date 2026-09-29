@extends('layouts.auth', ['titulo' => 'Nueva contraseña'])

@section('contenido')
    <div class="text-center mb-6">
        <h4 class="text-1000">Crear nueva contraseña</h4>
        <p class="text-700 mb-0">Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3 text-start">
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="username" />
        </div>
        <div class="mb-3 text-start">
            <label class="form-label" for="password">Nueva contraseña</label>
            <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" />
        </div>
        <div class="mb-3 text-start">
            <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
        </div>
        <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
    </form>
@endsection
