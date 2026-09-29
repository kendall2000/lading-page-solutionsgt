{{-- Mensajes de estado (Fortify y controladores) y errores de validación. --}}
@php
    $mensajesEstado = [
        'two-factor-authentication-enabled' => 'Escanea el código QR con tu aplicación de autenticación y confirma con el código de 6 dígitos.',
        'two-factor-authentication-confirmed' => 'Verificación en dos pasos activada.',
        'two-factor-authentication-disabled' => 'Verificación en dos pasos desactivada.',
        'recovery-codes-generated' => 'Se generaron códigos de recuperación nuevos. Guárdalos en un lugar seguro.',
        'password-updated' => 'Contraseña actualizada.',
    ];
    $estado = session('status');
@endphp

@if ($estado)
    <div class="alert alert-soft-success py-2 fs--1" role="alert">{{ $mensajesEstado[$estado] ?? $estado }}</div>
@endif

{{-- Todas las bolsas de errores (Fortify usa «updatePassword» al cambiar la contraseña). --}}
@php $listaErrores = collect($errors->getBags())->flatMap(fn ($bolsa) => $bolsa->all()); @endphp
@if ($listaErrores->isNotEmpty())
    <div class="alert alert-soft-danger py-2 fs--1" role="alert">
        @foreach ($listaErrores as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
