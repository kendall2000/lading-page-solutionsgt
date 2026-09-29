{{-- Planes y precios (panel → Planes y precios), con las tarjetas de precios de la plantilla. --}}
@if ($datos['servicios']->isNotEmpty())
    <section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
        <div class="container-small px-lg-7 px-xxl-3">
            @include('publico.bloques._titulo')
            <div class="row g-3 justify-content-center">
                @foreach ($datos['servicios'] as $servicio)
                    <div class="col-md-6 col-lg-4">
                        <div class="pricing-card h-100">
                            <div class="card bg-transparent h-100 {{ $servicio->destacado ? 'border border-2 border-info rounded-4' : 'border-0' }}">
                                <div class="card-body p-6 p-lg-7 d-flex flex-column">
                                    <h3 class="mb-2">{{ $servicio->nombre }}</h3>
                                    @if ($servicio->descripcion)<p class="text-700 fs--1 mb-4">{{ $servicio->descripcion }}</p>@endif
                                    @if ($servicio->precio)
                                        <h1 class="fs-4 d-flex align-items-center gap-1 mb-4">{{ $servicio->precio }}@if ($servicio->periodo)<span class="fs-0 fw-normal">{{ $servicio->periodo }}</span>@endif</h1>
                                    @endif
                                    <a class="btn btn-lg w-100 mb-6 {{ $servicio->destacado ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ \App\Support\Sitio::enlaceContacto() }}">Me interesa</a>
                                    @if ($servicio->listaIncluye())
                                        <h5 class="mb-4">Incluye</h5>
                                        <ul class="fa-ul ps-4 m-0 pricing">
                                            @foreach ($servicio->listaIncluye() as $item)
                                                <li class="d-flex align-items-center mb-3"><span class="fa-li"><span class="fas fa-check text-primary"></span></span><p class="mb-0">{{ $item }}</p></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
