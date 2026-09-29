@extends('layouts.admin', ['titulo' => $plantilla->exists ? $plantilla->nombre : 'Nueva plantilla'])

{{-- Editor de plantilla (add-product.html: columna principal + tarjeta lateral). Las variables se escriben {{ asi }}. --}}
@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.correos.index', ['ver' => 'plantillas']) }}">Correos</a></li>
            <li class="breadcrumb-item active">{{ $plantilla->exists ? $plantilla->nombre : 'Nueva plantilla' }}</li>
        </ol>
    </nav>
    <form class="mb-5" method="POST" action="{{ $plantilla->exists ? route('admin.correos.plantillas.update', $plantilla) : route('admin.correos.plantillas.store') }}" id="formPlantilla">
        @csrf
        @if ($plantilla->exists)
            @method('PUT')
        @endif
        <div class="row g-3 flex-between-end mb-4">
            <div class="col-auto">
                <h2 class="mb-2">{{ $plantilla->exists ? 'Editar plantilla' : 'Nueva plantilla' }}</h2>
                <h5 class="text-700 fw-semi-bold">Usa <code>@{{ variable }}</code> donde vaya un dato; el marco (logo, colores y pie) lo pone el sistema.</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2" href="{{ route('admin.correos.index', ['ver' => 'plantillas']) }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Guardar</button>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-7">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" maxlength="120" required value="{{ old('nombre', $plantilla->nombre) }}" />
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="codigo">Código</label>
                        <input class="form-control font-monospace" id="codigo" name="codigo" maxlength="60" value="{{ old('codigo', $plantilla->codigo) }}" @disabled($plantilla->del_sistema) placeholder="promocion_mensual" />
                        @if ($plantilla->del_sistema)<div class="form-text">La usa el sistema: el código no cambia.</div>@endif
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <input class="form-control" id="descripcion" name="descripcion" maxlength="255" value="{{ old('descripcion', $plantilla->descripcion) }}" />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="asunto">Asunto</label>
                        <input class="form-control" id="asunto" name="asunto" maxlength="200" required value="{{ old('asunto', $plantilla->asunto) }}" />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contenido">Contenido (HTML)</label>
                        <textarea class="form-control font-monospace fs--1" id="contenido" name="contenido" rows="14" required>{{ old('contenido', $plantilla->contenido) }}</textarea>
                    </div>
                </div>
                <h5 class="mb-2">Así se verá <span class="text-600 fw-normal fs--1">(los datos de ejemplo aparecen entre corchetes)</span></h5>
                <iframe class="w-100 border border-300 rounded-3 bg-white" id="previa" sandbox style="height: 26rem;" title="Vista previa"></iframe>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $plantilla->is_active)) />
                            <label class="form-check-label fw-semi-bold" for="is_active">Activa</label>
                        </div>
                        <h5 class="mb-2 text-1000">Variables</h5>
                        <p class="fs--1 text-700 mb-2">Toca una para insertarla. Siempre existen <code>@{{ sistema }}</code> y <code>@{{ color }}</code>.</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach (array_unique(array_merge($plantilla->variables ?? [], ['sistema', 'color'])) as $var)
                                <button class="btn btn-phoenix-secondary btn-sm font-monospace insertar" type="button" data-var="{{ $var }}">{{ $var }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if ($plantilla->exists)
        <div class="row g-3 mb-9">
            <div class="col-12 col-xl-8">
                <form class="card" method="POST" action="{{ route('admin.correos.plantillas.probar', $plantilla) }}">
                    @csrf
                    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
                        <div class="flex-1" style="min-width: 14rem;">
                            <label class="form-label" for="destinatario">Enviar una prueba (versión guardada) a</label>
                            <input class="form-control" id="destinatario" name="destinatario" type="email" required value="{{ auth()->user()->email }}" />
                        </div>
                        <button class="btn btn-phoenix-primary" type="submit"><span class="fas fa-paper-plane me-2"></span>Enviar prueba</button>
                        <a class="btn btn-phoenix-secondary" href="{{ route('admin.correos.plantillas.previa', $plantilla) }}" target="_blank" rel="noopener">Ver con el marco</a>
                    </div>
                </form>
            </div>
            @unless ($plantilla->del_sistema)
                <div class="col-12 col-xl-4">
                    <form class="card h-100" method="POST" action="{{ route('admin.correos.plantillas.destroy', $plantilla) }}" onsubmit="return confirm('¿Borrar la plantilla {{ $plantilla->nombre }}?')">
                        @csrf
                        @method('DELETE')
                        <div class="card-body d-flex align-items-end"><button class="btn btn-phoenix-danger w-100" type="submit"><span class="fas fa-trash-alt me-2"></span>Borrar plantilla</button></div>
                    </form>
                </div>
            @endunless
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var contenido = document.getElementById('contenido');
            var previa = document.getElementById('previa');
            var color = @json(\App\Support\Sitio::config()->color_primario ?: '#3874ff');
            function pintar() {
                var html = contenido.value.replace(/\{\{\s*(\w+)\s*\}\}/g, function (_, v) { return v === 'color' ? color : '[' + v + ']'; });
                // Sandbox sin scripts: solo muestra el diseño.
                previa.srcdoc = '<body style="font-family:Arial,sans-serif;padding:24px;line-height:1.6;color:#31374a;">' + html + '</body>';
            }
            contenido.addEventListener('input', pintar);
            document.querySelectorAll('.insertar').forEach(function (b) {
                b.addEventListener('click', function () {
                    var t = '{' + '{ ' + b.dataset.var + ' }' + '}', i = contenido.selectionStart || contenido.value.length;
                    contenido.value = contenido.value.slice(0, i) + t + contenido.value.slice(contenido.selectionEnd || i);
                    contenido.focus();
                    pintar();
                });
            });
            pintar();
        })();
    </script>
@endpush
