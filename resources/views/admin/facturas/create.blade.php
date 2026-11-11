@extends('layouts.admin', ['titulo' => 'Emitir factura'])

@section('contenido')
    {{-- Header de la Página con Estilo Premium Phoenix --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.facturas.index') }}">Facturación</a></li>
                    <li class="breadcrumb-item active">Nueva Factura</li>
                </ol>
            </nav>
            <h2 class="mb-1 text-1100">Emitir Factura / Comprobante</h2>
            <p class="text-700 mb-0">Facturación ágil con catálogo de software, búsqueda de NIT en tiempo real y soporte Pequeño Contribuyente.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.facturas.index') }}" class="btn btn-phoenix-secondary">
                <span class="fa-solid fa-arrow-left me-1"></span>Volver
            </a>
            <button type="submit" form="formFactura" class="btn btn-primary px-4 shadow-sm">
                <span class="fa-solid fa-receipt me-2"></span>Emitir Factura
            </button>
        </div>
    </div>

    @if ($cotizacionOrigen)
        <div class="alert alert-subtle-info d-flex align-items-center justify-content-between mb-4 border-info">
            <div class="d-flex align-items-center">
                <span class="fa-solid fa-file-invoice text-info fs-2 me-3"></span>
                <div>
                    <h6 class="mb-0 text-info fw-bold">Facturando desde Cotización {{ $cotizacionOrigen->numero }}</h6>
                    <span class="fs--1 text-800">Se han precargado los datos del cliente y los conceptos de la cotización.</span>
                </div>
            </div>
            <span class="badge bg-info">{{ $cotizacionOrigen->cliente_empresa }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-subtle-danger alert-dismissible fade show mb-4 border-danger">
            <h6 class="text-danger fw-bold mb-1"><span class="fa-solid fa-triangle-exclamation me-1"></span> Revise los campos:</h6>
            <ul class="mb-0 ps-3 fs--1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.facturas.store') }}" id="formFactura">
        @csrf
        <div class="row g-4">
            
            {{-- Columna Izquierda: Cliente, Selección Rápida y Líneas --}}
            <div class="col-12 col-xl-8">
                
                {{-- Tarjeta 1: Datos del Receptor con Búsqueda de NIT SAT --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-body-highlight py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fa-solid fa-user-check text-primary fs-0"></span>
                            <h5 class="mb-0 text-1100">Cliente / Receptor</h5>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-primary fs--2">Búsqueda SAT en tiempo real</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-900" for="nit_receptor">NIT o DPI / CUI</label>
                                <div class="input-group">
                                    <input class="form-control font-monospace" id="nit_receptor" name="nit_receptor" type="text" placeholder="Ej: 12345678 o CF" value="{{ old('nit_receptor', $cotizacionOrigen ? ($cotizacionOrigen->cliente_nit ?? 'CF') : 'CF') }}" required />
                                    <button class="btn btn-phoenix-primary" type="button" id="btnBuscarNit" onclick="consultarNitSat()">
                                        <span id="spinnerNit" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                        <span id="iconoNit" class="fa-solid fa-magnifying-glass me-1"></span>Buscar
                                    </button>
                                </div>
                                <div id="feedbackNit" class="fs--2 mt-1"></div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-900" for="nombre_receptor">Nombre o Razón Social</label>
                                <input class="form-control" id="nombre_receptor" name="nombre_receptor" type="text" placeholder="Nombre completo o Empresa" value="{{ old('nombre_receptor', $cotizacionOrigen?->cliente_empresa) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-800" for="correo_receptor">Correo para envío de factura</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body"><span class="fa-solid fa-envelope text-600"></span></span>
                                    <input class="form-control" id="correo_receptor" name="correo_receptor" type="email" placeholder="cliente@correo.com" value="{{ old('correo_receptor', $cotizacionOrigen?->cliente_correo) }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-800" for="direccion_receptor">Dirección Comercial</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body"><span class="fa-solid fa-location-dot text-600"></span></span>
                                    <input class="form-control" id="direccion_receptor" name="direccion_receptor" type="text" placeholder="Ciudad o dirección fiscal" value="{{ old('direccion_receptor', $cotizacionOrigen?->cliente_direccion ?? 'Ciudad') }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tarjeta 2: Catálogo Rápido de Software y Servicios --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-body-highlight py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fa-solid fa-box text-primary fs-0"></span>
                            <h5 class="mb-0 text-1100">Seleccionar desde Software y Productos Existentes</h5>
                        </div>
                        <span class="text-600 fs--2">Clic para agregar a la factura</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12 mb-1">
                                <span class="fs--2 fw-bold text-uppercase text-600">Sistemas Disponibles:</span>
                            </div>
                            @forelse($sistemas as $sis)
                                <div class="col-auto">
                                    <button type="button" class="btn btn-sm btn-subtle-primary text-start d-flex align-items-center gap-2 py-2 px-3 rounded-pill" onclick="agregarSistemaAlDetalle('{{ addslashes($sis->nombre) }}', '{{ addslashes($sis->resumen ?? '') }}', {{ (float)($sis->precio ?? 0) }})">
                                        <span class="fa-solid fa-laptop-code"></span>
                                        <strong>{{ $sis->nombre }}</strong>
                                        <span class="badge bg-primary text-white ms-1">{{ $sis->precio > 0 ? '$'.number_format($sis->precio, 2) : 'A la medida' }}</span>
                                    </button>
                                </div>
                            @empty
                                <div class="col-12"><span class="text-600 fs--2">No hay software cargado.</span></div>
                            @endforelse

                            @if($productos->isNotEmpty())
                                <div class="col-12 mt-3 mb-1">
                                    <span class="fs--2 fw-bold text-uppercase text-600">Servicios y Planes:</span>
                                </div>
                                @foreach($productos as $prod)
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-subtle-secondary text-start d-flex align-items-center gap-2 py-2 px-3 rounded-pill" onclick="agregarProductoAlDetalle('{{ addslashes($prod->nombre) }}', '{{ addslashes($prod->descripcion ?? '') }}')">
                                            <span class="fa-solid fa-cube"></span>
                                            <span>{{ $prod->nombre }}</span>
                                        </button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tarjeta 3: Líneas de la Factura (Dinámicas y con B/S) --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-body-highlight py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fa-solid fa-table-list text-primary fs-0"></span>
                            <h5 class="mb-0 text-1100">Conceptos y Renglones</h5>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-none" onclick="agregarFila()">
                            <span class="fa-solid fa-plus me-1"></span>Agregar línea manual
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tablaItems">
                                <thead class="bg-body-secondary">
                                    <tr>
                                        <th style="width: 140px;" class="ps-3">Tipo SAT</th>
                                        <th>Descripción del Concepto (Texto Libre)</th>
                                        <th style="width: 100px;">Cant.</th>
                                        <th style="width: 130px;">Precio Unit.</th>
                                        <th style="width: 130px;" class="text-end">Total</th>
                                        <th style="width: 48px;" class="pe-3"></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoItems">
                                    @if($cotizacionOrigen && $cotizacionOrigen->items->isNotEmpty())
                                        @foreach($cotizacionOrigen->items as $idx => $it)
                                            <tr class="fila-item">
                                                <td class="ps-3">
                                                    <select class="form-select form-select-sm" name="items[{{ $idx }}][tipo]">
                                                        <option value="S" selected>Servicio (S)</option>
                                                        <option value="B">Bien (B)</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm" name="items[{{ $idx }}][descripcion]" rows="2" required>{{ $it->titulo }}{{ $it->descripcion ? " — " . $it->descripcion : '' }}</textarea>
                                                </td>
                                                <td>
                                                    <input class="form-control form-control-sm text-center cant" type="number" step="1" min="1" name="items[{{ $idx }}][cantidad]" value="{{ (int)($it->cantidad ?? 1) }}" oninput="recalcularTotales()" required />
                                                </td>
                                                <td>
                                                    <input class="form-control form-control-sm text-end precio" type="number" step="0.01" min="0" name="items[{{ $idx }}][precio_unitario]" value="{{ number_format((float)($it->precio_unitario ?? 0), 2, '.', '') }}" oninput="recalcularTotales()" required />
                                                </td>
                                                <td class="text-end fw-bold subt">0.00</td>
                                                <td class="text-center pe-3">
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="eliminarFila(this)">
                                                        <span class="fa-solid fa-trash-can"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="fila-item">
                                            <td class="ps-3">
                                                <select class="form-select form-select-sm" name="items[0][tipo]">
                                                    <option value="S" selected>Servicio (S)</option>
                                                    <option value="B">Bien (B)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" name="items[0][descripcion]" rows="2" placeholder="Describa el servicio, software, renta o desarrollo a la medida..." required></textarea>
                                            </td>
                                            <td>
                                                <input class="form-control form-control-sm text-center cant" type="number" step="1" min="1" name="items[0][cantidad]" value="1" oninput="recalcularTotales()" required />
                                            </td>
                                            <td>
                                                <input class="form-control form-control-sm text-end precio" type="number" step="0.01" min="0" name="items[0][precio_unitario]" value="0.00" oninput="recalcularTotales()" required />
                                            </td>
                                            <td class="text-end fw-bold subt">0.00</td>
                                            <td class="text-center pe-3">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="eliminarFila(this)">
                                                    <span class="fa-solid fa-trash-can"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Derecha: Resumen de Totales y Condiciones --}}
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header bg-body-highlight py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fa-solid fa-calculator text-primary fs-0"></span>
                            <h5 class="mb-0 text-1100">Totales y Condiciones</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-900" for="moneda">Moneda de Facturación</label>
                            <select class="form-select" id="moneda" name="moneda" onchange="recalcularTotales()">
                                <option value="USD" {{ old('moneda', $cotizacionOrigen?->moneda ?? 'USD') === 'USD' ? 'selected' : '' }}>USD ($) Dólares Estadounidenses</option>
                                <option value="GTQ" {{ old('moneda', $cotizacionOrigen?->moneda) === 'GTQ' ? 'selected' : '' }}>GTQ (Q) Quetzales de Guatemala</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-800" for="condicion_pago">Condición de Venta</label>
                            <select class="form-select" id="condicion_pago" name="condicion_pago">
                                <option value="contado" selected>Contado</option>
                                <option value="renta">Renta Mensual / SaaS</option>
                                <option value="suscripcion">Suscripción Anual</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-800" for="metodo_pago">Método de Cobro</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago">
                                <option value="transferencia" selected>Transferencia Bancaria</option>
                                <option value="deposito">Depósito Bancario</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta de Crédito / Débito</option>
                                <option value="cheque">Cheque</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-800" for="notas">Observaciones / Términos</label>
                            <textarea class="form-control form-control-sm" id="notas" name="notas" rows="3" placeholder="Garantía, cuenta bancaria para depósito o notas especiales...">{{ old('notas', $cotizacionOrigen?->notas) }}</textarea>
                        </div>

                        {{-- Panel de Totales Resumido --}}
                        <div class="bg-body-secondary p-3 rounded-3 mb-4">
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">Régimen SAT:</span>
                                <span class="badge bg-secondary">Pequeño Contribuyente</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">Desglose IVA:</span>
                                <span class="text-success fw-bold">No Aplica (5% mensual)</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-700">Subtotal:</span>
                                <span class="fw-bold" id="resumenSubtotal">0.00</span>
                            </div>
                            <div class="border-top my-2"></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-1 fw-bold text-1100">TOTAL:</span>
                                <span class="fs-2 fw-bolder text-primary" id="resumenTotal">0.00</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fs-0 fw-bold shadow-sm">
                                <span class="fa-solid fa-receipt me-2"></span>Emitir y Ver Factura
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    <script>
        let indexFila = {{ $cotizacionOrigen && $cotizacionOrigen->items->isNotEmpty() ? $cotizacionOrigen->items->count() : 1 }};

        function agregarFila(tipo = 'S', descripcion = '', cantidad = 1, precio = 0.00) {
            const cuerpo = document.getElementById('cuerpoItems');
            const tr = document.createElement('tr');
            tr.className = 'fila-item';
            tr.innerHTML = `
                <td class="ps-3">
                    <select class="form-select form-select-sm" name="items[${indexFila}][tipo]">
                        <option value="S" ${tipo === 'S' ? 'selected' : ''}>Servicio (S)</option>
                        <option value="B" ${tipo === 'B' ? 'selected' : ''}>Bien (B)</option>
                    </select>
                </td>
                <td>
                    <textarea class="form-control form-control-sm" name="items[${indexFila}][descripcion]" rows="2" placeholder="Descripción del bien o servicio..." required>${descripcion}</textarea>
                </td>
                <td>
                    <input class="form-control form-control-sm text-center cant" type="number" step="1" min="1" name="items[${indexFila}][cantidad]" value="${cantidad}" oninput="recalcularTotales()" required />
                </td>
                <td>
                    <input class="form-control form-control-sm text-end precio" type="number" step="0.01" min="0" name="items[${indexFila}][precio_unitario]" value="${parseFloat(precio).toFixed(2)}" oninput="recalcularTotales()" required />
                </td>
                <td class="text-end fw-bold subt">0.00</td>
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="eliminarFila(this)">
                        <span class="fa-solid fa-trash-can"></span>
                    </button>
                </td>
            `;
            cuerpo.appendChild(tr);
            indexFila++;
            recalcularTotales();
        }

        function agregarSistemaAlDetalle(nombre, resumen, precio) {
            const desc = `Licencia / Renta de Software: ${nombre}${resumen ? '\n' + resumen : ''}`;
            agregarFila('S', desc, 1, precio);
        }

        function agregarProductoAlDetalle(nombre, descripcion) {
            const desc = `${nombre}${descripcion ? '\n' + descripcion : ''}`;
            agregarFila('S', desc, 1, 0.00);
        }

        function eliminarFila(btn) {
            const filas = document.querySelectorAll('.fila-item');
            if (filas.length > 1) {
                btn.closest('tr').remove();
                recalcularTotales();
            } else {
                alert('La factura debe tener al menos una línea.');
            }
        }

        function recalcularTotales() {
            const filas = document.querySelectorAll('.fila-item');
            let total = 0;
            const moneda = document.getElementById('moneda').value;
            const simbolo = moneda === 'GTQ' ? 'Q ' : '$ ';

            filas.forEach(f => {
                const cant = parseFloat(f.querySelector('.cant').value) || 0;
                const prec = parseFloat(f.querySelector('.precio').value) || 0;
                const subt = cant * prec;
                f.querySelector('.subt').innerText = subt.toFixed(2);
                total += subt;
            });

            document.getElementById('resumenSubtotal').innerText = simbolo + total.toFixed(2);
            document.getElementById('resumenTotal').innerText = simbolo + total.toFixed(2);
        }

        function consultarNitSat() {
            const nitInput = document.getElementById('nit_receptor');
            const nit = nitInput.value.trim();
            const btn = document.getElementById('btnBuscarNit');
            const spinner = document.getElementById('spinnerNit');
            const icono = document.getElementById('iconoNit');
            const feedback = document.getElementById('feedbackNit');
            const nombreInput = document.getElementById('nombre_receptor');

            if (!nit) {
                feedback.innerHTML = '<span class="text-danger">Ingrese un NIT o DPI para buscar.</span>';
                return;
            }

            if (nit.toUpperCase() === 'CF') {
                nombreInput.value = 'CONSUMIDOR FINAL';
                feedback.innerHTML = '<span class="text-success"><span class="fa-solid fa-check"></span> Consumidor Final</span>';
                return;
            }

            spinner.classList.remove('d-none');
            icono.classList.add('d-none');
            btn.disabled = true;
            feedback.innerHTML = '<span class="text-muted">Consultando certificador SAT...</span>';

            fetch(`{{ route('admin.facturas.consultar-nit') }}?nit=${encodeURIComponent(nit)}`)
                .then(r => r.json())
                .then(data => {
                    spinner.classList.add('d-none');
                    icono.classList.remove('d-none');
                    btn.disabled = false;

                    if (data.exito && data.nombre) {
                        nombreInput.value = data.nombre;
                        feedback.innerHTML = `<span class="text-success fw-bold"><span class="fa-solid fa-circle-check"></span> Encontrado: ${data.nombre}</span>`;
                    } else {
                        feedback.innerHTML = `<span class="text-warning"><span class="fa-solid fa-triangle-exclamation"></span> ${data.mensaje || 'NIT no encontrado. Puede escribir el nombre manualmente.'}</span>`;
                    }
                })
                .catch(() => {
                    spinner.classList.add('d-none');
                    icono.classList.remove('d-none');
                    btn.disabled = false;
                    feedback.innerHTML = '<span class="text-danger">Error de comunicación al consultar el NIT.</span>';
                });
        }

        document.getElementById('nit_receptor').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                consultarNitSat();
            }
        });

        document.addEventListener('DOMContentLoaded', recalcularTotales);
    </script>
@endsection
