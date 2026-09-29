@extends('layouts.auth', ['titulo' => 'Recuperar contraseña'])

@section('contenido')
    <div class="text-center mb-6">
        <h4 class="text-1000">¿Olvidaste tu contraseña?</h4>
        <p class="text-700 mb-0">Escribe tu correo y te enviaremos un enlace para crear una nueva.</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3 text-start">
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tu@correo.com" required autofocus maxlength="150" />
        </div>
        <button class="btn btn-primary w-100 mb-3" type="submit">Enviar enlace</button>
    </form>
    <div class="text-center"><a class="fs--1 fw-bold" href="{{ route('login') }}">Volver a iniciar sesión</a></div>
@endsection
