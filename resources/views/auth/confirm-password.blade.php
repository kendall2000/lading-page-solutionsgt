@extends('layouts.auth', ['titulo' => 'Confirmar contraseña'])

@section('contenido')
    <div class="text-center mb-6">
        <h4 class="text-1000">Confirma tu contraseña</h4>
        <p class="text-700 mb-0">Es una zona protegida: vuelve a escribir tu contraseña para continuar.</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <div class="mb-3 text-start">
            <label class="form-label" for="password">Contraseña</label>
            <input class="form-control" id="password" name="password" type="password" required autofocus autocomplete="current-password" />
        </div>
        <button class="btn btn-primary w-100" type="submit">Confirmar</button>
    </form>
@endsection
