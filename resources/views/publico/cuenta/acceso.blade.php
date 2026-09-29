@extends('publico.cuenta.marco', ['tituloMarco' => 'Entrar con tu enlace', 'iconoMarco' => 'fa-link'])

{{-- Página intermedia con botón: los antivirus del correo abren los enlaces y gastarían el de un solo uso. --}}
@section('marco')
    @if ($valido)
        <p class="text-700 text-center mb-4">Toca el botón para entrar a tu cuenta.</p>
        <form method="POST" action="{{ route('cuenta.acceso.usar', $token) }}">
            @csrf
            <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-right-to-bracket me-2"></span>Entrar a mi cuenta</button>
        </form>
    @else
        <p class="text-700 text-center mb-4">Este enlace ya se usó o venció. Pide uno nuevo: tarda unos segundos.</p>
        <a class="btn btn-phoenix-primary w-100" href="{{ route('cuenta.entrar') }}">Pedir otro enlace</a>
    @endif
@endsection
