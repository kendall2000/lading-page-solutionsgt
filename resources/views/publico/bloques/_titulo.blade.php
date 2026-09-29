{{-- Etiqueta, título y texto de introducción de una sección, centrados. --}}
@if ($s->etiqueta || $s->titulo || ($conTexto ?? true) && $s->contenido)
    <div class="text-center mb-7 mx-auto" style="max-width: 48rem">
        @if ($s->etiqueta)<h5 class="text-info mb-3">{{ $s->etiqueta }}</h5>@endif
        @if ($s->titulo)<h2 class="mb-3 lh-base">{{ $s->titulo }}</h2>@endif
        @if (($conTexto ?? true) && $s->contenido)
            <div class="sgt-contenido text-700">{{ $s->contenidoHtml() }}</div>
        @endif
    </div>
@endif
