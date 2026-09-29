@extends('layouts.publico', ['titulo' => $manual->titulo, 'descripcion' => $manual->resumen])

@section('contenido')
    <section class="py-9 sgt-encabezado">
        <div class="container-small px-lg-7 px-xxl-3">
            <nav aria-label="Ruta" class="mb-3">
                <ol class="breadcrumb mb-0 fs--1">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ \App\Support\Sitio::enlaceBloque('manuales') }}">Manuales</a></li>
                    @if ($manual->sistema)<li class="breadcrumb-item"><a href="{{ route('sistema', $manual->sistema->slug) }}">{{ $manual->sistema->nombre }}</a></li>@endif
                    <li class="breadcrumb-item active" aria-current="page">{{ $manual->titulo }}</li>
                </ol>
            </nav>
            <h1 class="fs-4 fs-md-5 fw-black mb-3">{{ $manual->titulo }}</h1>
            @if ($manual->resumen)<p class="fs-0 text-700 mb-4" style="max-width: 46rem">{{ $manual->resumen }}</p>@endif
            <div class="d-flex flex-wrap gap-2">
                @if ($manual->archivo)
                    <a class="btn btn-primary" href="{{ $manual->urlArchivo() }}" target="_blank" rel="noopener"><span class="fa-solid fa-file-pdf me-2"></span>Descargar PDF</a>
                @endif
                @if ($manual->sistema)
                    <a class="btn btn-phoenix-secondary" href="{{ route('sistema', $manual->sistema->slug) }}"><span class="{{ $manual->sistema->icono }} me-2"></span>Ver {{ $manual->sistema->nombre }}</a>
                @endif
            </div>
        </div>
    </section>

    <section class="py-9">
        <div class="container-small px-lg-7 px-xxl-3">
            <div class="row g-6">
                <div class="{{ $otros->isNotEmpty() ? 'col-lg-8' : 'col-12' }}">
                    @if ($video = $manual->videoEmbebido())
                        <div class="ratio ratio-16x9 mb-6 rounded-3 overflow-hidden shadow-sm">
                            <iframe src="{{ $video }}" title="{{ $manual->titulo }}" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @elseif ($manual->video_url)
                        <p><a href="{{ $manual->video_url }}" target="_blank" rel="noopener"><span class="fa-solid fa-play me-2"></span>Ver el video</a></p>
                    @endif
                    @if ($manual->contenido)
                        <div class="sgt-contenido sgt-manual text-900 fs-0">{{ $manual->contenidoHtml() }}</div>
                    @elseif ($manual->archivo)
                        <div class="ratio mb-4" style="--phoenix-aspect-ratio: 130%">
                            <iframe src="{{ $manual->urlArchivo() }}" title="{{ $manual->titulo }}" class="border border-300 rounded-3"></iframe>
                        </div>
                    @endif
                </div>
                @if ($otros->isNotEmpty())
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm position-sticky" style="top: 6rem">
                            <div class="card-body">
                                <h5 class="mb-3">Otros manuales</h5>
                                <ul class="list-unstyled mb-0">
                                    @foreach ($otros as $o)
                                        <li class="mb-2"><a class="fs--1" href="{{ route('manual', $o->slug) }}"><span class="fa-solid fa-book me-2 text-600"></span>{{ $o->titulo }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
    @push('estilos')
        <style>
            .sgt-manual h1, .sgt-manual h2, .sgt-manual h3 { margin-top: 2rem; margin-bottom: 1rem; }
            .sgt-manual ol, .sgt-manual ul { padding-left: 1.5rem; }
            .sgt-manual code { background: var(--phoenix-gray-100); padding: .1rem .3rem; border-radius: .25rem; }
            .sgt-manual blockquote { border-left: 4px solid var(--phoenix-primary); padding-left: 1rem; color: var(--phoenix-gray-700); }
            .sgt-manual table { width: 100%; margin-bottom: 1rem; }
            .sgt-manual th, .sgt-manual td { border: 1px solid var(--phoenix-gray-200); padding: .5rem; }
        </style>
    @endpush

    @push('datos_estructurados')
        @php
            $ficha = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'TechArticle',
                'headline' => $manual->titulo,
                'description' => $manual->resumen,
                'url' => route('manual', $manual->slug),
                'inLanguage' => 'es',
                'dateModified' => $manual->updated_at?->toAtomString(),
                'about' => $manual->sistema ? ['@type' => 'SoftwareApplication', 'name' => $manual->sistema->nombre, 'url' => route('sistema', $manual->sistema->slug)] : null,
                'publisher' => ['@type' => 'Organization', 'name' => \App\Support\Sitio::nombre(), 'url' => route('inicio')],
            ]);
        @endphp
        <script type="application/ld+json">{!! json_encode($ficha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush
@endsection
