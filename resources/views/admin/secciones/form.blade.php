@extends('layouts.admin', ['titulo' => $seccion->nombreTipo().' · '.$seccion->pagina->titulo])

@php
    $v = fn ($c) => old($c, $seccion->{$c});
    $op = fn ($c, $d = null) => old("opciones.{$c}", $seccion->opcion($c, $d));
    $usa = fn ($c) => \App\Support\Bloques::usa($seccion->tipo, $c);
    $opciones = $tipo['opciones'] ?? [];
    $elem = $tipo['elementos'] ?? null;
    $ayudaMarkdown = 'Puedes usar **negrita**, *cursiva*, listas empezando la línea con «- » y enlaces [texto](https://…). Deja una línea en blanco entre párrafos.';
@endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.paginas.edit', $seccion->pagina) }}"><span class="fa-solid fa-angle-left me-1"></span>{{ $seccion->pagina->titulo }}</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $tipo['nombre'] }}</h2>
            <p class="text-700 mb-0 fs--1">{{ $tipo['descripcion'] }}</p>
        </div>
        @if ($seccion->pagina->visible)
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ $seccion->pagina->enlace() }}#s{{ $seccion->id }}" target="_blank" rel="noopener"><span class="fa-solid fa-eye me-2"></span>Ver en el sitio</a>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.secciones.update', $seccion) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            @if ($usa('etiqueta'))
                                <div class="col-md-5">
                                    <label class="form-label" for="etiqueta">Etiqueta</label>
                                    <input class="form-control" id="etiqueta" name="etiqueta" value="{{ $v('etiqueta') }}" maxlength="80" placeholder="Texto pequeño sobre el título" />
                                </div>
                            @endif
                            @if (in_array('resaltado', $opciones, true))
                                <div class="col-md-7">
                                    <label class="form-label" for="op_resaltado">Palabra resaltada (va antes del título, en color)</label>
                                    <input class="form-control" id="op_resaltado" name="opciones[resaltado]" value="{{ $op('resaltado') }}" maxlength="60" placeholder="Sistemas" />
                                </div>
                            @endif
                            @if ($usa('titulo'))
                                <div class="col-12">
                                    <label class="form-label" for="titulo">Título</label>
                                    <input class="form-control" id="titulo" name="titulo" value="{{ $v('titulo') }}" maxlength="200" />
                                </div>
                            @endif
                            @if ($usa('contenido'))
                                <div class="col-12">
                                    <label class="form-label" for="contenido">Texto</label>
                                    <textarea class="form-control" id="contenido" name="contenido" rows="{{ $seccion->tipo === 'texto' ? 10 : 4 }}" maxlength="20000">{{ $v('contenido') }}</textarea>
                                    <div class="form-text">{{ $ayudaMarkdown }}</div>
                                </div>
                            @endif
                            @if ($usa('boton'))
                                <div class="col-md-5">
                                    <label class="form-label" for="boton_texto">Botón: texto</label>
                                    <input class="form-control" id="boton_texto" name="boton_texto" value="{{ $v('boton_texto') }}" maxlength="60" placeholder="Ver sistemas" />
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="boton_enlace">Botón: enlace</label>
                                    <input class="form-control" id="boton_enlace" name="boton_enlace" value="{{ $v('boton_enlace') }}" maxlength="255" placeholder="/software  o  https://…  o  #contacto" />
                                </div>
                            @endif
                            @if ($usa('boton2'))
                                <div class="col-md-5">
                                    <label class="form-label" for="boton2_texto">Segundo botón: texto</label>
                                    <input class="form-control" id="boton2_texto" name="boton2_texto" value="{{ $v('boton2_texto') }}" maxlength="60" />
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="boton2_enlace">Segundo botón: enlace</label>
                                    <input class="form-control" id="boton2_enlace" name="boton2_enlace" value="{{ $v('boton2_enlace') }}" maxlength="255" />
                                </div>
                            @endif
                            @if (! array_intersect(['etiqueta', 'titulo', 'contenido', 'boton'], $tipo['campos']))
                                <div class="col-12"><p class="text-700 fs--1 mb-0">Esta sección se arma con sus {{ $elem ? \Illuminate\Support\Str::lower($elem['nombre']) : 'datos' }}{{ $elem ? ' (abajo)' : '' }}.</p></div>
                            @endif
                            @if (in_array($seccion->tipo, ['sistemas', 'manuales', 'planes', 'testimonios', 'clientes'], true))
                                <div class="col-12">
                                    <div class="alert alert-soft-info fs--1 py-2 mb-0">
                                        <span class="fa-solid fa-circle-info me-1"></span>
                                        El contenido sale de
                                        @switch($seccion->tipo)
                                            @case('sistemas') <a href="{{ route('admin.sistemas.index') }}">Software</a> @break
                                            @case('manuales') <a href="{{ route('admin.manuales.index') }}">Manuales</a> @break
                                            @case('planes') <a href="{{ route('admin.servicios.index') }}">Planes y precios</a> @break
                                            @default <a href="{{ route('admin.clientes.index') }}">Clientes</a>
                                        @endswitch
                                        : lo que agregues ahí aparece aquí solo.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Apariencia</h5>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" id="visible" name="visible" type="checkbox" value="1" @checked(old('visible', $seccion->visible)) />
                            <label class="form-check-label" for="visible">Visible</label>
                        </div>
                        @unless (in_array($seccion->tipo, ['carrusel', 'llamado', 'cifras'], true))
                            <div class="mb-3">
                                <label class="form-label" for="fondo">Fondo</label>
                                <select class="form-select" id="fondo" name="fondo">
                                    @foreach (\App\Support\Bloques::FONDOS as $clave => $texto)
                                        <option value="{{ $clave }}" @selected($v('fondo') === $clave)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endunless

                        @foreach ($opciones as $opcion)
                            @switch($opcion)
                                @case('altura')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_altura">Alto de las fotos</label>
                                        <select class="form-select" id="op_altura" name="opciones[altura]">
                                            @foreach (['normal' => 'Normal', 'alta' => 'Alta', 'pantalla' => 'Pantalla completa'] as $k => $t)
                                                <option value="{{ $k }}" @selected($op('altura', 'normal') === $k)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @break
                                @case('lado')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_lado">Imagen a la</label>
                                        <select class="form-select" id="op_lado" name="opciones[lado]">
                                            <option value="derecha" @selected($op('lado', 'derecha') === 'derecha')>Derecha</option>
                                            <option value="izquierda" @selected($op('lado') === 'izquierda')>Izquierda</option>
                                        </select>
                                    </div>
                                    @break
                                @case('alineacion')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_alineacion">Texto (sin imagen)</label>
                                        <select class="form-select" id="op_alineacion" name="opciones[alineacion]">
                                            <option value="centro" @selected($op('alineacion', 'centro') === 'centro')>Centrado</option>
                                            <option value="izquierda" @selected($op('alineacion') === 'izquierda')>A la izquierda (textos largos)</option>
                                        </select>
                                    </div>
                                    @break
                                @case('columnas')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_columnas">Tarjetas por fila</label>
                                        <select class="form-select" id="op_columnas" name="opciones[columnas]">
                                            @foreach (['2', '3', '4'] as $n)
                                                <option value="{{ $n }}" @selected((string) $op('columnas', '3') === $n)>{{ $n }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @break
                                @case('estilo')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_estilo">Presentación</label>
                                        <select class="form-select" id="op_estilo" name="opciones[estilo]">
                                            <option value="tarjetas" @selected($op('estilo', 'tarjetas') === 'tarjetas')>Tarjetas (catálogo)</option>
                                            <option value="filas" @selected($op('estilo') === 'filas')>Filas grandes con imagen</option>
                                        </select>
                                    </div>
                                    @break
                                @case('categoria_id')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_categoria">Solo la categoría</label>
                                        <select class="form-select" id="op_categoria" name="opciones[categoria_id]">
                                            <option value="">Todas</option>
                                            @foreach ($categorias as $id => $nombre)
                                                <option value="{{ $id }}" @selected((string) $op('categoria_id') === (string) $id)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @break
                                @case('sistema_id')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_sistema">Solo manuales de</label>
                                        <select class="form-select" id="op_sistema" name="opciones[sistema_id]">
                                            <option value="">Todos los sistemas</option>
                                            @foreach ($sistemas as $id => $nombre)
                                                <option value="{{ $id }}" @selected((string) $op('sistema_id') === (string) $id)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @break
                                @case('limite')
                                    <div class="mb-3">
                                        <label class="form-label" for="op_limite">Máximo a mostrar</label>
                                        <input class="form-control" id="op_limite" name="opciones[limite]" type="number" min="0" max="100" value="{{ $op('limite') }}" placeholder="todos" />
                                    </div>
                                    @break
                                @default
                                    @php
                                        $textos = ['solo_destacados' => 'Solo los destacados', 'filtros' => 'Mostrar filtros (categoría, tipo, prueba)', 'mapa' => 'Mostrar el mapa de la dirección principal', 'whatsapp' => 'Agregar botón de WhatsApp'];
                                    @endphp
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" id="op_{{ $opcion }}" name="opciones[{{ $opcion }}]" type="checkbox" value="1" @checked($op($opcion)) />
                                        <label class="form-check-label" for="op_{{ $opcion }}">{{ $textos[$opcion] ?? $opcion }}</label>
                                    </div>
                            @endswitch
                        @endforeach
                    </div>
                </div>
                @if ($usa('imagen'))
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Imagen</h5>
                            @include('admin.partes.imagen', ['campo' => 'imagen', 'titulo' => $usa('imagen_oscura') ? 'Imagen (modo claro)' : 'Imagen', 'ayuda' => 'PNG, JPG o WEBP, hasta 4 MB.', 'url' => $seccion->url('imagen')])
                            @if ($usa('imagen_oscura'))
                                @include('admin.partes.imagen', ['campo' => 'imagen_oscura', 'titulo' => 'Imagen (modo oscuro)', 'ayuda' => 'Opcional.', 'url' => $seccion->url('imagen_oscura')])
                            @endif
                        </div>
                    </div>
                @endif
                <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>Guardar sección</button>
            </div>
        </div>
    </form>

    @if ($elem)
        @include('admin.secciones.elementos')
    @endif

    <form class="mt-4" method="POST" action="{{ route('admin.secciones.destroy', $seccion) }}" onsubmit="return confirm('¿Eliminar esta sección y todo su contenido?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar sección</button>
    </form>
@endsection
