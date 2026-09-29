{{--
    Páginas de error con el diseño de la plantilla (pages/errors/*.html de Phoenix).
    Sin menú ni consultas propias: tiene que verse aunque la base no responda
    (Sitio::config y el logo ya se protegen con rescue).
    Recibe: $codigo, $titulo, $mensaje y, opcional, $ilustracion (403, 404 o 500) y $boton.
--}}
@php $ilustracion = $ilustracion ?? null; @endphp
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
<main class="main" id="top">
    <div class="px-3">
        <div class="row min-vh-100 flex-center p-5">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="row justify-content-center align-items-center g-5">
                    @if ($ilustracion)
                        <div class="col-12 col-lg-6 text-center order-lg-1">
                            <img class="img-fluid w-lg-100 d-dark-none" src="{{ asset("assets/img/spot-illustrations/{$ilustracion}-illustration.png") }}" alt="" width="400" />
                            <img class="img-fluid w-md-50 w-lg-100 d-light-none" src="{{ asset("assets/img/spot-illustrations/dark_{$ilustracion}-illustration.png") }}" alt="" width="540" />
                        </div>
                    @endif
                    <div class="col-12 col-lg-6 text-center {{ $ilustracion ? 'text-lg-start' : '' }}">
                        <a class="d-inline-flex align-items-center mb-5" href="{{ url('/') }}">@include('partials.logo', ['alto' => 36])</a>
                        @if ($ilustracion && (string) $ilustracion === (string) $codigo)
                            <div>
                                <img class="img-fluid mb-6 w-50 w-lg-75 d-dark-none" src="{{ asset("assets/img/spot-illustrations/{$codigo}.png") }}" alt="Error {{ $codigo }}" />
                                <img class="img-fluid mb-6 w-50 w-lg-75 d-light-none" src="{{ asset("assets/img/spot-illustrations/dark_{$codigo}.png") }}" alt="Error {{ $codigo }}" />
                            </div>
                        @else
                            <p class="display-1 fw-black text-primary mb-3">{{ $codigo }}</p>
                        @endif
                        <h2 class="text-800 fw-bolder mb-3">{{ $titulo }}</h2>
                        <p class="text-900 mb-5">{{ $mensaje }}</p>
                        @if ($boton ?? true)
                            <a class="btn btn-lg btn-primary" href="{{ url('/') }}">Ir al inicio</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@include('partials.scripts')
</body>
</html>
