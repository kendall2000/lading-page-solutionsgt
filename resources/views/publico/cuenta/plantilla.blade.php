{{--
    Portal «Mi cuenta»: encabezado de perfil y pestañas (diseño apps/e-commerce/landing/profile.html de Phoenix).
    Recibe $cuenta, $conteo y $tituloPortal.
--}}
@extends('layouts.publico', ['titulo' => $tituloPortal, 'noIndexar' => true])

@section('contenido')
    @php
        $pestanasPortal = [
            ['cuenta.inicio', 'fa-house', 'Resumen', null],
            ['cuenta.sistemas', 'fa-laptop-code', 'Mis sistemas', $conteo['sistemas']],
            ['cuenta.solicitudes', 'fa-inbox', 'Solicitudes y pruebas', $conteo['solicitudes']],
            ['cuenta.conversaciones', 'fa-comments', 'Conversaciones', $conteo['conversaciones']],
            ['cuenta.manuales', 'fa-book', 'Manuales', null],
            ['cuenta.pagos', 'fa-credit-card', 'Pagos', null],
            ['cuenta.perfil', 'fa-user', 'Mi perfil', null],
        ];
    @endphp
    <section class="pt-6 pb-9 sgt-encabezado">
        <div class="container-small px-lg-7 px-xxl-3">
            <nav class="mb-3" aria-label="Ruta">
                <ol class="breadcrumb mb-0 fs--1">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cuenta.inicio') }}">Mi cuenta</a></li>
                    @unless (request()->routeIs('cuenta.inicio'))<li class="breadcrumb-item active" aria-current="page">{{ $tituloPortal }}</li>@endunless
                </ol>
            </nav>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center g-3 g-sm-4 text-center text-sm-start">
                        <div class="col-12 col-sm-auto">
                            <div class="avatar avatar-4xl mx-auto"><div class="avatar-name rounded-circle bg-soft-primary"><span class="text-primary fs-2">{{ $cuenta->iniciales() }}</span></div></div>
                        </div>
                        <div class="col-12 col-sm flex-1">
                            <h3 class="mb-1">{{ $cuenta->nombre }}</h3>
                            <p class="text-700 mb-0 fs--1">
                                {{ $cuenta->cliente?->empresa ?? $cuenta->empresa ?? $cuenta->correo }}
                                · Cliente desde {{ $cuenta->created_at->translatedFormat('F Y') }}
                            </p>
                        </div>
                        <div class="col-12 col-lg-auto">
                            <div class="d-flex justify-content-center justify-content-sm-start gap-4 gap-md-5 border-top border-top-lg-0 border-dashed border-300 pt-3 pt-lg-0">
                                <div class="text-center"><h4 class="fs-1 text-1000 mb-0">{{ $conteo['sistemas'] }}</h4><p class="fs--1 text-700 mb-0">Sistemas</p></div>
                                <div class="text-center"><h4 class="fs-1 text-1000 mb-0">{{ $conteo['solicitudes'] }}</h4><p class="fs--1 text-700 mb-0">Solicitudes</p></div>
                                <div class="text-center"><h4 class="fs-1 text-1000 mb-0">{{ $conteo['conversaciones'] }}</h4><p class="fs--1 text-700 mb-0">Chats</p></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto text-center">
                            <form method="POST" action="{{ route('cuenta.salir') }}">
                                @csrf
                                <button class="btn btn-phoenix-secondary btn-sm" type="submit"><span class="fa-solid fa-right-from-bracket me-1"></span>Salir</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="scrollbar mb-4">
                <ul class="nav nav-underline flex-nowrap pb-1">
                    @foreach ($pestanasPortal as [$rutaPestana, $iconoPestana, $textoPestana, $numeroPestana])
                        <li class="nav-item me-3">
                            <a class="nav-link text-nowrap {{ request()->routeIs($rutaPestana, $rutaPestana === 'cuenta.conversaciones' ? 'cuenta.conversacion' : $rutaPestana) ? 'active' : '' }}" href="{{ route($rutaPestana) }}">
                                <span class="fa-solid {{ $iconoPestana }} me-2"></span>{{ $textoPestana }}
                                @if ($numeroPestana !== null)<span class="text-700 fw-normal">({{ $numeroPestana }})</span>@endif
                                @if ($rutaPestana === 'cuenta.conversaciones' && $conteo['sinLeer'])
                                    <span class="badge badge-phoenix badge-phoenix-danger ms-1">{{ $conteo['sinLeer'] }} sin leer</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if (session('status'))
                <div class="alert alert-soft-success py-2 fs--1" role="alert">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-soft-danger py-2 fs--1" role="alert">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            @yield('portal')
        </div>
    </section>
@endsection
