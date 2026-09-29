@extends('layouts.auth', ['titulo' => 'Iniciar sesión'])

@section('contenido')
    <div class="text-center mb-6">
        <h3 class="text-1000">Panel de administración</h3>
        <p class="text-700">Ingresa tus credenciales para continuar</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('login') }}" autocomplete="on">
        @csrf
        <div class="mb-3 text-start">
            <label class="form-label" for="email">Correo electrónico</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input @error('email') is-invalid @enderror" id="email" name="email" type="email"
                       value="{{ old('email') }}" placeholder="tu@correo.com" required autofocus autocomplete="username" maxlength="150" />
                <span class="fas fa-user text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <div class="mb-3 text-start">
            <label class="form-label" for="password">Contraseña</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input" id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password" />
                <span class="fas fa-key text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <div class="row flex-between-center mb-6">
            <div class="col-auto">
                <div class="form-check mb-0">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" @checked(old('remember')) />
                    <label class="form-check-label mb-0" for="remember">Recordar sesión</label>
                </div>
            </div>
            <div class="col-auto">
                <a class="fs--1 fw-semi-bold" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
        <button class="btn btn-primary w-100 mb-3" type="submit">Iniciar sesión</button>
    </form>
@endsection
