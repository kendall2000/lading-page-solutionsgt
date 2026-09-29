@extends('layouts.admin', ['titulo' => $sistema->exists ? $sistema->nombre : 'Nuevo sistema'])

@php $v = fn ($c) => old($c, $sistema->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.sistemas.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Software</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
        <h2 class="mb-0 text-1100">{{ $sistema->exists ? $sistema->nombre : 'Nuevo sistema' }}</h2>
        @if ($sistema->exists && $sistema->visible)
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('sistema', $sistema->slug) }}" target="_blank" rel="noopener"><span class="fa-solid fa-eye me-2"></span>Ver en el sitio</a>
        @endif
    </div>

    <form method="POST" action="{{ $sistema->exists ? route('admin.sistemas.update', $sistema) : route('admin.sistemas.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($sistema->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="nombre">Nombre *</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="120" />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="slug">Dirección web</label>
                                <div class="input-group">
                                    <span class="input-group-text fs--1">/sistemas/</span>
                                    <input class="form-control" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="140" placeholder="se genera del nombre" />
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="resumen">Resumen *</label>
                                <textarea class="form-control" id="resumen" name="resumen" rows="2" required maxlength="300">{{ $v('resumen') }}</textarea>
                                <div class="form-text">Una o dos frases: aparece en la portada.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="descripcion">Descripción completa</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="6" maxlength="10000">{{ $v('descripcion') }}</textarea>
                                <div class="form-text">Se muestra en la página del sistema. Cada línea es un párrafo.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="caracteristicas">Características</label>
                                <textarea class="form-control" id="caracteristicas" name="caracteristicas" rows="8" maxlength="5000" placeholder="Una por línea">{{ $v('caracteristicas') }}</textarea>
                                <div class="form-text">Una por línea; se muestran con una marca de verificación.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tecnologias">Tecnologías</label>
                                <input class="form-control" id="tecnologias" name="tecnologias" value="{{ $v('tecnologias') }}" maxlength="300" placeholder="Laravel, MySQL, Bootstrap" />
                                <div class="form-text">Separadas por coma.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="url_demo">Enlace de demo en línea o descarga (opcional)</label>
                                <input class="form-control" id="url_demo" name="url_demo" type="url" value="{{ $v('url_demo') }}" maxlength="255" placeholder="https://" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Publicación</h5>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="visible" name="visible" type="checkbox" value="1" @checked(old('visible', $sistema->visible)) />
                            <label class="form-check-label" for="visible">Visible en el sitio</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" id="destacado" name="destacado" type="checkbox" value="1" @checked(old('destacado', $sistema->destacado)) />
                            <label class="form-check-label" for="destacado">Marcar como destacado</label>
                        </div>
                        <div class="row g-3">
                            <div class="col-5">
                                <label class="form-label" for="orden">Orden</label>
                                <input class="form-control" id="orden" name="orden" type="number" min="0" value="{{ $v('orden') }}" />
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="icono">Ícono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><span class="{{ $v('icono') }}" id="vistaIcono"></span></span>
                                    <input class="form-control" id="icono" name="icono" value="{{ $v('icono') }}" maxlength="60" oninput="document.getElementById('vistaIcono').className = this.value" />
                                </div>
                            </div>
                        </div>
                        <div class="form-text">Ícono de <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" rel="noopener">Font Awesome</a>, p. ej. <code>fa-solid fa-utensils</code>.</div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Catálogo</h5>
                        <div class="mb-3">
                            <label class="form-label" for="categoria_id">Categoría</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Sin categoría</option>
                                @foreach ($categorias as $id => $nombre)
                                    <option value="{{ $id }}" @selected((string) $v('categoria_id') === (string) $id)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Las categorías se crean en la lista de Software, abajo.</div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="modalidad">Tipo</label>
                                <select class="form-select" id="modalidad" name="modalidad">
                                    @foreach (\App\Models\Sistema::MODALIDADES as $clave => [$texto])
                                        <option value="{{ $clave }}" @selected($v('modalidad') === $clave)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="precio">Precio</label>
                                <input class="form-control" id="precio" name="precio" value="{{ $v('precio') }}" maxlength="60" placeholder="Desde Q350/mes" />
                            </div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="acepta_demo" name="acepta_demo" type="checkbox" value="1" @checked(old('acepta_demo', $sistema->acepta_demo)) />
                            <label class="form-check-label" for="acepta_demo">Pueden pedir una demostración</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="acepta_prueba" name="acepta_prueba" type="checkbox" value="1" @checked(old('acepta_prueba', $sistema->acepta_prueba)) />
                            <label class="form-check-label" for="acepta_prueba">Pueden pedir una prueba con usuario y contraseña</label>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 14rem">
                            <span class="input-group-text">Prueba de</span>
                            <input class="form-control" id="dias_prueba" name="dias_prueba" type="number" min="1" max="365" value="{{ $v('dias_prueba') ?: 15 }}" aria-label="Días de prueba" />
                            <span class="input-group-text">días</span>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Imagen principal</h5>
                        @include('admin.partes.imagen', ['campo' => 'imagen', 'titulo' => 'Captura (modo claro)', 'ayuda' => 'Horizontal, 1600 px aprox. PNG, JPG o WEBP.', 'url' => $sistema->url('imagen')])
                        @include('admin.partes.imagen', ['campo' => 'imagen_oscura', 'titulo' => 'Captura (modo oscuro)', 'ayuda' => 'Opcional.', 'url' => $sistema->url('imagen_oscura')])
                    </div>
                </div>
                <button class="btn btn-primary w-100 mb-3" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>{{ $sistema->exists ? 'Guardar cambios' : 'Crear sistema' }}</button>
            </div>
        </div>
    </form>

    @if ($sistema->exists)
        {{-- Galería de capturas --}}
        <div class="card mt-4">
            <div class="card-body">
                <h4 class="mb-1">Capturas de pantalla</h4>
                <p class="text-700 fs--1 mb-3">Se muestran en la galería de la portada y en la página del sistema.</p>
                <form class="d-flex flex-wrap gap-2 mb-4" method="POST" action="{{ route('admin.sistemas.imagenes.store', $sistema) }}" enctype="multipart/form-data">
                    @csrf
                    <input class="form-control" style="max-width: 420px" name="capturas[]" type="file" accept="image/png,image/jpeg,image/webp" multiple required />
                    <button class="btn btn-phoenix-primary" type="submit"><span class="fa-solid fa-upload me-2"></span>Subir capturas</button>
                </form>
                @if ($sistema->imagenes->isEmpty())
                    <p class="text-700 fs--1 mb-0">Sin capturas todavía.</p>
                @else
                    <div class="row g-3">
                        @foreach ($sistema->imagenes as $img)
                            <div class="col-sm-6 col-md-4 col-xl-3">
                                <div class="border border-300 rounded-3 p-2 h-100">
                                    <img class="rounded-2 w-100 mb-2" src="{{ $img->url() }}" alt="" style="aspect-ratio: 16/10; object-fit: cover;" />
                                    <form method="POST" action="{{ route('admin.sistemas.imagenes.update', [$sistema, $img]) }}" class="d-flex gap-1 mb-1">
                                        @csrf
                                        @method('PUT')
                                        <input class="form-control form-control-sm" name="titulo" value="{{ $img->titulo }}" placeholder="Título" maxlength="150" aria-label="Título" />
                                        <input class="form-control form-control-sm" name="orden" type="number" min="0" value="{{ $img->orden }}" style="width: 4.5rem" aria-label="Orden" />
                                        <button class="btn btn-phoenix-secondary btn-sm" type="submit" title="Guardar"><span class="fa-solid fa-check"></span></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.sistemas.imagenes.destroy', [$sistema, $img]) }}" onsubmit="return confirm('¿Eliminar esta captura?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link text-danger p-0 fs--2" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <form class="mt-4" method="POST" action="{{ route('admin.sistemas.destroy', $sistema) }}" onsubmit="return confirm('¿Eliminar este sistema y todas sus capturas? No se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button class="btn btn-link text-danger px-0" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar sistema</button>
        </form>
    @endif
@endsection
