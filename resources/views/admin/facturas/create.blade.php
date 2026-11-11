@extends('layouts.admin', ['titulo' => 'Emitir Factura'])

@section('contenido')
<style>
    /* Estilos inspirados en el diseño POS Ejecutivo de sistema-inventario */
    .pos-container {
        font-family: inherit;
    }
    .pos-card-product {
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #ffffff;
        cursor: pointer;
        user-select: none;
    }
    .pos-card-product:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.15);
    }
    .pos-card-product:active {
        transform: translateY(0);
    }
    .pos-cart-row {
        transition: all 0.15s ease;
        border: 1px solid #f1f5f9;
        background: #f8fafc;
        border-radius: 12px;
    }
    .pos-cart-row:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .pos-qty-btn {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-weight: bold;
        transition: all 0.1s;
    }
    .pos-qty-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .pos-qty-input {
        width: 44px;
        height: 28px;
        text-align: center;
        font-family: monospace;
        font-weight: 700;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
    }
    .pos-search-box {
        position: relative;
    }
    .pos-search-box .fa-magnifying-glass {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .pos-search-box input {
        padding-left: 36px;
        height: 42px;
        border-radius: 10px;
    }
</style>

<div class="pos-container">
    {{-- Encabezado con estilo POS Ejecutivo --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-soft-primary text-primary" style="width: 44px; height: 44px;">
                <span class="fa-solid fa-cash-register fs-2"></span>
            </div>
            <div>
                <h3 class="mb-0 text-1100 fw-bold">Punto de Emisión & Facturación</h3>
                <p class="text-600 fs--2 mb-0">Emisión rápida de facturas, venta libre de servicios/software y control fiscal directo.</p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge badge-phoenix badge-phoenix-success py-2 px-3">
                <span class="fa-solid fa-circle fa-beat-fade text-success me-1 fs--2"></span>
                Régimen Pequeño Contribuyente
            </span>
            <a href="{{ route('admin.facturas.index') }}" class="btn btn-sm btn-phoenix-secondary">
                <span class="fa-solid fa-list me-1"></span>Ver Emitidas
            </a>
            <button type="submit" form="formFactura" class="btn btn-sm btn-primary px-3 shadow-sm">
                <span class="fa-solid fa-receipt me-1"></span>Cobrar y Facturar
            </button>
        </div>
    </div>

    @if ($cotizacionOrigen)
        <div class="alert alert-subtle-info d-flex align-items-center justify-content-between mb-4 border-info">
            <div class="d-flex align-items-center">
                <span class="fa-solid fa-file-invoice text-info fs-2 me-3"></span>
                <div>
                    <h6 class="mb-0 text-info fw-bold">Facturando desde Cotización {{ $cotizacionOrigen->numero }}</h6>
                    <span class="fs--1 text-800">Cliente: {{ $cotizacionOrigen->cliente_empresa }} &middot; Ítems importados al carrito.</span>
                </div>
            </div>
            <span class="badge bg-info text-white">Importada</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.facturas.store') }}" id="formFactura">
        @csrf
        
        <div class="row g-4">
            {{-- ==============================================
                 COLUMNA IZQUIERDA: Catálogo Visual POS (7 cols)
                 ============================================== --}}
            <div class="col-12 col-xl-7">
                {{-- Tarjeta de Catálogo con Filtro Rápido --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <div class="pos-search-box flex-grow-1">
                                <span class="fa-solid fa-magnifying-glass"></span>
                                <input type="text" id="busquedaCatalogo" class="form-control" placeholder="Buscar software o servicio en el catálogo..." oninput="filtrarCatalogo()" />
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="abrirModalLineaManual()">
                                <span class="fa-solid fa-feather me-1"></span>+ Línea a la medida
                            </button>
                        </div>

                        {{-- Pestañas de categorías rápidas --}}
                        <div class="d-flex gap-2 mb-3 border-bottom pb-2">
                            <button type="button" class="btn btn-sm btn-subtle-primary rounded-pill active" onclick="cambiarTabCatalogo('todos', this)">Todos</button>
                            <button type="button" class="btn btn-sm btn-subtle-secondary rounded-pill" onclick="cambiarTabCatalogo('software', this)">Sistemas y Software</button>
                            <button type="button" class="btn btn-sm btn-subtle-secondary rounded-pill" onclick="cambiarTabCatalogo('servicios', this)">Servicios & Planes</button>
                        </div>

                        {{-- Cuadrícula de Productos Estilo POS --}}
                        <div class="row g-2" id="gridCatalogo" style="max-height: 480px; overflow-y: auto;">
                            @foreach($sistemas as $sis)
                                <div class="col-md-6 item-catalogo" data-tipo="software" data-nombre="{{ strtolower($sis->nombre) }}">
                                    <div class="pos-card-product p-3 rounded-3 h-100 d-flex flex-column justify-content-between" onclick="agregarAlCarrito('S', 'Licencia / Renta de Software: {{ addslashes($sis->nombre) }}', 1, {{ (float)($sis->precio ?? 0) }})">
                                        <div class="d-flex align-items-start gap-2.5">
                                            <div class="d-flex align-items-center justify-content-center rounded-2 bg-soft-primary text-primary flex-shrink-0" style="width: 40px; height: 40px;">
                                                <span class="fa-solid fa-laptop-code fs-1"></span>
                                            </div>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="fw-bold text-1100 text-truncate fs--1">{{ $sis->nombre }}</div>
                                                <div class="text-600 fs--2 line-clamp-1">{{ $sis->resumen ?: 'Software desarrollado para empresas' }}</div>
                                                <span class="badge badge-phoenix badge-phoenix-info fs--2 mt-1">Servicio (S)</span>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                            <span class="fs--2 text-600">Licenciamiento</span>
                                            <span class="fw-bold text-primary font-monospace">{{ $sis->precio > 0 ? '$'.number_format($sis->precio, 2) : 'A convenir' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($productos as $prod)
                                <div class="col-md-6 item-catalogo" data-tipo="servicios" data-nombre="{{ strtolower($prod->nombre) }}">
                                    <div class="pos-card-product p-3 rounded-3 h-100 d-flex flex-column justify-content-between" onclick="agregarAlCarrito('S', '{{ addslashes($prod->nombre) }}', 1, 0.00)">
                                        <div class="d-flex align-items-start gap-2.5">
                                            <div class="d-flex align-items-center justify-content-center rounded-2 bg-soft-secondary text-secondary flex-shrink-0" style="width: 40px; height: 40px;">
                                                <span class="fa-solid fa-cube fs-1"></span>
                                            </div>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="fw-bold text-1100 text-truncate fs--1">{{ $prod->nombre }}</div>
                                                <div class="text-600 fs--2 line-clamp-1">{{ $prod->descripcion ?: 'Servicio técnico o plan' }}</div>
                                                <span class="badge badge-phoenix badge-phoenix-secondary fs--2 mt-1">Plan / Servicio</span>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                            <span class="fs--2 text-600">Suscripción</span>
                                            <span class="fw-bold text-secondary font-monospace">Precio a definir</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Banner de información / atajos --}}
                <div class="card border-0 bg-soft-primary p-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fa-solid fa-lightbulb text-primary fs-2"></span>
                        <div class="fs--2 text-800">
                            <strong>Tip POS:</strong> Puedes pulsar cualquier producto para añadirlo al ticket, o usar el botón <strong>"+ Línea a la medida"</strong> para redactar conceptos extensos con descripciones personalizadas.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==============================================
                 COLUMNA DERECHA: Carrito, Receptor y Totales (5 cols)
                 ============================================== --}}
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm mb-4">
                    
                    {{-- 1. Buscador y Datos del Cliente con Búsqueda SAT --}}
                    <div class="card-header bg-body-highlight py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase fs--2 fw-bold text-600">Cliente / Receptor</span>
                            <span class="badge badge-phoenix badge-phoenix-primary fs--2">Consulta SAT</span>
                        </div>
                        <div class="input-group">
                            <input class="form-control form-control-sm font-monospace" id="nit_receptor" name="nit_receptor" type="text" placeholder="NIT o CF..." value="{{ old('nit_receptor', $cotizacionOrigen ? ($cotizacionOrigen->cliente_nit ?? 'CF') : 'CF') }}" required />
                            <button class="btn btn-sm btn-primary" type="button" id="btnBuscarNit" onclick="consultarNitSat()">
                                <span id="spinnerNit" class="spinner-border spinner-border-sm me-1 d-none"></span>
                                <span id="iconoNit" class="fa-solid fa-magnifying-glass me-1"></span>Buscar
                            </button>
                        </div>
                        <div id="feedbackNit" class="fs--2 mt-1"></div>

                        <div class="mt-2">
                            <input class="form-control form-control-sm mb-2" id="nombre_receptor" name="nombre_receptor" type="text" placeholder="Razón Social / Nombre Completo" value="{{ old('nombre_receptor', $cotizacionOrigen?->cliente_empresa) }}" required />
                            <div class="row g-2">
                                <div class="col-6">
                                    <input class="form-control form-control-sm" id="correo_receptor" name="correo_receptor" type="email" placeholder="Correo (opcional)" value="{{ old('correo_receptor', $cotizacionOrigen?->cliente_correo) }}" />
                                </div>
                                <div class="col-6">
                                    <input class="form-control form-control-sm" id="direccion_receptor" name="direccion_receptor" type="text" placeholder="Dirección" value="{{ old('direccion_receptor', $cotizacionOrigen?->cliente_direccion ?? 'Ciudad') }}" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Líneas del Ticket / Carrito POS --}}
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase fs--2 fw-bold text-600">Líneas en el Ticket (<span id="conteoLineas">0</span>)</span>
                            <button type="button" class="btn btn-link text-danger p-0 fs--2 text-decoration-none" onclick="vaciarCarrito()">
                                <span class="fa-solid fa-rotate-left me-1"></span>Limpiar ticket
                            </button>
                        </div>

                        {{-- Lista dinámica de ítems tipo tarjeta POS --}}
                        <div id="listaCarrito" style="max-height: 280px; overflow-y: auto;" class="d-flex flex-column gap-2 mb-3">
                            {{-- Se inyectan dinámicamente con JS --}}
                        </div>

                        {{-- Contenedor vacío --}}
                        <div id="carritoVacio" class="text-center py-4 border border-dashed rounded-3 bg-light">
                            <span class="fa-solid fa-basket-shopping text-400 fs-3 mb-2 d-block"></span>
                            <p class="text-600 fs--1 mb-0">El ticket se encuentra vacío</p>
                            <span class="fs--2 text-500">Toca un software del catálogo o pulsa "+ Línea a la medida".</span>
                        </div>

                        <hr class="my-3">

                        {{-- Opciones de Venta --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fs--2 fw-bold text-700 mb-1" for="moneda">Moneda</label>
                                <select class="form-select form-select-sm" id="moneda" name="moneda" onchange="actualizarMoneda()">
                                    <option value="USD" {{ old('moneda', $cotizacionOrigen?->moneda ?? 'USD') === 'USD' ? 'selected' : '' }}>USD ($) Dólares</option>
                                    <option value="GTQ" {{ old('moneda', $cotizacionOrigen?->moneda) === 'GTQ' ? 'selected' : '' }}>GTQ (Q) Quetzales</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fs--2 fw-bold text-700 mb-1" for="metodo_pago">Método de Cobro</label>
                                <select class="form-select form-select-sm" id="metodo_pago" name="metodo_pago">
                                    <option value="transferencia" selected>Transferencia</option>
                                    <option value="deposito">Depósito</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="paypal">PayPal</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs--2 text-700 mb-1" for="notas">Notas en Factura</label>
                                <input class="form-control form-control-sm" id="notas" name="notas" placeholder="Ej: Renta mes de noviembre / Garantía..." value="{{ old('notas', $cotizacionOrigen?->notas) }}" />
                            </div>
                            <input type="hidden" name="condicion_pago" value="contado">
                        </div>

                        {{-- Panel de Totales Resumido estilo POS --}}
                        <div class="rounded-3 bg-body-highlight p-3 border">
                            <div class="d-flex justify-content-between fs--1 text-700 mb-1">
                                <span>Régimen Fiscal:</span>
                                <span class="badge bg-secondary">Pequeño Contribuyente</span>
                            </div>
                            <div class="d-flex justify-content-between fs--1 text-700 mb-1">
                                <span>Subtotal:</span>
                                <span class="fw-bold font-monospace" id="txtSubtotal">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between fs--1 text-700 mb-2">
                                <span>Desglose IVA:</span>
                                <span class="text-success fw-bold">Exento (5% Mensual SAT)</span>
                            </div>
                            <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                                <span class="fs-1 fw-bold text-1100">TOTAL:</span>
                                <span class="fs-2 fw-bolder text-primary font-monospace" id="txtTotal">0.00</span>
                            </div>
                        </div>

                        {{-- Botón Grande de Cobro --}}
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100 py-2 fs-0 fw-bold shadow-sm">
                                <span class="fa-solid fa-receipt me-2"></span>Cobrar y Generar Factura
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

{{-- Modal para redactar Línea a la Medida --}}
<div class="modal fade" id="modalLineaManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="fa-solid fa-pen-to-square me-2 text-primary"></span>Agregar Concepto Personalizado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Clasificación SAT</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modal_tipo" id="modal_tipo_s" value="S" checked>
                            <label class="form-check-label" for="modal_tipo_s"><strong>Servicio (S)</strong> - Desarrollo, renta, asesoría</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modal_tipo" id="modal_tipo_b" value="B">
                            <label class="form-check-label" for="modal_tipo_b"><strong>Bien (B)</strong> - Producto, hardware, licencia</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción / Texto Libre</label>
                    <textarea class="form-control" id="modal_desc" rows="3" placeholder="Describa a detalle el alcance, periodo o condiciones..."></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-bold">Cantidad</label>
                        <input class="form-control" type="number" id="modal_cant" value="1" min="1" step="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Precio Unitario</label>
                        <input class="form-control" type="number" id="modal_precio" value="0.00" min="0" step="0.01">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarLineaManual()">Agregar al Ticket</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Carrito de compras tipo POS
    let carrito = [];
    let modalManual = null;

    document.addEventListener('DOMContentLoaded', function() {
        modalManual = new bootstrap.Modal(document.getElementById('modalLineaManual'));

        // Si viene desde cotización, precargar sus ítems
        @if($cotizacionOrigen && $cotizacionOrigen->items->isNotEmpty())
            @foreach($cotizacionOrigen->items as $it)
                agregarAlCarrito(
                    'S',
                    '{{ addslashes($it->titulo . ($it->descripcion ? " — " . $it->descripcion : "")) }}',
                    {{ (int)($it->cantidad ?? 1) },
                    {{ (float)($it->precio_unitario ?? 0) }}
                );
            @endforeach
        @endif
        
        renderizarCarrito();
    });

    function agregarAlCarrito(tipo, descripcion, cantidad = 1, precio = 0.00) {
        carrito.push({
            tipo: tipo,
            descripcion: descripcion,
            cantidad: parseFloat(cantidad) || 1,
            precio: parseFloat(precio) || 0.00
        });
        renderizarCarrito();
    }

    function renderizarCarrito() {
        const contenedor = document.getElementById('listaCarrito');
        const vacio = document.getElementById('carritoVacio');
        const conteo = document.getElementById('conteoLineas');
        const moneda = document.getElementById('moneda').value;
        const simbolo = moneda === 'GTQ' ? 'Q' : '$';

        contenedor.innerHTML = '';
        conteo.innerText = carrito.length;

        if (carrito.length === 0) {
            vacio.classList.remove('d-none');
            document.getElementById('txtSubtotal').innerText = simbolo + ' 0.00';
            document.getElementById('txtTotal').innerText = simbolo + ' 0.00';
            return;
        }

        vacio.classList.add('d-none');
        let total = 0;

        carrito.forEach((item, index) => {
            const subtotal = item.cantidad * item.precio;
            total += subtotal;

            const div = document.createElement('div');
            div.className = 'pos-cart-row p-2.5 d-flex align-items-center gap-2';
            div.innerHTML = `
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge ${item.tipo === 'B' ? 'bg-primary' : 'bg-info'} text-white fs--2">${item.tipo}</span>
                        <input type="hidden" name="items[${index}][tipo]" value="${item.tipo}">
                        <span class="fw-bold fs--1 text-truncate text-1000 d-inline-block" style="max-width: 220px;" title="${item.descripcion}">${item.descripcion}</span>
                    </div>
                    <textarea class="d-none" name="items[${index}][descripcion]">${item.descripcion}</textarea>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="fs--2 text-muted">P.U:</span>
                        <input type="number" step="0.01" class="form-control form-control-sm font-monospace p-1 text-end" style="width: 75px; height: 24px; font-size: 11px;" value="${item.precio.toFixed(2)}" onchange="cambiarPrecioItem(${index}, this.value)" name="items[${index}][precio_unitario]">
                    </div>
                </div>

                {{-- Controles Táctiles POS de Cantidad --}}
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button type="button" class="pos-qty-btn" onclick="ajustarCantidad(${index}, -1)">-</button>
                    <input type="number" class="pos-qty-input" value="${item.cantidad}" min="1" step="1" onchange="fijarCantidadItem(${index}, this.value)" name="items[${index}][cantidad]">
                    <button type="button" class="pos-qty-btn" onclick="ajustarCantidad(${index}, 1)">+</button>
                </div>

                {{-- Total de la línea --}}
                <div class="font-monospace fw-bold fs--1 text-end" style="width: 65px;">
                    ${simbolo}${subtotal.toFixed(2)}
                </div>

                {{-- Botón eliminar --}}
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="quitarItem(${index})">
                    <span class="fa-solid fa-trash-can"></span>
                </button>
            `;
            contenedor.appendChild(div);
        });

        document.getElementById('txtSubtotal').innerText = simbolo + ' ' + total.toFixed(2);
        document.getElementById('txtTotal').innerText = simbolo + ' ' + total.toFixed(2);
    }

    function ajustarCantidad(index, cambio) {
        if (carrito[index]) {
            const nueva = carrito[index].cantidad + cambio;
            if (nueva > 0) {
                carrito[index].cantidad = nueva;
                renderizarCarrito();
            }
        }
    }

    function fijarCantidadItem(index, valor) {
        const v = parseFloat(valor);
        if (v > 0) {
            carrito[index].cantidad = v;
            renderizarCarrito();
        }
    }

    function cambiarPrecioItem(index, valor) {
        const p = parseFloat(valor);
        if (!isNaN(p) && p >= 0) {
            carrito[index].precio = p;
            renderizarCarrito();
        }
    }

    function quitarItem(index) {
        carrito.splice(index, 1);
        renderizarCarrito();
    }

    function vaciarCarrito() {
        if (carrito.length > 0 && confirm('¿Desea vaciar todos los ítems del ticket?')) {
            carrito = [];
            renderizarCarrito();
        }
    }

    function actualizarMoneda() {
        renderizarCarrito();
    }

    function abrirModalLineaManual() {
        document.getElementById('modal_desc').value = '';
        document.getElementById('modal_cant').value = '1';
        document.getElementById('modal_precio').value = '0.00';
        modalManual.show();
    }

    function confirmarLineaManual() {
        const desc = document.getElementById('modal_desc').value.trim();
        const cant = parseFloat(document.getElementById('modal_cant').value) || 1;
        const prec = parseFloat(document.getElementById('modal_precio').value) || 0;
        const tipo = document.querySelector('input[name="modal_tipo"]:checked').value;

        if (!desc) {
            alert('Debe escribir la descripción del concepto.');
            return;
        }

        agregarAlCarrito(tipo, desc, cant, prec);
        modalManual.hide();
    }

    function filtrarCatalogo() {
        const texto = document.getElementById('busquedaCatalogo').value.toLowerCase().trim();
        const items = document.querySelectorAll('.item-catalogo');

        items.forEach(it => {
            const nombre = it.getAttribute('data-nombre');
            if (!texto || nombre.includes(texto)) {
                it.classList.remove('d-none');
            } else {
                it.classList.add('d-none');
            }
        });
    }

    function cambiarTabCatalogo(categoria, btn) {
        document.querySelectorAll('#gridCatalogo .item-catalogo').forEach(it => {
            if (categoria === 'todos' || it.getAttribute('data-tipo') === categoria) {
                it.classList.remove('d-none');
            } else {
                it.classList.add('d-none');
            }
        });

        btn.parentElement.querySelectorAll('button').forEach(b => {
            b.classList.remove('btn-subtle-primary', 'active');
            b.classList.add('btn-subtle-secondary');
        });
        btn.classList.remove('btn-subtle-secondary');
        btn.classList.add('btn-subtle-primary', 'active');
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
            feedback.innerHTML = '<span class="text-danger">Ingrese un NIT para consultar.</span>';
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
        feedback.innerHTML = '<span class="text-muted">Consultando con SAT / INFILE...</span>';

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
                    feedback.innerHTML = `<span class="text-warning"><span class="fa-solid fa-triangle-exclamation"></span> ${data.mensaje || 'No encontrado en la SAT. Escriba el nombre.'}</span>`;
                }
            })
            .catch(() => {
                spinner.classList.add('d-none');
                icono.classList.remove('d-none');
                btn.disabled = false;
                feedback.innerHTML = '<span class="text-danger">Error de conexión al consultar NIT.</span>';
            });
    }

    document.getElementById('nit_receptor').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            consultarNitSat();
        }
    });
</script>
@endsection
