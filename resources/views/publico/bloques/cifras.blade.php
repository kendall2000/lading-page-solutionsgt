{{-- Números animados (countUp de la plantilla). --}}
@if ($s->elementos->isNotEmpty())
    <section class="counter-container {{ $fondo }}" id="s{{ $s->id }}">
        <div class="position-absolute start-0 end-0 w-100 counter-overlay" style="transform: skewY(-8deg)"></div>
        <div class="bg-holder d-none d-lg-block" style="background-image:url({{ asset('assets/img/bg/bg-left-25.png') }});background-size:auto;background-position:left center;"></div>
        <div class="bg-holder d-none d-lg-block" style="background-image:url({{ asset('assets/img/bg/bg-right-25.png') }});background-size:auto;background-position:right center;"></div>
        <div class="container-small position-relative">
            @include('publico.bloques._titulo', ['conTexto' => false])
            <div class="row gx-0 gy-8 justify-content-center">
                @foreach ($s->elementos as $e)
                    @php $numero = (int) preg_replace('/\D/', '', (string) $e->valor); @endphp
                    <div class="col-sm-6 col-md-auto {{ $loop->last ? '' : 'me-md-5 pe-md-5 border-end-md border-dashed' }} text-center">
                        <h1 class="fs-5 fs-lg-7 fw-bolder text-info mb-3" data-countup='{"endValue":{{ $numero }},"duration":3,"suffix":{{ json_encode((string) $e->subtitulo) }}}'>{{ $numero }}{{ $e->subtitulo }}</h1>
                        <h4>{{ $e->titulo }}</h4>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
