{{-- Título y párrafos con imagen opcional al lado (quiénes somos, misión, visión…). --}}
@php
    $izquierda = $s->opcion('lado') === 'izquierda';
    // Sin imagen, el texto va centrado salvo que se pida a la izquierda (textos largos: políticas, términos).
    $alineado = $s->imagen ? 'text-center text-lg-start' : ($s->opcion('alineacion') === 'izquierda' ? 'text-start' : 'text-center');
@endphp
<section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        <div class="row align-items-center g-6 {{ $s->imagen ? '' : 'justify-content-center' }}">
            <div class="{{ $s->imagen ? 'col-lg-6' : 'col-lg-9' }} {{ $izquierda ? 'order-lg-1' : '' }} {{ $alineado }}">
                @if ($s->etiqueta)<h5 class="text-info mb-3">{{ $s->etiqueta }}</h5>@endif
                @if ($s->titulo)<h2 class="mb-4 lh-base">{{ $s->titulo }}</h2>@endif
                @if ($s->contenido)<div class="sgt-contenido text-800 mb-4">{{ $s->contenidoHtml() }}</div>@endif
                @if ($s->boton_texto && $s->boton_enlace)
                    <a class="btn btn-primary rounded-pill" href="{{ $s->boton_enlace }}">{{ $s->boton_texto }}<span class="fa-solid fa-angle-right ms-2"></span></a>
                @endif
            </div>
            @if ($s->imagen)
                <div class="col-lg-6 text-center {{ $izquierda ? 'order-lg-0' : '' }}">
                    <img class="img-fluid sgt-captura" src="{{ $s->url('imagen') }}" alt="{{ $s->titulo }}" loading="lazy" style="max-height: 480px" />
                </div>
            @endif
        </div>
    </div>
</section>
