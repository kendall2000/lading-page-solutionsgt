{{-- Testimonios de clientes (carrusel de la plantilla). --}}
@if ($datos['testimonios']->isNotEmpty())
    <section class="pt-10 pb-14 overflow-x-hidden {{ $fondo }}" id="s{{ $s->id }}">
        <div class="container-small px-lg-7 px-xxl-3">
            @include('publico.bloques._titulo', ['conTexto' => false])
            <div class="carousel testimonial-carousel slide position-relative dark__bg-1100" id="testimonios{{ $s->id }}" data-bs-ride="carousel">
                <div class="bg-holder d-none d-md-block" style="background-image:url({{ asset('assets/img/bg/39.png') }});background-size:186px;background-position:top 20px right 20px;"></div>
                <img class="position-absolute d-none d-lg-block" src="{{ asset('assets/img/bg/bg-left-22.png') }}" width="150" alt="" style="top: -100px; left: -70px" />
                <img class="position-absolute d-none d-lg-block" src="{{ asset('assets/img/bg/bg-right-22.png') }}" width="150" alt="" style="bottom: -80px; right: -80px" />
                <div class="carousel-inner">
                    @foreach ($datos['testimonios'] as $t)
                        <div class="carousel-item text-center py-8 px-5 px-xl-15 {{ $loop->first ? 'active' : '' }}">
                            @for ($e = 1; $e <= 5; $e++)
                                <span class="{{ $e <= $t->calificacion ? 'fa fa-star text-warning' : 'fa-regular fa-star text-warning-300' }}"></span>
                            @endfor
                            <h3 class="fw-semi-bold fst-italic mt-3 mb-8 w-xl-70 mx-auto lh-base">“{{ $t->testimonio }}”</h3>
                            <div class="d-flex align-items-center justify-content-center gap-3 mx-auto">
                                <div class="avatar avatar-3xl">
                                    @if ($t->foto)
                                        <img class="rounded-circle border border-2 border-primary" src="{{ $t->url('foto') }}" alt="{{ $t->contacto }}" />
                                    @else
                                        <div class="avatar-name rounded-circle border border-2 border-primary"><span>{{ $t->iniciales() }}</span></div>
                                    @endif
                                </div>
                                <div class="text-start">
                                    <h5>{{ $t->contacto ?: $t->empresa }}</h5>
                                    <p class="mb-0">{{ collect([$t->cargo, $t->contacto ? $t->empresa : null])->filter()->implode(' · ') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($datos['testimonios']->count() > 1)
                    <div class="carousel-indicators">
                        @foreach ($datos['testimonios'] as $t)
                            <button class="{{ $loop->first ? 'active' : '' }}" type="button" data-bs-target="#testimonios{{ $s->id }}" data-bs-slide-to="{{ $loop->index }}" aria-label="Testimonio {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
