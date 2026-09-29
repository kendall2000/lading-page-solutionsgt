{{-- Logos de clientes (los que tienen logo y «Mostrar el logo» activo). --}}
@if ($datos['logos']->isNotEmpty())
    <section class="py-6 {{ $fondo }}" id="s{{ $s->id }}">
        <div class="container-small px-lg-7 px-xxl-3">
            <p class="text-center text-700 fw-semi-bold mb-4">{{ $s->titulo ?: 'Empresas que confían en nuestro trabajo' }}</p>
            <div class="row g-0 justify-content-center">
                @foreach ($datos['logos'] as $cliente)
                    <div class="col-6 col-md-3">
                        <div class="p-3 p-lg-5 d-flex flex-center h-100 border-1 border-dashed border-bottom border-end">
                            @if ($cliente->sitio_web)<a href="{{ $cliente->sitio_web }}" target="_blank" rel="noopener">@endif
                            <img class="sgt-logo-cliente" src="{{ $cliente->url('logo') }}" alt="{{ $cliente->empresa }}" title="{{ $cliente->empresa }}" loading="lazy" />
                            @if ($cliente->sitio_web)</a>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
