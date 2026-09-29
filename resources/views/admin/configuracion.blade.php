@extends('layouts.admin', ['titulo' => 'Datos del sitio'])

@php
    $campo = fn ($nombre) => old($nombre, $cfg->{$nombre});
    $redes = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'github' => 'GitHub'];
@endphp

@section('contenido')
    <h2 class="mb-2 text-1100">Datos del sitio</h2>
    <p class="text-700 mb-4">Textos, datos de contacto, redes sociales, colores e imágenes que se ven en el sitio público.</p>

    <form method="POST" action="{{ route('admin.sitio.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Identidad</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nombre">Nombre del sitio *</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ $campo('nombre') }}" required maxlength="100" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="eslogan">Eslogan</label>
                                <input class="form-control" id="eslogan" name="eslogan" value="{{ $campo('eslogan') }}" maxlength="150" placeholder="Desarrollo de sistemas empresariales" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="propietario">Tu nombre</label>
                                <input class="form-control" id="propietario" name="propietario" value="{{ $campo('propietario') }}" maxlength="120" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cargo">Tu cargo o profesión</label>
                                <input class="form-control" id="cargo" name="cargo" value="{{ $campo('cargo') }}" maxlength="120" placeholder="Desarrollador de software" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="color_primario">Color principal</label>
                                <div class="d-flex gap-2">
                                    <input class="form-control form-control-color" type="color" value="{{ $campo('color_primario') ?: '#3874ff' }}" oninput="document.getElementById('color_primario').value = this.value" title="Elegir color" />
                                    <input class="form-control" id="color_primario" name="color_primario" value="{{ $campo('color_primario') }}" maxlength="7" placeholder="#3874ff" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="meta_descripcion">Descripción para Google</label>
                                <input class="form-control" id="meta_descripcion" name="meta_descripcion" value="{{ $campo('meta_descripcion') }}" maxlength="300" />
                                <div class="form-text">La que aparece en los resultados de búsqueda y al compartir el enlace.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Portada</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="hero_resaltado">Palabra resaltada</label>
                                <input class="form-control" id="hero_resaltado" name="hero_resaltado" value="{{ $campo('hero_resaltado') }}" maxlength="60" placeholder="Sistemas" />
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="hero_titulo">Resto del título</label>
                                <input class="form-control" id="hero_titulo" name="hero_titulo" value="{{ $campo('hero_titulo') }}" maxlength="150" placeholder="a la medida de tu negocio" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="hero_texto">Texto de la portada</label>
                                <textarea class="form-control" id="hero_texto" name="hero_texto" rows="3" maxlength="600">{{ $campo('hero_texto') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Sobre mí</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="sobre_titulo">Título</label>
                                <input class="form-control" id="sobre_titulo" name="sobre_titulo" value="{{ $campo('sobre_titulo') }}" maxlength="150" placeholder="Hola, soy" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="sobre_texto">Tu historia</label>
                                <textarea class="form-control" id="sobre_texto" name="sobre_texto" rows="7" maxlength="3000">{{ $campo('sobre_texto') }}</textarea>
                                <div class="form-text">Cada línea se muestra como un párrafo.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="anios_experiencia">Años de experiencia</label>
                                <input class="form-control" id="anios_experiencia" name="anios_experiencia" type="number" min="0" max="99" value="{{ $campo('anios_experiencia') }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="proyectos_entregados">Proyectos entregados</label>
                                <input class="form-control" id="proyectos_entregados" name="proyectos_entregados" type="number" min="0" value="{{ $campo('proyectos_entregados') }}" />
                                <div class="form-text">Con 0 no se muestra la cifra.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Contacto</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="telefono">Teléfono</label>
                                <input class="form-control" id="telefono" name="telefono" value="{{ $campo('telefono') }}" maxlength="30" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="whatsapp">WhatsApp</label>
                                <input class="form-control" id="whatsapp" name="whatsapp" value="{{ $campo('whatsapp') }}" maxlength="30" placeholder="5555 5555" />
                                <div class="form-text">Con 8 dígitos se agrega +502.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="correo">Correo</label>
                                <input class="form-control" id="correo" name="correo" type="email" value="{{ $campo('correo') }}" maxlength="150" />
                                <div class="form-text">Se muestra en el sitio. Quién recibe el aviso de cada mensaje nuevo se configura en <a href="{{ route('admin.correos.index') }}">Correos</a>.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="horario">Horario de atención</label>
                                <input class="form-control" id="horario" name="horario" value="{{ $campo('horario') }}" maxlength="150" placeholder="Lunes a viernes, 8:00 a 17:00" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Redes sociales</h4>
                        <p class="text-700 fs--1">Solo se muestran las que tengan enlace.</p>
                        @foreach ($redes as $red => $nombreRed)
                            <div class="input-group mb-2">
                                <span class="input-group-text" style="width: 2.75rem"><span class="fa-brands fa-{{ $red === 'linkedin' ? 'linkedin-in' : $red }}"></span></span>
                                <input class="form-control" name="{{ $red }}" type="url" value="{{ $campo($red) }}" placeholder="https://… ({{ $nombreRed }})" maxlength="255" aria-label="{{ $nombreRed }}" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Imágenes</h4>
                        @foreach (\App\Models\ConfiguracionSitio::IMAGENES as $img => [$tituloImg, $ayuda])
                            @include('admin.partes.imagen', ['campo' => $img, 'titulo' => $tituloImg, 'ayuda' => $ayuda, 'url' => $cfg->url($img)])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="position-sticky bottom-0 py-3 z-index-1 border-top border-200" style="background: var(--phoenix-body-bg)">
            <button class="btn btn-primary px-6" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>Guardar cambios</button>
        </div>
    </form>
@endsection
