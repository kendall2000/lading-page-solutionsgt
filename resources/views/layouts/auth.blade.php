<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
<main class="main" id="top">
    <div class="row vh-100 g-0">
        <div class="col-lg-6 position-relative d-none d-lg-block">
            <div class="bg-holder" style="background-image:url({{ asset('assets/img/bg/30.png') }}); background-size: cover; background-position: center;"></div>
        </div>
        <div class="col-lg-6">
            <div class="row flex-center h-100 g-0 px-4 px-sm-0">
                <div class="col col-sm-6 col-lg-7 col-xl-6">
                    <a class="d-flex flex-center text-decoration-none mb-4" href="{{ route('inicio') }}">
                        <div class="d-flex align-items-center fw-bolder fs-5 d-inline-block">
                            @include('partials.logo', ['alto' => 48])
                        </div>
                    </a>
                    @yield('contenido')
                </div>
            </div>
        </div>
    </div>
</main>
@include('partials.scripts')
</body>
</html>
