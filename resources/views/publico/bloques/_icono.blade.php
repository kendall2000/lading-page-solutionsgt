{{-- Imagen del elemento o, si no tiene, su ícono de Font Awesome. Parámetros: e (Elemento), clase. --}}
@if ($e->imagen)
    <img src="{{ $e->url() }}" alt="{{ $e->titulo }}" style="max-height: {{ $alto ?? 56 }}px; max-width: {{ ($alto ?? 56) * 2 }}px; object-fit: contain;" loading="lazy" />
@elseif ($e->icono)
    <span class="sgt-icono {{ $clase ?? '' }}"><span class="{{ $e->icono }}"></span></span>
@endif
