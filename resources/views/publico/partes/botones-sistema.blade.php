{{-- Botones de un sistema en el catálogo: detalle, usar gratis, pedir demostración o prueba. --}}
@php $tam = ($chico ?? false) ? 'btn-sm' : ''; @endphp
<div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
    <a class="btn btn-primary {{ $tam }}" href="{{ route('sistema', $sis->slug) }}">Ver detalle</a>
    @if ($sis->modalidad === 'gratis' && $sis->url_demo)
        <a class="btn btn-success {{ $tam }}" href="{{ $sis->url_demo }}" target="_blank" rel="noopener"><span class="fa-solid fa-arrow-up-right-from-square me-1"></span>Usar gratis</a>
    @endif
    @if ($sis->ofrecePrueba())
        <a class="btn btn-phoenix-success {{ $tam }}" href="{{ route('sistema', ['sistema' => $sis->slug, 'tipo' => 'prueba']) }}#solicitar">Probar {{ $sis->dias_prueba }} días</a>
    @endif
    @if ($sis->proximamente)
        <a class="btn btn-phoenix-secondary {{ $tam }}" href="{{ route('sistema', $sis->slug) }}#solicitar"><span class="fa-solid fa-bell me-1"></span>Avísame</a>
    @endif
    @if ($sis->ofreceDemo())
        <a class="btn btn-phoenix-primary {{ $tam }}" href="{{ route('sistema', ['sistema' => $sis->slug, 'tipo' => 'demo']) }}#solicitar">Pedir demo</a>
    @endif
</div>
