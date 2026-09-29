@php
    $claro = $s->url('imagen') ?? asset('assets/img/bg/bg-34.png');
    $oscuro = $s->imagen ? $s->url('imagen_oscura') : asset('assets/img/bg/bg-35.png');
    $imgPortada = function (string $clase) use ($claro, $oscuro, $s) {
        $alt = e($s->titulo);
        $html = '<img class="'.$clase.' rounded-2 hero-image-shadow'.($oscuro ? ' d-dark-none' : '').'" src="'.e($claro).'" alt="'.$alt.'" />';
        if ($oscuro) {
            $html .= '<img class="'.$clase.' rounded-2 hero-image-shadow d-light-none" src="'.e($oscuro).'" alt="'.$alt.'" />';
        }

        return new \Illuminate\Support\HtmlString($html);
    };
@endphp
<section class="pb-8 overflow-hidden {{ $fondo }}" id="s{{ $s->id }}">
    <div class="hero-header-container-alternate position-relative">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6 pt-8 pb-6 position-relative z-index-5 text-center text-lg-start">
                    @if ($s->etiqueta)
                        <p class="text-primary fw-bold text-uppercase fs--1 mb-3" style="letter-spacing: .08em">{{ $s->etiqueta }}</p>
                    @endif
                    <h1 class="fs-5 fs-md-6 fs-xl-7 fw-black mb-4">
                        @if ($s->opcion('resaltado'))<span class="text-gradient-info me-2">{{ $s->opcion('resaltado') }}</span>@endif{{ $s->titulo }}
                    </h1>
                    @if ($s->contenido)<div class="mb-5 pe-xl-10 sgt-contenido">{{ $s->contenidoHtml() }}</div>@endif
                    @if ($s->boton_texto && $s->boton_enlace)
                        <a class="btn btn-lg btn-primary rounded-pill me-3 mb-2" href="{{ $s->boton_enlace }}" role="button">{{ $s->boton_texto }}</a>
                    @endif
                    @if ($s->boton2_texto && $s->boton2_enlace)
                        <a class="btn btn-link me-2 fs-0 p-0 mb-2" href="{{ $s->boton2_enlace }}" role="button">{{ $s->boton2_texto }}<span class="fa-solid fa-angle-right ms-2 fs--1"></span></a>
                    @endif
                </div>
                <div class="col-12 col-lg-auto d-none d-lg-block">
                    <div class="hero-image-container position-absolute h-100 end-0 d-flex align-items-center">
                        <div class="position-relative">
                            <div class="position-absolute end-0 hero-image-container-overlay" style="transform: skewY(-8deg);"></div>
                            <img class="position-absolute end-0 hero-image-container-bg" src="{{ asset('assets/img/bg/bg-36.png') }}" alt="" />
                            {{ $imgPortada('w-100') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-small px-md-8 mb-8 d-lg-none">
            <div class="position-relative">
                <div class="position-absolute end-0 hero-image-container-overlay"></div>
                <img class="position-absolute top-50 hero-image-container-bg" src="{{ asset('assets/img/bg/bg-39.png') }}" alt="" />
                {{ $imgPortada('img-fluid ms-auto') }}
            </div>
        </div>
    </div>
</section>
