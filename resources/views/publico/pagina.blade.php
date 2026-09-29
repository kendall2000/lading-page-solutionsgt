@extends('layouts.publico')

{{-- Página armada con secciones (panel → Páginas). Cada tipo tiene su vista en publico/bloques. --}}
@section('contenido')
    @php
        $primera = $secciones->first()?->tipo;
        $conEncabezado = ! $pagina->es_inicio && ! in_array($primera, ['portada', 'carrusel'], true);
    @endphp

    @if ($conEncabezado)
        @php $fondoEnc = $pagina->urlEncabezado(); @endphp
        <section class="py-9 py-md-10 {{ $fondoEnc ? 'sgt-encabezado-img sgt-seccion-oscura' : 'sgt-encabezado' }}" @if ($fondoEnc) style="background-image: url('{{ $fondoEnc }}')" @endif>
            <div class="container-small px-lg-7 px-xxl-3 position-relative text-center">
                <nav aria-label="Ruta" class="d-flex justify-content-center mb-3">
                    <ol class="breadcrumb mb-0 fs--1">
                        <li class="breadcrumb-item"><a class="{{ $fondoEnc ? 'text-white' : '' }}" href="{{ route('inicio') }}">Inicio</a></li>
                        @if ($pagina->padre)
                            <li class="breadcrumb-item"><a class="{{ $fondoEnc ? 'text-white' : '' }}" href="{{ $pagina->padre->enlace() }}">{{ $pagina->padre->nombreMenu() }}</a></li>
                        @endif
                        <li class="breadcrumb-item active {{ $fondoEnc ? 'text-300' : '' }}" aria-current="page">{{ $pagina->nombreMenu() }}</li>
                    </ol>
                </nav>
                <h1 class="fs-4 fs-md-5 fw-black mb-3">{{ $pagina->titulo }}</h1>
                @if ($pagina->subtitulo)
                    <p class="fs-0 mb-0 mx-auto text-700" style="max-width: 44rem">{{ $pagina->subtitulo }}</p>
                @endif
            </div>
        </section>
    @endif

    @forelse ($secciones as $seccion)
        @include('publico.bloques.'.$seccion->tipo, ['s' => $seccion, 'fondo' => match ($seccion->fondo) {
            'suave' => 'bg-soft-primary dark__bg-1100',
            'oscuro' => 'bg-1100 dark__bg-1000 sgt-seccion-oscura',
            default => '',
        }])
    @empty
        <section class="py-10 text-center text-700"><p class="mb-0">Esta página todavía no tiene contenido.</p></section>
    @endforelse
@endsection
