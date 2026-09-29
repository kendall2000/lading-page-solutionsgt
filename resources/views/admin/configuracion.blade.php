@extends('layouts.admin', ['titulo' => 'Datos del sitio'])

@php
    $campo = fn ($nombre) => old($nombre, $cfg->{$nombre});
    $redes = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'github' => 'GitHub'];
@endphp

@section('contenido')
    <h2 class="mb-2 text-1100">Datos del sitio</h2>
    <p class="text-700 mb-4">Nombre, contacto, redes sociales, color y logo del sitio. Los textos y fotos de cada página se editan en <a href="{{ route('admin.paginas.index') }}">Páginas y menú</a>.</p>

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
                        <div class="d-flex flex-between-center mb-3">
                            <h4 class="mb-0">Chat en vivo</h4>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" id="chat_activo" name="chat_activo" type="checkbox" value="1" @checked(old('chat_activo', $cfg->chat_activo ?? true)) />
                                <label class="form-check-label fw-semi-bold" for="chat_activo">Mostrar el chat en el sitio</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="chat_titulo">Título de la ventana</label>
                                <input class="form-control" id="chat_titulo" name="chat_titulo" value="{{ $campo('chat_titulo') }}" maxlength="80" placeholder="Chatea con nosotros" />
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="chat_bienvenida">Mensaje de bienvenida</label>
                                <input class="form-control" id="chat_bienvenida" name="chat_bienvenida" value="{{ $campo('chat_bienvenida') }}" maxlength="300" placeholder="Escríbenos y te respondemos aquí mismo…" />
                            </div>
                        </div>
                        <p class="form-text mb-0 mt-2">Las conversaciones se responden en <a href="{{ route('admin.chat.index') }}">Chat en vivo</a>. Si un visitante escribe y no estás conectado, te llega un correo a «Avisos a» (Correos).</p>
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
