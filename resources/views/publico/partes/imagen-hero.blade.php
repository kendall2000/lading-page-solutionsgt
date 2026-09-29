{{-- Imagen de portada: la subida en «Datos del sitio» o, si no hay, la de la plantilla. --}}
@php
    $claro = $cfg->url('imagen_hero') ?? asset('assets/img/bg/bg-34.png');
    $oscuro = $cfg->imagen_hero ? $cfg->url('imagen_hero_oscura') : asset('assets/img/bg/bg-35.png');
@endphp
<img class="{{ $clase }} rounded-2 hero-image-shadow {{ $oscuro ? 'd-dark-none' : '' }}" src="{{ $claro }}" alt="{{ \App\Support\Sitio::nombre() }}" />
@if ($oscuro)
    <img class="{{ $clase }} rounded-2 hero-image-shadow d-light-none" src="{{ $oscuro }}" alt="{{ \App\Support\Sitio::nombre() }}" />
@endif
