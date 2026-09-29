@extends('layouts.auth', ['titulo' => 'Verificación en dos pasos'])

@section('contenido')
    <div class="text-center mb-6">
        <h4 class="text-1000">Verificación en dos pasos</h4>
        <p class="text-700 mb-0" id="ayuda-codigo">Escribe el código de 6 dígitos de tu aplicación de autenticación.</p>
        <p class="text-700 mb-0 d-none" id="ayuda-recuperacion">Escribe uno de tus códigos de recuperación.</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('two-factor.login.store') }}">
        @csrf
        <div class="mb-3" id="campo-codigo">
            <label class="form-label" for="code">Código</label>
            <input class="form-control text-center fs-2" id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" autofocus />
        </div>
        <div class="mb-3 d-none" id="campo-recuperacion">
            <label class="form-label" for="recovery_code">Código de recuperación</label>
            <input class="form-control" id="recovery_code" name="recovery_code" type="text" autocomplete="off" />
        </div>
        <button class="btn btn-primary w-100 mb-3" type="submit">Verificar</button>
    </form>
    <div class="text-center">
        <a class="fs--1 fw-bold" href="#" id="alternar">Usar un código de recuperación</a>
    </div>

    <script>
        document.getElementById('alternar').addEventListener('click', function (e) {
            e.preventDefault();
            const recuperacion = document.getElementById('campo-recuperacion').classList.toggle('d-none') === false;
            document.getElementById('campo-codigo').classList.toggle('d-none', recuperacion);
            document.getElementById('ayuda-codigo').classList.toggle('d-none', recuperacion);
            document.getElementById('ayuda-recuperacion').classList.toggle('d-none', !recuperacion);
            document.getElementById(recuperacion ? 'code' : 'recovery_code').value = '';
            document.getElementById(recuperacion ? 'recovery_code' : 'code').focus();
            this.textContent = recuperacion ? 'Usar el código de la aplicación' : 'Usar un código de recuperación';
        });
    </script>
@endsection
