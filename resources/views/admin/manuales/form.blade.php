@extends('layouts.admin', ['titulo' => $manual->exists ? $manual->titulo : 'Nuevo manual'])

@php $v = fn ($c) => old($c, $manual->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.manuales.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Manuales</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
        <h2 class="mb-0 text-1100">{{ $manual->exists ? $manual->titulo : 'Nuevo manual' }}</h2>
        @if ($manual->exists && $manual->visible)
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('manual', $manual->slug) }}" target="_blank" rel="noopener"><span class="fa-solid fa-eye me-2"></span>Ver en el sitio</a>
        @endif
    </div>

    <form method="POST" action="{{ $manual->exists ? route('admin.manuales.update', $manual) : route('admin.manuales.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($manual->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="titulo">Título *</label>
                                <input class="form-control" id="titulo" name="titulo" value="{{ $v('titulo') }}" required maxlength="150" placeholder="Cómo abrir y cerrar caja" />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="sistema_id">Sistema</label>
                                <select class="form-select" id="sistema_id" name="sistema_id">
                                    <option value="">General (sin sistema)</option>
                                    @foreach ($sistemas as $id => $nombre)
                                        <option value="{{ $id }}" @selected((string) $v('sistema_id') === (string) $id)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="resumen">Resumen</label>
                                <input class="form-control" id="resumen" name="resumen" value="{{ $v('resumen') }}" maxlength="300" placeholder="De qué trata, en una frase" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="contenido">Guía paso a paso</label>
                                <textarea class="form-control font-monospace fs--1" id="contenido" name="contenido" rows="18" maxlength="200000">{{ $v('contenido') }}</textarea>
                                <div class="form-text">
                                    Formato: <code>## Título</code> para subtítulos, <code>**negrita**</code>, listas con <code>- </code> o <code>1. </code>,
                                    enlaces <code>[texto](https://…)</code> e imágenes <code>![descripción](https://…/captura.png)</code>. Deja una línea en blanco entre párrafos.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Publicación</h5>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" id="visible" name="visible" type="checkbox" value="1" @checked(old('visible', $manual->visible)) />
                            <label class="form-check-label" for="visible">Publicado</label>
                        </div>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" id="solo_clientes" name="solo_clientes" type="checkbox" value="1" @checked(old('solo_clientes', $manual->solo_clientes)) />
                            <label class="form-check-label" for="solo_clientes"><span class="fa-solid fa-lock me-1 text-warning"></span>Solo para clientes</label>
                        </div>
                        <p class="fs--2 text-700 mb-3">Solo lo ven las cuentas de clientes con el sistema del manual vigente (sin sistema: cualquier cliente con un sistema vigente). Los demás ven el título con un candado y se les pide entrar.</p>
                        <div class="row g-3">
                            <div class="col-7">
                                <label class="form-label" for="slug">Dirección</label>
                                <input class="form-control" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="170" placeholder="del título" />
                            </div>
                            <div class="col-5">
                                <label class="form-label" for="orden">Orden</label>
                                <input class="form-control" id="orden" name="orden" type="number" min="0" value="{{ $v('orden') }}" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">PDF y video</h5>
                        <div class="mb-3">
                            <label class="form-label" for="archivo">Archivo PDF</label>
                            @if ($manual->archivo)
                                <div class="mb-2 fs--1"><a href="{{ $manual->urlArchivo() }}" target="_blank" rel="noopener"><span class="fa-solid fa-file-pdf text-danger me-1"></span>Ver PDF actual</a></div>
                            @endif
                            <input class="form-control form-control-sm" id="archivo" name="archivo" type="file" accept="application/pdf" />
                            <div class="form-text">Hasta 20 MB. Se guarda en Contabo.</div>
                            @if ($manual->archivo)
                                <div class="form-check mt-1">
                                    <input class="form-check-input" id="quitar_archivo" name="quitar_archivo" type="checkbox" value="1" />
                                    <label class="form-check-label fs--1 text-700" for="quitar_archivo">Quitar PDF</label>
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="form-label" for="video_url">Video (YouTube o Vimeo)</label>
                            <input class="form-control" id="video_url" name="video_url" type="url" value="{{ $v('video_url') }}" maxlength="255" placeholder="https://www.youtube.com/watch?v=…" />
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>{{ $manual->exists ? 'Guardar cambios' : 'Crear manual' }}</button>
            </div>
        </div>
    </form>
    @if ($manual->exists)
        <form class="mt-3" method="POST" action="{{ route('admin.manuales.destroy', $manual) }}" onsubmit="return confirm('¿Eliminar este manual?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar manual</button>
        </form>
    @endif
@endsection
