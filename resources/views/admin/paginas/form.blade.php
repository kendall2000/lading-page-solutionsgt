@extends('layouts.admin', ['titulo' => $pagina->exists ? $pagina->titulo : 'Nueva página'])

@php $v = fn ($c) => old($c, $pagina->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.paginas.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Páginas y menú</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
        <h2 class="mb-0 text-1100">{{ $pagina->exists ? $pagina->titulo : 'Nueva página' }}</h2>
        @if ($pagina->exists && $pagina->visible)
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ $pagina->enlace() }}" target="_blank" rel="noopener"><span class="fa-solid fa-eye me-2"></span>Ver en el sitio</a>
        @endif
    </div>

    <div class="row g-4">
        {{-- Secciones de la página --}}
        @if ($pagina->exists)
            <div class="col-xl-7 order-xl-1">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-1">Secciones</h4>
                        <p class="text-700 fs--1 mb-3">Se muestran en este orden, de arriba hacia abajo.</p>
                        @forelse ($pagina->secciones as $sec)
                            <div class="d-flex align-items-center gap-3 border border-300 rounded-3 p-3 mb-2 {{ $sec->visible ? '' : 'bg-light opacity-75' }}">
                                <div class="d-flex flex-column">
                                    @foreach (['arriba' => 'up', 'abajo' => 'down'] as $dir => $flecha)
                                        <form method="POST" action="{{ route('admin.secciones.mover', [$sec, $dir]) }}">
                                            @csrf
                                            <button class="btn btn-link p-0 text-600 lh-1" type="submit" title="Mover {{ $dir }}" @disabled(($dir === 'arriba' && $loop->parent->first) || ($dir === 'abajo' && $loop->parent->last))><span class="fa-solid fa-caret-{{ $flecha }}"></span></button>
                                        </form>
                                    @endforeach
                                </div>
                                <span class="text-primary"><span data-feather="{{ \App\Support\Bloques::TIPOS[$sec->tipo]['icono'] ?? 'square' }}"></span></span>
                                <div class="flex-1 min-w-0">
                                    <a class="fw-bold text-1000" href="{{ route('admin.secciones.edit', $sec) }}">{{ $sec->titulo ?: $sec->nombreTipo() }}</a>
                                    <div class="fs--2 text-600">
                                        {{ $sec->nombreTipo() }}
                                        @if (\App\Support\Bloques::tieneElementos($sec->tipo)) · {{ $sec->elementos_count }} {{ \Illuminate\Support\Str::lower(\App\Support\Bloques::TIPOS[$sec->tipo]['elementos']['nombre']) }}@endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.secciones.alternar', $sec) }}">
                                    @csrf
                                    <button class="btn btn-phoenix-secondary btn-sm" type="submit" title="{{ $sec->visible ? 'Ocultar' : 'Mostrar' }}"><span class="fa-solid {{ $sec->visible ? 'fa-eye' : 'fa-eye-slash' }}"></span></button>
                                </form>
                                <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.secciones.edit', $sec) }}">Editar</a>
                            </div>
                        @empty
                            <p class="text-700 fs--1">Esta página todavía no tiene secciones. Agrega la primera abajo.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-1">Agregar sección</h4>
                        <p class="text-700 fs--1 mb-3">Elige qué tipo de contenido quieres poner. Se agrega al final y luego la completas.</p>
                        <div class="row g-2">
                            @foreach (\App\Support\Bloques::TIPOS as $clave => $t)
                                <div class="col-sm-6">
                                    <form method="POST" action="{{ route('admin.secciones.store', $pagina) }}" class="h-100">
                                        @csrf
                                        <input type="hidden" name="tipo" value="{{ $clave }}" />
                                        <button class="btn btn-phoenix-secondary w-100 h-100 text-start d-flex gap-3 p-3" type="submit">
                                            <span class="text-primary"><span data-feather="{{ $t['icono'] }}"></span></span>
                                            <span>
                                                <span class="d-block fw-bold text-1000">{{ $t['nombre'] }}</span>
                                                <span class="d-block fs--2 fw-normal text-700 white-space-normal" style="white-space: normal">{{ $t['descripcion'] }}</span>
                                            </span>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Datos de la página --}}
        <div class="{{ $pagina->exists ? 'col-xl-5' : 'col-xl-7' }}">
            <form class="card" method="POST" action="{{ $pagina->exists ? route('admin.paginas.update', $pagina) : route('admin.paginas.store') }}" enctype="multipart/form-data">
                @csrf
                @if ($pagina->exists) @method('PUT') @endif
                <div class="card-body">
                    <h4 class="mb-3">Datos de la página</h4>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="titulo">Título *</label>
                            <input class="form-control" id="titulo" name="titulo" value="{{ $v('titulo') }}" required maxlength="120" placeholder="Nosotros" />
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="titulo_menu">Nombre en el menú</label>
                            <input class="form-control" id="titulo_menu" name="titulo_menu" value="{{ $v('titulo_menu') }}" maxlength="60" placeholder="igual al título" />
                        </div>
                        @unless ($pagina->es_inicio)
                            <div class="col-md-6">
                                <label class="form-label" for="slug">Dirección</label>
                                <div class="input-group">
                                    <span class="input-group-text fs--1">/</span>
                                    <input class="form-control" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="140" placeholder="se genera del título" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="padre_id">Va dentro de (submenú)</label>
                                <select class="form-select" id="padre_id" name="padre_id">
                                    <option value="">— Menú principal —</option>
                                    @foreach ($padres as $id => $nombre)
                                        <option value="{{ $id }}" @selected((string) $v('padre_id') === (string) $id)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="slug" value="{{ $pagina->slug }}" />
                        @endunless
                        <div class="col-12">
                            <label class="form-label" for="subtitulo">Subtítulo</label>
                            <input class="form-control" id="subtitulo" name="subtitulo" value="{{ $v('subtitulo') }}" maxlength="300" placeholder="Frase corta bajo el título de la página" />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="meta_descripcion">Descripción para Google</label>
                            <input class="form-control" id="meta_descripcion" name="meta_descripcion" value="{{ $v('meta_descripcion') }}" maxlength="300" />
                        </div>
                        <div class="col-12">
                            @include('admin.partes.imagen', ['campo' => 'imagen_encabezado', 'titulo' => 'Foto de fondo del encabezado', 'ayuda' => 'Opcional. Horizontal, se oscurece para que se lea el título.', 'url' => $pagina->urlEncabezado()])
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-4">
                            @unless ($pagina->es_inicio)
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="visible" name="visible" type="checkbox" value="1" @checked(old('visible', $pagina->visible)) />
                                    <label class="form-check-label" for="visible">Publicada</label>
                                </div>
                            @endunless
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="en_menu" name="en_menu" type="checkbox" value="1" @checked(old('en_menu', $pagina->en_menu)) />
                                <label class="form-check-label" for="en_menu">Mostrar en el menú</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">{{ $pagina->exists ? 'Guardar página' : 'Crear página' }}</button>
                </div>
            </form>
            @if ($pagina->exists && ! $pagina->es_inicio)
                <form class="mt-3" method="POST" action="{{ route('admin.paginas.destroy', $pagina) }}" onsubmit="return confirm('¿Eliminar esta página y todas sus secciones? No se puede deshacer.')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar página</button>
                </form>
            @endif
        </div>
    </div>
@endsection
