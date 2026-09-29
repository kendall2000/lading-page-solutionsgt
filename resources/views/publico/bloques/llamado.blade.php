{{-- Llamado a la acción (recuadro destacado de la plantilla). --}}
<section class="bg-soft-primary dark__bg-1000 pb-10 overflow-hidden" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        <div class="position-absolute w-100 h-100 start-0 end-0" style="bottom: -350px; transform: skewY(-8deg); background: linear-gradient(102.27deg, #38ABFF 4.69%, var(--phoenix-primary) 106.27%)"></div>
        <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/bg-left-24.png') }});background-size:auto;background-position:left center;"></div>
        <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/bg-right-24.png') }});background-size:auto;background-position:right center;"></div>
        <div class="row justify-content-center pt-8">
            <div class="col-12 text-center">
                <div class="card py-md-9 px-md-13 border-0 z-index-1 shadow-lg">
                    <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/bg-38.png') }});background-position:center;background-size:100%;"></div>
                    <div class="card-body position-relative">
                        @if ($s->imagen)
                            <img class="img-fluid mb-5" src="{{ $s->url('imagen') }}" style="max-height: 180px" alt="" loading="lazy" />
                        @else
                            <img class="img-fluid mb-5 d-dark-none" src="{{ asset('assets/img/spot-illustrations/37.png') }}" width="220" alt="" loading="lazy" />
                            <img class="img-fluid mb-5 d-light-none" src="{{ asset('assets/img/spot-illustrations/37_2.png') }}" width="220" alt="" loading="lazy" />
                        @endif
                        @if ($s->etiqueta)<p class="fw-bold">{{ $s->etiqueta }}</p>@endif
                        @if ($s->titulo)<h1 class="fs-2 fs-sm-4 fs-lg-6 fw-bolder lh-sm mb-3">{{ $s->titulo }}</h1>@endif
                        @if ($s->contenido)<div class="sgt-contenido text-700 mb-5 mx-auto" style="max-width: 40rem">{{ $s->contenidoHtml() }}</div>@endif
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            @if ($s->opcion('whatsapp') && ($wa = $cfg->enlaceWhatsapp('Hola, quiero información sobre sus sistemas.')))
                                <a class="btn btn-lg btn-success" href="{{ $wa }}" target="_blank" rel="noopener"><span class="fa-brands fa-whatsapp me-2"></span>WhatsApp</a>
                            @endif
                            @if ($s->boton_texto && $s->boton_enlace)
                                <a class="btn btn-lg btn-primary" href="{{ $s->boton_enlace }}">{{ $s->boton_texto }}</a>
                            @endif
                            @if ($s->boton2_texto && $s->boton2_enlace)
                                <a class="btn btn-lg btn-phoenix-primary" href="{{ $s->boton2_enlace }}">{{ $s->boton2_texto }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
