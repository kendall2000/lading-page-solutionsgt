@extends('publico.cuenta.marco', ['tituloMarco' => 'Confirma tu correo', 'iconoMarco' => 'fa-envelope-circle-check'])

@section('marco')
    <p class="text-700 text-center">Te enviamos un correo a <strong>{{ $cuenta->correo }}</strong> con un botón para confirmar tu cuenta. Cuando lo confirmes podrás ver tus sistemas, solicitudes y conversaciones.</p>
    <p class="text-600 fs--1 text-center mb-4">¿No llegó? Revisa la carpeta de spam o pide que te lo enviemos otra vez.</p>
    <form class="mb-2" method="POST" action="{{ route('cuenta.reenviar') }}">
        @csrf
        <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>Enviar otra vez</button>
    </form>
    <form method="POST" action="{{ route('cuenta.salir') }}">
        @csrf
        <button class="btn btn-link w-100" type="submit">Salir</button>
    </form>
@endsection
