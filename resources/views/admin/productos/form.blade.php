@extends('layouts.admin', ['titulo' => $producto->exists ? $producto->nombre : 'Nuevo producto'])

@use('App\Models\Precio')
@use('App\Models\Producto')

@php
    $v = fn ($c) => old($c, $producto->{$c});
    $ayudas = [
        'unico' => 'Se cobra una sola vez (servicios, instalación…). No disponible para software.',
        'semanal' => 'PayPal cobra solo cada semana.',
        'mensual' => 'PayPal cobra solo cada mes.',
        'trimestral' => 'PayPal cobra solo cada 3 meses.',
        'anual' => 'PayPal cobra solo cada año.',
        'de_por_vida' => 'No se cobra en línea: el botón lleva a hablar con un asesor. El monto es opcional («Desde…»).',
    ];
@endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.productos.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Productos y precios</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $producto->exists ? $producto->nombre : 'Nuevo producto' }}</h2>

    <form method="POST" action="{{ $producto->exists ? route('admin.productos.update', $producto) : route('admin.productos.store') }}">
        @csrf
        @if ($producto->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-3">Producto</h4>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="nombre">Nombre *</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="120" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="tipo">Tipo *</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    @foreach (Producto::TIPOS as $clave => [$texto])
                                        <option value="{{ $clave }}" @selected($v('tipo') === $clave)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12" id="bloqueSistema">
                                <label class="form-label" for="sistema_id">Sistema al que da acceso *</label>
                                <select class="form-select" id="sistema_id" name="sistema_id">
                                    <option value="">Elige…</option>
                                    @foreach ($sistemas as $id => $nombre)
                                        <option value="{{ $id }}" @selected((string) $v('sistema_id') === (string) $id)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Al pagar, el cliente queda con ese sistema en «Mi cuenta» hasta su próxima fecha de cobro (+ los días de gracia de PayPal). Si deja de pagar, vence solo.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="descripcion">Descripción corta</label>
                                <input class="form-control" id="descripcion" name="descripcion" value="{{ $v('descripcion') }}" maxlength="300" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="incluye">Qué incluye</label>
                                <textarea class="form-control" id="incluye" name="incluye" rows="6" maxlength="3000" placeholder="Uno por línea">{{ $v('incluye') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="slug">Dirección (para referencia)</label>
                                <input class="form-control" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="140" placeholder="se arma con el nombre" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="orden">Orden</label>
                                <input class="form-control" id="orden" name="orden" type="number" min="0" value="{{ $v('orden') }}" />
                            </div>
                            <div class="col-md-4 d-flex flex-column justify-content-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $producto->activo)) />
                                    <label class="form-check-label" for="activo">En venta</label>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" id="destacado" name="destacado" type="checkbox" value="1" @checked(old('destacado', $producto->destacado)) />
                                    <label class="form-check-label" for="destacado">Destacado</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-1">Precios</h4>
                        <p class="fs--1 text-700 mb-3">Activa los periodos que quieras ofrecer, en {{ $pagos->moneda }}. Cambiar un precio no afecta a quienes ya están suscritos: siguen pagando lo que aceptaron.</p>
                        @foreach (Precio::PERIODOS as $periodo => [$nombrePeriodo])
                            @php $precio = $precios[$periodo] ?? null; @endphp
                            <div class="border border-300 rounded-2 p-3 mb-2" data-periodo="{{ $periodo }}">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0 flex-1">
                                        <input class="form-check-input" id="precio_{{ $periodo }}" name="precios[{{ $periodo }}][activo]" type="checkbox" value="1"
                                               @checked(old("precios.{$periodo}.activo", $precio?->activo)) />
                                        <label class="form-check-label fw-semi-bold" for="precio_{{ $periodo }}">{{ $nombrePeriodo }}</label>
                                    </div>
                                    <div class="input-group input-group-sm" style="max-width: 150px">
                                        <span class="input-group-text">{{ $pagos->moneda }}</span>
                                        <input class="form-control text-end" name="precios[{{ $periodo }}][monto]" type="number" step="0.01" min="1" max="99999"
                                               value="{{ old("precios.{$periodo}.monto", $precio?->monto) }}" aria-label="Monto {{ $nombrePeriodo }}" />
                                    </div>
                                </div>
                                <div class="form-text mt-1">{{ $ayudas[$periodo] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <button class="btn btn-primary w-100 mt-3" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>{{ $producto->exists ? 'Guardar cambios' : 'Crear producto' }}</button>
            </div>
        </div>
    </form>

    @if ($producto->exists)
        <form class="mt-4" method="POST" action="{{ route('admin.productos.destroy', $producto) }}" onsubmit="return confirm('¿Eliminar este producto? El historial de pagos se conserva.')">
            @csrf
            @method('DELETE')
            <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar producto</button>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var tipo = document.getElementById('tipo');
            var bloque = document.getElementById('bloqueSistema');
            var unico = document.querySelector('[data-periodo="unico"]');
            function mostrar() {
                var esSistema = tipo.value === 'sistema';
                bloque.classList.toggle('d-none', !esSistema);
                unico.classList.toggle('opacity-50', esSistema);
            }
            tipo.addEventListener('change', mostrar);
            mostrar();
        })();
    </script>
@endpush
