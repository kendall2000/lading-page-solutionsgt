{{-- Logo del sitio: imagen clara/oscura o, sin imagen, el ícono y el nombre. --}}
@php $cfgLogo = \App\Support\Sitio::config(); $alto = $alto ?? 32; @endphp
@if ($cfgLogo->logo)
    <img class="{{ $cfgLogo->logo_oscuro ? 'd-dark-none' : '' }}" src="{{ $cfgLogo->url('logo') }}" alt="{{ \App\Support\Sitio::nombre() }}" style="max-height: {{ $alto }}px; max-width: 220px;" />
    @if ($cfgLogo->logo_oscuro)
        <img class="d-light-none" src="{{ $cfgLogo->url('logo_oscuro') }}" alt="{{ \App\Support\Sitio::nombre() }}" style="max-height: {{ $alto }}px; max-width: 220px;" />
    @endif
@else
    <span class="fa-solid fa-code text-primary" style="font-size: {{ $alto * 0.75 }}px"></span>
    <p class="logo-text ms-2 mb-0 {{ $claseTexto ?? '' }}">{{ \App\Support\Sitio::nombre() }}</p>
@endif
