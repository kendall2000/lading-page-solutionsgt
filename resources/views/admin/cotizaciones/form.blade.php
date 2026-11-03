@extends('layouts.admin', ['titulo' => ($cotizacion->exists ? 'Editar cotización ' . $cotizacion->numero : 'Nueva cotización')])

@section('contenido')
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.cotizaciones.index') }}">Cotizaciones</a></li>
                <li class="breadcrumb-item active">{{ $cotizacion->exists ? 'Editar ' . $cotizacion->numero : 'Nueva' }}</li>
            </ol>
        </nav>
        <h2 class="mb-0 text-1100">{{ $cotizacion->exists ? 'Editar Cotización ' . $cotizacion->numero : 'Crear Cotización' }}</h2>
        <p class="text-700 mb-0">Configura las secciones, tablas de precios y condiciones de la propuesta.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <p class="fw-bold mb-1">Por favor corrige los siguientes errores:</p>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $cotizacion->exists ? route('admin.cotizaciones.update', $cotizacion) : route('admin.cotizaciones.store') }}">
        @csrf
        @if ($cotizacion->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <!-- 1. Datos Generales -->
                <div class="card mb-4">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h5 class="mb-0">1. Encabezado y Datos de la Cotización</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="numero">Número correlativo</label>
                                <input class="form-control" id="numero" name="numero" type="text" value="{{ old('numero', $cotizacion->numero) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="fecha">Fecha</label>
                                <input class="form-control" id="fecha" name="fecha" type="date" value="{{ old('fecha', $cotizacion->fecha ? $cotizacion->fecha->format('Y-m-d') : date('Y-m-d')) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="vigencia_dias">Vigencia (días)</label>
                                <input class="form-control" id="vigencia_dias" name="vigencia_dias" type="number" min="1" max="365" value="{{ old('vigencia_dias', $cotizacion->vigencia_dias ?? 30) }}" required />
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="titulo">Título de la cotización</label>
                                <input class="form-control" id="titulo" name="titulo" type="text" value="{{ old('titulo', $cotizacion->titulo) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="moneda">Moneda</label>
                                <select class="form-select" id="moneda" name="moneda">
                                    <option value="GTQ" {{ old('moneda', $cotizacion->moneda) === 'GTQ' ? 'selected' : '' }}>Quetzales (GTQ)</option>
                                    <option value="USD" {{ old('moneda', $cotizacion->moneda) === 'USD' ? 'selected' : '' }}>Dólares (USD)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subtitulo">Subtítulo / Sistema al que aplica</label>
                                <input class="form-control" id="subtitulo" name="subtitulo" type="text" value="{{ old('subtitulo', $cotizacion->subtitulo) }}" placeholder="Ej: Sistema web Lunea Boutique: tienda en línea y panel administrativo" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Cliente y Proveedor -->
                <div class="card mb-4">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h5 class="mb-0">2. Partes (Cliente y Proveedor)</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold text-primary mb-3">Datos del Cliente</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="cliente_id">Vincular Cliente registrado (opcional)</label>
                                <select class="form-select" id="cliente_id" name="cliente_id">
                                    <option value="">Selecciona o escribe los datos abajo...</option>
                                    @foreach ($clientes as $cl)
                                        <option value="{{ $cl->id }}" {{ old('cliente_id', $cotizacion->cliente_id) == $cl->id ? 'selected' : '' }}>
                                            {{ $cl->empresa }} {{ $cl->contacto ? "({$cl->contacto})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sistema_id">Sistema referenciado (opcional)</label>
                                <select class="form-select" id="sistema_id" name="sistema_id">
                                    <option value="">Ninguno seleccionado</option>
                                    @foreach ($sistemas as $sis)
                                        <option value="{{ $sis->id }}" {{ old('sistema_id', $cotizacion->sistema_id) == $sis->id ? 'selected' : '' }}>{{ $sis->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cliente_empresa">Empresa del Cliente</label>
                                <input class="form-control" id="cliente_empresa" name="cliente_empresa" type="text" value="{{ old('cliente_empresa', $cotizacion->cliente_empresa) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cliente_atencion">Atención a</label>
                                <input class="form-control" id="cliente_atencion" name="cliente_atencion" type="text" value="{{ old('cliente_atencion', $cotizacion->cliente_atencion) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cliente_correo">Correo del cliente</label>
                                <input class="form-control" id="cliente_correo" name="cliente_correo" type="email" value="{{ old('cliente_correo', $cotizacion->cliente_correo) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cliente_telefono">Teléfono del cliente</label>
                                <input class="form-control" id="cliente_telefono" name="cliente_telefono" type="text" value="{{ old('cliente_telefono', $cotizacion->cliente_telefono) }}" />
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary mb-3">Datos del Proveedor</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="proveedor_nombre">Nombre Proveedor</label>
                                <input class="form-control" id="proveedor_nombre" name="proveedor_nombre" type="text" value="{{ old('proveedor_nombre', $cotizacion->proveedor_nombre ?? 'Kendall Moreno') }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="proveedor_cargo">Cargo / Descripción</label>
                                <input class="form-control" id="proveedor_cargo" name="proveedor_cargo" type="text" value="{{ old('proveedor_cargo', $cotizacion->proveedor_cargo ?? 'Desarrollo y soporte de software') }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="proveedor_correo">Correo Proveedor</label>
                                <input class="form-control" id="proveedor_correo" name="proveedor_correo" type="email" value="{{ old('proveedor_correo', $cotizacion->proveedor_correo) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="proveedor_telefono">Teléfono Proveedor</label>
                                <input class="form-control" id="proveedor_telefono" name="proveedor_telefono" type="text" value="{{ old('proveedor_telefono', $cotizacion->proveedor_telefono) }}" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Objeto de la Cotización -->
                <div class="card mb-4">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h5 class="mb-0">3. Objeto de la Cotización</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="objeto_descripcion">Descripción introductoria</label>
                            <textarea class="form-control" id="objeto_descripcion" name="objeto_descripcion" rows="3">{{ old('objeto_descripcion', $cotizacion->objeto_descripcion) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="objeto_puntos_texto">Puntos o módulos cubiertos (uno por línea)</label>
                            <textarea class="form-control" id="objeto_puntos_texto" name="objeto_puntos_texto" rows="5">{{ old('objeto_puntos_texto', is_array($cotizacion->objeto_puntos) ? implode("\n", $cotizacion->objeto_puntos) : '') }}</textarea>
                            <div class="form-text">Escribe cada viñeta en una línea separada.</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="objeto_nota">Nota al pie del objeto</label>
                            <textarea class="form-control" id="objeto_nota" name="objeto_nota" rows="2">{{ old('objeto_nota', $cotizacion->objeto_nota) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 4. Tabla de Planes de Servicio (Constructor Visual) -->
                <div class="card mb-4" id="seccion-planes-servicio">
                    <div class="card-header border-bottom bg-body-emphasis py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">4. Tabla de Planes y Precios (Constructor Visual)</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-caracteristica">
                            <span class="fa-solid fa-plus me-1"></span>Agregar fila de servicio
                        </button>
                    </div>
                    <div class="card-body">
                        @php
                            $columnas = $cotizacion->planes['columnas'] ?? [
                                ['id' => 'basico', 'nombre' => 'Básico', 'precio' => '300', 'frecuencia' => '/ mes'],
                                ['id' => 'mantenimiento', 'nombre' => 'Mantenimiento', 'precio' => '600', 'frecuencia' => '/ mes'],
                                ['id' => 'plus', 'nombre' => 'Mantenimiento Plus', 'precio' => '750', 'frecuencia' => '/ mes'],
                            ];
                            $filas = $cotizacion->planes['filas'] ?? [];
                        @endphp

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle" id="tabla-constructor-planes">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 260px;">Servicio / Característica</th>
                                        @foreach($columnas as $cIdx => $col)
                                            <th class="text-center" style="min-width: 170px;">
                                                <div class="mb-1">
                                                    <input type="text" name="plan_nombres[]" class="form-control form-control-sm text-center fw-bold" value="{{ $col['nombre'] }}" placeholder="Nombre Plan" required>
                                                </div>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text">Q/$</span>
                                                    <input type="text" name="plan_precios[]" class="form-control text-center fw-bold" value="{{ $col['precio'] }}" placeholder="300" required>
                                                </div>
                                                <div>
                                                    <input type="text" name="plan_frecuencias[]" class="form-control form-control-sm text-center text-muted" value="{{ $col['frecuencia'] ?? '/ mes' }}" placeholder="/ mes">
                                                </div>
                                            </th>
                                        @endforeach
                                        <th style="width: 45px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-filas-planes">
                                    @forelse($filas as $fIdx => $fila)
                                        <tr class="fila-plan">
                                            <td>
                                                <input type="text" name="fila_caracteristicas[]" class="form-control form-control-sm" value="{{ $fila['caracteristica'] }}" placeholder="Ej: Respaldos diarios..." required>
                                            </td>
                                            @foreach($columnas as $cIdx => $col)
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="fila_valores[{{ $fIdx }}][{{ $cIdx }}]" class="form-control form-control-sm text-center valor-input" value="{{ $fila['valores'][$cIdx] ?? '—' }}">
                                                        <button type="button" class="btn btn-outline-secondary px-2 btn-toggle-check" title="Cambiar entre ✔ y —">✔</button>
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-eliminar-fila" title="Eliminar fila">
                                                    <span class="fa-solid fa-trash-can"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="fila-plan">
                                            <td>
                                                <input type="text" name="fila_caracteristicas[]" class="form-control form-control-sm" placeholder="Ej: Despliegue en servidor" required>
                                            </td>
                                            @foreach($columnas as $cIdx => $col)
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="fila_valores[0][{{ $cIdx }}]" class="form-control form-control-sm text-center valor-input" value="✔">
                                                        <button type="button" class="btn btn-outline-secondary px-2 btn-toggle-check">✔</button>
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-eliminar-fila">
                                                    <span class="fa-solid fa-trash-can"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="planes_nota">Texto explicativo "¿Cuál elegir?"</label>
                            <textarea class="form-control" id="planes_nota" name="planes_nota" rows="2">{{ old('planes_nota', $cotizacion->planes_nota) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 5. Mantenimiento vs Desarrollo Nuevo -->
                <div class="card mb-4">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h5 class="mb-0">5. Mantenimiento vs Desarrollo Nuevo</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="mantenimiento_incluye_texto">Mantenimiento (incluido en el plan)</label>
                                <textarea class="form-control" id="mantenimiento_incluye_texto" name="mantenimiento_incluye_texto" rows="6">{{ old('mantenimiento_incluye_texto', is_array($cotizacion->mantenimiento_incluye) ? implode("\n", $cotizacion->mantenimiento_incluye) : '') }}</textarea>
                                <div class="form-text">Una acción por línea.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="desarrollo_nuevo_incluye_texto">Desarrollo nuevo (se cotiza aparte)</label>
                                <textarea class="form-control" id="desarrollo_nuevo_incluye_texto" name="desarrollo_nuevo_incluye_texto" rows="6">{{ old('desarrollo_nuevo_incluye_texto', is_array($cotizacion->desarrollo_nuevo_incluye) ? implode("\n", $cotizacion->desarrollo_nuevo_incluye) : '') }}</textarea>
                                <div class="form-text">Una acción por línea.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="seccion_modulos_nuevos">Párrafo de Módulos nuevos y mejoras</label>
                                <textarea class="form-control" id="seccion_modulos_nuevos" name="seccion_modulos_nuevos" rows="3">{{ old('seccion_modulos_nuevos', $cotizacion->seccion_modulos_nuevos) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Condiciones y Aceptación -->
                <div class="card mb-4">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h5 class="mb-0">6. Condiciones y Aceptación</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="condiciones_texto">Condiciones comerciales (una por línea)</label>
                            <textarea class="form-control" id="condiciones_texto" name="condiciones_texto" rows="6">{{ old('condiciones_texto', is_array($cotizacion->condiciones) ? implode("\n", $cotizacion->condiciones) : '') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="opciones_aceptacion_texto">Opciones de aceptación (casillas de verificación, una por línea)</label>
                            <textarea class="form-control" id="opciones_aceptacion_texto" name="opciones_aceptacion_texto" rows="3">{{ old('opciones_aceptacion_texto', is_array($cotizacion->opciones_aceptacion) ? implode("\n", $cotizacion->opciones_aceptacion) : '') }}</textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="firma_cliente_nombre">Firma Cliente (nombre en pie de página)</label>
                                <input class="form-control" id="firma_cliente_nombre" name="firma_cliente_nombre" type="text" value="{{ old('firma_cliente_nombre', $cotizacion->firma_cliente_nombre) }}" placeholder="Ej: Lunea Boutique S.A." />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="firma_proveedor_nombre">Firma Proveedor</label>
                                <input class="form-control" id="firma_proveedor_nombre" name="firma_proveedor_nombre" type="text" value="{{ old('firma_proveedor_nombre', $cotizacion->firma_proveedor_nombre ?? 'Kendall Moreno') }}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna lateral: Controles de guardado y estado -->
            <div class="col-12 col-xl-4">
                <div class="card mb-4 sticky-top" style="top: 20px; z-index: 10;">
                    <div class="card-header border-bottom bg-body-emphasis py-3">
                        <h5 class="mb-0">Guardar & Estado</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                @foreach (\App\Models\Cotizacion::ESTADOS as $clave => [$nombre, $color])
                                    <option value="{{ $clave }}" {{ old('estado', $cotizacion->estado) === $clave ? 'selected' : '' }}>{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="tipo">Tipo de formato</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="planes" {{ old('tipo', $cotizacion->tipo) === 'planes' ? 'selected' : '' }}>Tabla de Planes de Soporte</option>
                                <option value="items" {{ old('tipo', $cotizacion->tipo) === 'items' ? 'selected' : '' }}>Por Partidas / Módulos</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="notas_internas">Notas internas (no visibles en PDF)</label>
                            <textarea class="form-control" id="notas_internas" name="notas_internas" rows="4">{{ old('notas_internas', $cotizacion->notas_internas) }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" type="submit">
                                <span class="fa-solid fa-floppy-disk me-2"></span>{{ $cotizacion->exists ? 'Guardar Cambios' : 'Crear Cotización' }}
                            </button>
                            <a class="btn btn-phoenix-secondary" href="{{ route('admin.cotizaciones.index') }}">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('tbody-filas-planes');
        const btnAgregar = document.getElementById('btn-agregar-caracteristica');

        function reindexarFilas() {
            const filas = tbody.querySelectorAll('.fila-plan');
            filas.forEach((fila, fIdx) => {
                const inputs = fila.querySelectorAll('.valor-input');
                inputs.forEach((input, cIdx) => {
                    input.name = `fila_valores[${fIdx}][${cIdx}]`;
                });
            });
        }

        // Toggle rápido con el botón ✔ / —
        tbody.addEventListener('click', function (e) {
            const btnToggle = e.target.closest('.btn-toggle-check');
            if (btnToggle) {
                const input = btnToggle.closest('.input-group').querySelector('.valor-input');
                if (input.value === '✔') {
                    input.value = '—';
                } else if (input.value === '—') {
                    input.value = '✔';
                } else {
                    input.value = '✔';
                }
            }

            const btnEliminar = e.target.closest('.btn-eliminar-fila');
            if (btnEliminar) {
                const fila = btnEliminar.closest('.fila-plan');
                if (tbody.querySelectorAll('.fila-plan').length > 1) {
                    fila.remove();
                    reindexarFilas();
                } else {
                    alert('Debe haber al menos una fila de servicio.');
                }
            }
        });

        if (btnAgregar) {
            btnAgregar.addEventListener('click', function () {
                const totalColumnas = document.querySelectorAll('input[name="plan_nombres[]"]').length;
                const fIdx = tbody.querySelectorAll('.fila-plan').length;
                let celdasValores = '';

                for (let c = 0; c < totalColumnas; c++) {
                    celdasValores += `
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" name="fila_valores[${fIdx}][${c}]" class="form-control form-control-sm text-center valor-input" value="✔">
                                <button type="button" class="btn btn-outline-secondary px-2 btn-toggle-check" title="Cambiar entre ✔ y —">✔</button>
                            </div>
                        </td>
                    `;
                }

                const tr = document.createElement('tr');
                tr.className = 'fila-plan';
                tr.innerHTML = `
                    <td>
                        <input type="text" name="fila_caracteristicas[]" class="form-control form-control-sm" placeholder="Nueva característica o servicio" required>
                    </td>
                    ${celdasValores}
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-eliminar-fila" title="Eliminar fila">
                            <span class="fa-solid fa-trash-can"></span>
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
                tr.querySelector('input[name="fila_caracteristicas[]"]').focus();
            });
        }
    });
    </script>
    @endpush
@endsection
