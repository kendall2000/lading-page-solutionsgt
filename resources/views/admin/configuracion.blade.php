@extends('layouts.admin', ['titulo' => 'Configuración del sistema'])

{{-- Como «Configuración del sistema» del restaurante: encabezado con acciones + pestañas verticales (add-product.html). --}}
@php
    $v = fn (string $campo) => old($campo, $cfg->{$campo});
    $pestanas = \App\Http\Controllers\Admin\ConfiguracionController::PESTANAS;
    $errorEn = [
        'imagenes' => array_keys(\App\Models\ConfiguracionSitio::IMAGENES),
        'inicio' => ['fotos_inicio', 'fotos_inicio.*', 'portada_imagen', 'portada_imagen_oscura'],
        'colores' => ['color_primario', 'color_secundario'],
        'redes' => ['facebook', 'instagram', 'linkedin', 'tiktok', 'youtube', 'github'],
        'chat' => ['chat_titulo', 'chat_bienvenida'],
        'acceso' => ['login_titulo', 'login_subtitulo'],
    ];
    $activa = collect($errorEn)->search(fn ($campos) => $errors->hasAny($campos))
        ?: (array_key_exists((string) request('pestana'), $pestanas) ? request('pestana') : 'empresa');
    $redes = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'github' => 'GitHub'];
@endphp

