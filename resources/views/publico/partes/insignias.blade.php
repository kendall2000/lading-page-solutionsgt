{{-- Insignias de un sistema: modalidad, precio, prueba gratis y destacado. --}}
@php [$textoMod, $colorMod] = $sis->modalidadInfo(); @endphp
<div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
    <span class="badge badge-phoenix badge-phoenix-{{ $colorMod }}">{{ $textoMod }}</span>
    @if ($sis->precio)<span class="badge badge-phoenix badge-phoenix-secondary">{{ $sis->precio }}</span>@endif
    @if ($sis->acepta_prueba)<span class="badge badge-phoenix badge-phoenix-success"><span class="fa-solid fa-flask me-1"></span>Prueba {{ $sis->dias_prueba }} días</span>@endif
    @if ($sis->destacado)<span class="badge badge-phoenix badge-phoenix-primary"><span class="fa-solid fa-star me-1"></span>Destacado</span>@endif
</div>
