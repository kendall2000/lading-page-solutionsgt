{{--
    Marco de las pantallas de acceso de clientes (entrar, registro, enlace, invitación):
    tarjeta centrada con el diseño de pages/authentication/simple de Phoenix, dentro del sitio.
--}}
@extends('layouts.publico', ['titulo' => $tituloMarco, 'noIndexar' => true])

@section('contenido')
    <section class="py-9 sgt-encabezado">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-sm">
                        <div class="card-body p-4 p-sm-5">
                            <div class="text-center mb-4">
                                <span class="sgt-icono mb-3"><span class="fa-solid {{ $iconoMarco ?? 'fa-user' }}"></span></span>
                                <h3 class="text-1000 mb-1">{{ $tituloMarco }}</h3>
                                @isset($subtituloMarco)<p class="text-700 mb-0">{{ $subtituloMarco }}</p>@endisset
                            </div>
                            @if (session('status'))
                                <div class="alert alert-soft-success py-2 fs--1" role="alert">{{ session('status') }}</div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-soft-danger py-2 fs--1" role="alert">
                                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                                </div>
                            @endif
                            @yield('marco')
                        </div>
                    </div>
                    @hasSection('pie')
                        <p class="text-center fs--1 text-700 mt-3 mb-0">@yield('pie')</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