@section('contenido')
    <form class="mb-9" method="POST" action="{{ route('admin.sitio.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="pestana" id="pestanaActiva" value="{{ $activa }}" />
        <div class="row g-3 flex-between-end mb-5">
            <div class="col-auto">
                <h2 class="mb-2">Configuración del sistema</h2>
                <h5 class="text-700 fw-semi-bold">Nombre, logo, íconos, fotos de inicio, colores y datos de contacto de todo el sitio.</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2" href="{{ route('inicio') }}" target="_blank" rel="noopener"><span class="fa-solid fa-eye me-2"></span>Ver sitio</a>
                <button class="btn btn-primary" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>Guardar configuración</button>
            </div>
        </div>

        <div class="row g-0 border-top border-bottom border-300">
            <div class="col-sm-4 col-xl-3">
                <div class="nav flex-sm-column border-bottom border-bottom-sm-0 border-end-sm border-300 fs--1 vertical-tab h-100" role="tablist" aria-orientation="vertical">
                    @foreach ($pestanas as $id => [$icono, $texto])
                        <a class="nav-link {{ $loop->last ? '' : 'border-end border-end-sm-0 border-bottom-sm border-300' }} text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center {{ $id === $activa ? 'active' : '' }}"
                           id="tab-{{ $id }}" data-bs-toggle="tab" data-bs-target="#panel-{{ $id }}" data-pestana="{{ $id }}" role="tab" aria-controls="panel-{{ $id }}" aria-selected="{{ $id === $activa ? 'true' : 'false' }}">
                            <span class="me-sm-2 fs-4 nav-icons" data-feather="{{ $icono }}"></span><span class="d-none d-sm-inline">{{ $texto }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-sm-8 col-xl-9">
                <div class="tab-content py-3 ps-sm-4 h-100">
                    {{-- Empresa y contacto --}}
                    <div class="tab-pane fade {{ $activa === 'empresa' ? 'show active' : '' }}" id="panel-empresa" role="tabpanel">
                        <h4 class="mb-3 d-sm-none">Empresa y contacto</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="nombre">Nombre del sitio *</label>
                                <input class="form-control" id="nombre" name="nombre" maxlength="100" required value="{{ $v('nombre') }}" />
                                <div class="form-text">Pestaña del navegador, menú, panel y correos.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="eslogan">Eslogan</label>
                                <input class="form-control" id="eslogan" name="eslogan" maxlength="150" value="{{ $v('eslogan') }}" placeholder="Desarrollo de sistemas empresariales" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="telefono">Teléfono</label>
                                <input class="form-control" id="telefono" name="telefono" maxlength="30" value="{{ $v('telefono') }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="whatsapp">WhatsApp</label>
                                <input class="form-control" id="whatsapp" name="whatsapp" maxlength="30" value="{{ $v('whatsapp') }}" placeholder="5555 5555" />
                                <div class="form-text">Con 8 dígitos se agrega +502.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="correo">Correo de contacto</label>
                                <input class="form-control" id="correo" name="correo" type="email" maxlength="150" value="{{ $v('correo') }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="horario">Horario de atención</label>
                                <input class="form-control" id="horario" name="horario" maxlength="150" value="{{ $v('horario') }}" placeholder="Lunes a viernes, 8:00 a 17:00" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="pie_texto">Pie de página del panel</label>
                                <input class="form-control" id="pie_texto" name="pie_texto" maxlength="200" value="{{ $v('pie_texto') }}" placeholder="{{ \App\Support\Sitio::nombre() }}" />
                            </div>
                            <div class="col-12">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="meta_descripcion">Descripción para Google</label>
                                <input class="form-control" id="meta_descripcion" name="meta_descripcion" maxlength="300" value="{{ $v('meta_descripcion') }}" />
                                <div class="form-text">La que aparece en los resultados de búsqueda y al compartir el enlace en WhatsApp o redes.</div>
                            </div>
                            <div class="col-12">
                                <p class="fs--1 text-700 mb-0">Las oficinas y el mapa se editan en <a href="{{ route('admin.direcciones.index') }}">Direcciones</a>; los textos de cada página, en <a href="{{ route('admin.paginas.index') }}">Páginas y menú</a>.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Logo e íconos --}}
                    <div class="tab-pane fade {{ $activa === 'imagenes' ? 'show active' : '' }}" id="panel-imagenes" role="tabpanel">
                        <h4 class="mb-3 d-sm-none">Logo e íconos</h4>
                        <div class="row g-3">
                            @foreach (\App\Models\ConfiguracionSitio::IMAGENES as $campo => [$tituloImg, $ayuda])
                                @include('admin.partes.imagen-tarjeta', [
                                    'campo' => $campo, 'titulo' => $tituloImg, 'ayuda' => $ayuda, 'url' => $cfg->url($campo),
                                    'oscuro' => $campo === 'logo_oscuro', 'aceptar' => $campo === 'favicon' ? 'image/png,image/x-icon,image/jpeg,image/webp' : null,
                                ])
                            @endforeach
                        </div>
                        <p class="fs--1 text-600 mt-3 mb-0">Se guardan en Contabo. El ícono de la pestaña puede tardar en cambiar por la caché del navegador (recarga con Ctrl + F5).</p>
                    </div>

                    {{-- Fotos de inicio --}}
                    <div class="tab-pane fade {{ $activa === 'inicio' ? 'show active' : '' }}" id="panel-inicio" role="tabpanel">
                        <h4 class="mb-1">Fotos del carrusel de inicio</h4>
                        <p class="text-700 fs--1 mb-3">Las fotos grandes que pasan solas arriba de la página de inicio. Horizontales, 1920 × 800 px aprox. Puedes elegir varias a la vez.</p>
                        @error('fotos_inicio')<div class="alert alert-soft-danger py-2 fs--1">{{ $message }}</div>@enderror
                        <div class="row g-3 mb-3">
                            @forelse ($carrusel?->elementos ?? [] as $foto)
                                <div class="col-6 col-md-4 col-xl-3">
                                    <div class="border border-300 rounded-3 p-2 h-100">
                                        <img class="rounded-2 w-100 mb-2" src="{{ $foto->url() }}" alt="" style="aspect-ratio: 16/9; object-fit: cover;" />
                                        <p class="fs--2 text-700 mb-1 text-truncate">{{ $foto->titulo ?: 'Sin título' }}</p>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" id="quitar_foto_{{ $foto->id }}" name="quitar_fotos[]" type="checkbox" value="{{ $foto->id }}" />
                                            <label class="form-check-label fs--1 text-danger" for="quitar_foto_{{ $foto->id }}">Quitar</label>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12"><p class="text-600 fs--1 mb-0"><span class="fa-regular fa-images me-2"></span>Todavía no hay fotos: el inicio empieza directo con la portada.</p></div>
                            @endforelse
                        </div>
                        <div class="border border-dashed border-primary rounded-3 p-3 mb-3">
                            <label class="form-label" for="fotos_inicio"><span class="fa-solid fa-plus text-primary me-2"></span>Agregar fotos</label>
                            <input class="form-control" id="fotos_inicio" name="fotos_inicio[]" type="file" accept="image/png,image/jpeg,image/webp" multiple />
                            <div class="d-flex flex-wrap gap-2 mt-2" id="previaFotosInicio"></div>
                        </div>
                        @if ($carrusel)
                            <p class="fs--1 text-700">Para ponerle título, texto y botón a cada foto, o cambiar el alto del carrusel:
                                <a href="{{ route('admin.secciones.edit', $carrusel) }}#elementos">editar el carrusel</a>.</p>
                        @endif

                        @if ($portada)
                            <hr class="my-4" />
                            <h4 class="mb-1">Imagen de la portada</h4>
                            <p class="text-700 fs--1 mb-3">La imagen al lado del título principal del inicio (por ejemplo, una captura de tu sistema). Si no pones una, se usa la de la plantilla.</p>
                            <div class="row g-3">
                                @include('admin.partes.imagen-tarjeta', ['campo' => 'portada_imagen', 'titulo' => 'Modo claro', 'ayuda' => 'Horizontal, 1600 px aprox.', 'url' => $portada->url('imagen'), 'oscuro' => false, 'aceptar' => null])
                                @include('admin.partes.imagen-tarjeta', ['campo' => 'portada_imagen_oscura', 'titulo' => 'Modo oscuro', 'ayuda' => 'Opcional: la misma captura en modo oscuro.', 'url' => $portada->url('imagen_oscura'), 'oscuro' => true, 'aceptar' => null])
                            </div>
                            <p class="fs--1 text-700 mt-3 mb-0">Los textos y botones de la portada se editan en <a href="{{ route('admin.secciones.edit', $portada) }}">editar la portada</a>.</p>
                        @endif
                    </div>

                    {{-- Colores --}}
                    <div class="tab-pane fade {{ $activa === 'colores' ? 'show active' : '' }}" id="panel-colores" role="tabpanel">
                        <h4 class="mb-3 d-sm-none">Colores</h4>
                        <div class="row g-4">
                            @foreach (['color_primario' => ['Color principal', 'Botones, enlaces, menú activo e interruptores.', '#3874ff'], 'color_secundario' => ['Color secundario', 'Degradados de títulos resaltados y recuadros destacados.', '#38abff']] as $campo => [$tituloColor, $ayuda, $defecto])
                                <div class="col-md-6">
                                    <label class="form-label text-1000 fs-0 ps-0 text-none" for="{{ $campo }}">{{ $tituloColor }}</label>
                                    <div class="input-group">
                                        <input class="form-control form-control-color flex-grow-0" style="width: 3.5rem;" type="color" id="{{ $campo }}_picker" value="{{ $v($campo) ?: $defecto }}" data-destino="{{ $campo }}" aria-label="{{ $tituloColor }}" />
                                        <input class="form-control font-monospace @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}" maxlength="7" value="{{ $v($campo) }}" placeholder="{{ $defecto }}" />
                                    </div>
                                    <div class="form-text">{{ $ayuda }} Vacío = el de la plantilla.</div>
                                </div>
                            @endforeach
                            <div class="col-12">
                                <p class="text-700 fs--1 mb-2">Sugeridos (color principal):</p>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach (['#3874ff' => 'Azul (Phoenix)', '#0f4c81' => 'Azul marino', '#e63757' => 'Rojo', '#f97316' => 'Naranja', '#16a34a' => 'Verde', '#7c3aed' => 'Morado', '#0f766e' => 'Turquesa', '#be185d' => 'Rosa'] as $hex => $nombreColor)
                                        <button class="btn btn-sm border border-300 d-flex align-items-center gap-2 color-sugerido" type="button" data-color="{{ $hex }}">
                                            <span class="rounded-circle d-inline-block" style="width: 1rem; height: 1rem; background: {{ $hex }};"></span>{{ $nombreColor }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="text-700 fs--1 mb-2">Vista previa:</p>
                                <div class="d-flex flex-wrap gap-3 align-items-center border border-300 rounded-3 p-3" id="vistaColores">
                                    <span class="btn btn-sm text-white previa-fondo">Botón principal</span>
                                    <span class="fw-bold previa-texto">Enlace</span>
                                    <span class="badge rounded-pill text-white previa-fondo">Etiqueta</span>
                                    <span class="fs-2 fw-black previa-degradado">Sistemas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Redes sociales --}}
                    <div class="tab-pane fade {{ $activa === 'redes' ? 'show active' : '' }}" id="panel-redes" role="tabpanel">
                        <h4 class="mb-1">Redes sociales</h4>
                        <p class="text-700 fs--1 mb-3">Aparecen en el pie de página y en Contáctenos. Solo se muestran las que tengan enlace.</p>
                        <div class="row g-2">
                            @foreach ($redes as $red => $nombreRed)
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width: 2.75rem"><span class="fa-brands fa-{{ $red === 'linkedin' ? 'linkedin-in' : $red }}"></span></span>
                                        <input class="form-control @error($red) is-invalid @enderror" name="{{ $red }}" type="url" value="{{ $v($red) }}" placeholder="https://… ({{ $nombreRed }})" maxlength="255" aria-label="{{ $nombreRed }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Chat en vivo --}}
                    <div class="tab-pane fade {{ $activa === 'chat' ? 'show active' : '' }}" id="panel-chat" role="tabpanel">
                        <div class="d-flex flex-between-center flex-wrap gap-2 mb-3">
                            <h4 class="mb-0">Chat en vivo</h4>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" id="chat_activo" name="chat_activo" type="checkbox" value="1" @checked(old('chat_activo', $cfg->chat_activo ?? true)) />
                                <label class="form-check-label fw-semi-bold" for="chat_activo">Mostrar el chat en el sitio</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="chat_titulo">Título de la ventana</label>
                                <input class="form-control" id="chat_titulo" name="chat_titulo" value="{{ $v('chat_titulo') }}" maxlength="80" placeholder="Chatea con nosotros" />
                            </div>
                            <div class="col-md-7">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="chat_bienvenida">Mensaje de bienvenida</label>
                                <input class="form-control" id="chat_bienvenida" name="chat_bienvenida" value="{{ $v('chat_bienvenida') }}" maxlength="300" placeholder="Escríbenos y te respondemos aquí mismo…" />
                            </div>
                        </div>
                        <p class="form-text mb-0 mt-3">La foto del chat es el ícono de la pestaña (Logo e íconos). Las conversaciones se responden en <a href="{{ route('admin.chat.index') }}">Chat en vivo</a>.</p>
                    </div>

                    {{-- Inicio de sesión --}}
                    <div class="tab-pane fade {{ $activa === 'acceso' ? 'show active' : '' }}" id="panel-acceso" role="tabpanel">
                        <h4 class="mb-3 d-sm-none">Inicio de sesión</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="login_titulo">Título</label>
                                <input class="form-control" id="login_titulo" name="login_titulo" maxlength="100" value="{{ $v('login_titulo') }}" placeholder="Panel de administración" />
                            </div>
                            <div class="col-12">
                                <label class="form-label text-1000 fs-0 ps-0 text-none" for="login_subtitulo">Subtítulo</label>
                                <input class="form-control" id="login_subtitulo" name="login_subtitulo" maxlength="200" value="{{ $v('login_subtitulo') }}" placeholder="Ingresa tus credenciales para continuar" />
                            </div>
                            <div class="col-12">
                                <p class="fs--1 text-700 mb-0">El fondo del inicio de sesión se cambia en <a href="#" class="ir-pestana" data-pestana="imagenes">Logo e íconos</a>.
                                    <a href="{{ route('login') }}" target="_blank" rel="noopener">Ver el inicio de sesión</a> (en una ventana privada).</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            // Recordar la pestaña para volver a ella después de guardar.
            document.querySelectorAll('[data-bs-toggle="tab"][data-pestana]').forEach(function (t) {
                t.addEventListener('shown.bs.tab', function () { document.getElementById('pestanaActiva').value = t.dataset.pestana; });
            });
            document.querySelectorAll('.ir-pestana').forEach(function (a) {
                a.addEventListener('click', function (e) { e.preventDefault(); bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-' + a.dataset.pestana)).show(); });
            });
            // Vista previa de la imagen elegida antes de guardar.
            document.querySelectorAll('.archivo-imagen').forEach(function (input) {
                input.addEventListener('change', function () {
                    var img = document.querySelector('.img-previa[data-campo="' + input.dataset.campo + '"]');
                    var icono = document.querySelector('.sin-imagen[data-campo="' + input.dataset.campo + '"]');
                    if (!input.files[0] || !img) return;
                    img.src = URL.createObjectURL(input.files[0]);
                    img.classList.remove('d-none');
                    if (icono) icono.remove();
                });
            });
            // Fotos de inicio: miniaturas de las elegidas.
            var fotos = document.getElementById('fotos_inicio');
            fotos.addEventListener('change', function () {
                var cont = document.getElementById('previaFotosInicio');
                cont.innerHTML = '';
                Array.prototype.forEach.call(fotos.files, function (f) {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(f);
                    img.className = 'rounded-2 border border-300';
                    img.style.cssText = 'width: 120px; height: 68px; object-fit: cover;';
                    cont.appendChild(img);
                });
            });
            // Colores: selector, campo de texto, sugeridos y vista previa sincronizados.
            function valor(id, defecto) { var c = document.getElementById(id).value; return /^#[0-9a-f]{6}$/i.test(c) ? c : defecto; }
            function pintar() {
                var p = valor('color_primario', '#3874ff'), s = valor('color_secundario', '#38abff');
                document.querySelectorAll('#vistaColores .previa-fondo').forEach(function (e) { e.style.background = p; });
                document.querySelectorAll('#vistaColores .previa-texto').forEach(function (e) { e.style.color = p; });
                document.querySelectorAll('#vistaColores .previa-degradado').forEach(function (e) {
                    e.style.background = 'linear-gradient(90deg, ' + s + ', ' + p + ')';
                    e.style.webkitBackgroundClip = 'text'; e.style.backgroundClip = 'text'; e.style.color = 'transparent';
                });
            }
            document.querySelectorAll('input[type=color][data-destino]').forEach(function (p) {
                var texto = document.getElementById(p.dataset.destino);
                p.addEventListener('input', function () { texto.value = p.value; pintar(); });
                texto.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(texto.value)) { p.value = texto.value; } pintar(); });
            });
            document.querySelectorAll('.color-sugerido').forEach(function (b) {
                b.addEventListener('click', function () {
                    document.getElementById('color_primario').value = b.dataset.color;
                    document.getElementById('color_primario_picker').value = b.dataset.color;
                    pintar();
                });
            });
            pintar();
        })();
    </script>
@endpush
