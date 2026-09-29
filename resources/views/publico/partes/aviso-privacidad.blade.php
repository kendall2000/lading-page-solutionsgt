{{-- Línea bajo los formularios (contacto y chat), solo si existe la página de privacidad. --}}
@if ($privacidad = \App\Support\Sitio::enlacePrivacidad())
    <p class="fs--2 text-600 text-center mt-2 mb-0">Al enviar aceptas nuestra <a href="{{ $privacidad }}" target="_blank">política de privacidad</a>.</p>
@endif
