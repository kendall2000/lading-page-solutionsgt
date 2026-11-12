@extends('layouts.admin', ['titulo' => 'Emitir Factura'])

@section('contenido')
<style>
    /* Offset para Sticky en Desktop por debajo del navbar administrativo */
    @media (min-width: 1200px) {
        .sticky-xl-top-ticket {
            position: sticky;
            top: 4.8rem;
            z-index: 10;
        }
    }

    /* Feedback físico sutil para tarjetas del catálogo (Emil Kowalski) */
    .card-product-pos {
        transition: transform 140ms cubic-bezier(0.23, 1, 0.32, 1), box-shadow 140ms ease, border-color 140ms ease;
        user-select: none;
    }
    .card-product-pos:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px -4px rgba(15, 23, 42, 0.08);
        border-color: rgba(56, 116, 255, 0.5) !important;
    }
    .card-product-pos:active {
        transform: scale(0.985) translateY(0);
    }

    /* Animación de entrada suave para ítems añadidos al ticket */
    @media (prefers-reduced-motion: no-preference) {
        @keyframes cartItemEnter {
            0% {
                opacity: 0;
                transform: translateY(-5px) scale(0.985);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .cart-row-animate {
            animation: cartItemEnter 160ms cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }
    }
</style>

<div>
    {{-- Encabezado oficial con clases Phoenix --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-3 bg-soft-primary text-primary flex-shrink-0" style="width: 44px; height: 44px;">
                <span class="fa-solid fa-cash-register fs-2"></span>
            </div>
            <div>
                <h3 class="mb-0 text-1100 fw-bold">Punto de Emisión &amp; Facturación</h3>
                <p class="text-600 fs--2 mb-0">Emisión de facturas adaptada para móvil, tablet y escritorio.</p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto justify-content-start justify-content-md-end">
            <span class="badge badge-phoenix badge-phoenix-success py-2 px-3">
                <span class="fa-solid fa-circle text-success me-1 fs--2"></span>
                Pequeño Contribuyente
            </span>
            <a href="{{ route('admin.facturas.index') }}" class="btn btn-sm btn-phoenix-secondary">
                <span class="fa-solid fa-list me-1"></span>Ver Emitidas
            </a>
            <button type="submit" form="formFactura" class="btn btn-sm btn-primary px-3 shadow-sm flex-grow-1 flex-md-grow-0">
                <span class="fa-solid fa-receipt me-1"></span>Cobrar y Facturar
            </button>
        </div>
    </div>

    @if ($cotizacionOrigen)
        <div class="alert alert-soft-info d-flex align-items-center justify-content-between mb-4 border-info">
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
        
        <div class="row g-4 flex-column-reverse flex-xl-row">
            {{-- ==============================================
                 COLUMNA IZQUIERDA: Catálogo Visual POS (7 cols)
                 ============================================== --}}
            <div class="col-12 col-xl-7">
                {{-- Tarjeta de Catálogo con Filtro Rápido --}}
                <div class="card border border-200 mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-stretch align-items-sm-center mb-3">
                            <div class="search-box flex-grow-1 position-relative">
                                <input type="text" id="busquedaCatalogo" class="form-control search-input fuzzy-search" placeholder="Buscar software o servicio..." oninput="filtrarCatalogo()" />
                                <span class="fas fa-search search-box-icon"></span>
                            </div>
                            <button type="button" class="btn btn-phoenix-primary text-nowrap" onclick="abrirModalLineaManual()">
                                <span class="fa-solid fa-feather me-1"></span>+ Línea a la medida
                            </button>
                        </div>

                        {{-- Pestañas de categorías rápidas con scroll horizontal en móvil --}}
                        <div class="d-flex gap-2 mb-3 border-bottom pb-2 overflow-auto text-nowrap">
                            <button type="button" class="btn btn-sm btn-phoenix-primary rounded-pill active" onclick="cambiarTabCatalogo('todos', this)">Todos</button>
                            <button type="button" class="btn btn-sm btn-phoenix-secondary rounded-pill" onclick="cambiarTabCatalogo('software', this)">Sistemas y Software</button>
                            <button type="button" class="btn btn-sm btn-phoenix-secondary rounded-pill" onclick="cambiarTabCatalogo('servicios', this)">Servicios &amp; Planes</button>
                        </div>

                        {{-- Cuadrícula de Productos Estilo Catálogo Phoenix --}}
                        <div class="row g-2 scrollbar" id="gridCatalogo" style="max-height: 520px; overflow-y: auto;">
                            @foreach($sistemas as $sis)
                                <div class="col-12 col-sm-6 item-catalogo" data-tipo="software" data-nombre="{{ strtolower($sis->nombre) }}">
                                    <div class="card h-100 border border-200 card-product-pos cursor-pointer" onclick="agregarAlCarrito('S', 'Licencia / Renta de Software: {{ addslashes($sis->nombre) }}', 1, {{ (float)($sis->precio ?? 0) }})">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
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
                                            <div class="d-flex justify-content-between align-items-center border-top border-200 pt-2 mt-2">
                                                <span class="fs--2 text-600">Licenciamiento</span>
                                                <span class="fw-bold text-primary font-monospace">{{ $sis->precio > 0 ? '$'.number_format($sis->precio, 2) : 'A convenir' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($productos as $prod)
                                <div class="col-12 col-sm-6 item-catalogo" data-tipo="servicios" data-nombre="{{ strtolower($prod->nombre) }}">
                                    <div class="card h-100 border border-200 card-product-pos cursor-pointer" onclick="agregarAlCarrito('S', '{{ addslashes($prod->nombre) }}', 1, 0.00)">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
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
                                            <div class="d-flex justify-content-between align-items-center border-top border-200 pt-2 mt-2">
                                                <span class="fs--2 text-600">Suscripción</span>
                                                <span class="fw-bold text-secondary font-monospace">Precio libre</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Tip POS con componentes nativos --}}
                <div class="card border border-200 bg-soft-primary p-3 mb-4 mb-xl-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fa-solid fa-lightbulb text-primary fs-2"></span>
                        <div class="fs--2 text-800">
                            <strong>Tip:</strong> Puedes pulsar un producto para agregarlo o usar <strong>"+ Línea a la medida"</strong> para conceptos libres.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==============================================
                 COLUMNA DERECHA: Carrito, Receptor y Totales (5 cols)
                 Sticky en escritorio con align-self-start y offset superior
                 ============================================== --}}
            <div class="col-12 col-xl-5 align-self-start sticky-xl-top-ticket">
                <div class="card border border-200 shadow-sm mb-4">
                    
                    {{-- 1. Buscador y Datos del Cliente con Búsqueda SAT --}}
                    <div class="card-header bg-body py-3 border-bottom border-200">
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
                                <div class="col-12 col-sm-6">
                                    <input class="form-control form-control-sm" id="correo_receptor" name="correo_receptor" type="email" placeholder="Correo (opcional)" value="{{ old('correo_receptor', $cotizacionOrigen?->cliente_correo) }}" />
                                </div>
                                <div class="col-12 col-sm-6">
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

                        {{-- Lista dinámica de ítems tipo tarjeta adaptada a Phoenix --}}
                        <div id="listaCarrito" style="max-height: 280px; overflow-y: auto;" class="scrollbar d-flex flex-column gap-2 mb-3">
                            {{-- Se inyectan dinámicamente con JS --}}
                        </div>

                        {{-- Contenedor vacío oficial Phoenix --}}
                        <div id="carritoVacio" class="text-center py-4 border border-dashed border-300 rounded-3 bg-light">
                            <span class="fa-solid fa-basket-shopping text-400 fs-3 mb-2 d-block"></span>
                            <p class="text-600 fs--1 mb-0">El ticket se encuentra vacío</p>
                            <span class="fs--2 text-500">Toca un software o pulsa "+ Línea a la medida".</span>
                        </div>

                        <hr class="my-3 border-200">

                        {{-- Opciones de Venta adaptables --}}
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label fs--2 fw-bold text-700 mb-1" for="moneda">Moneda</label>
                                <select class="form-select form-select-sm" id="moneda" name="moneda" onchange="actualizarMoneda()">
                                    <option value="USD" {{ old('moneda', $cotizacionOrigen?->moneda ?? 'USD') === 'USD' ? 'selected' : '' }}>USD ($) Dólares</option>
                                    <option value="GTQ" {{ old('moneda', $cotizacionOrigen?->moneda) === 'GTQ' ? 'selected' : '' }}>GTQ (Q) Quetzales</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
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

                        {{-- Panel de Totales Resumido estilo Order Summary de Phoenix --}}
                        <div class="rounded-3 bg-light p-3 border border-200">
                            <div class="d-flex justify-content-between fs--1 text-700 mb-1">
                                <span>Régimen Fiscal:</span>
                                <span class="badge badge-phoenix badge-phoenix-secondary">Pequeño Contribuyente</span>
                            </div>
                            <div class="d-flex justify-content-between fs--1 text-700 mb-1">
                                <span>Subtotal:</span>
                                <span class="fw-bold font-monospace text-900" id="txtSubtotal">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between fs--1 text-700 mb-2">
                                <span>Desglose IVA:</span>
                                <span class="text-success fw-bold">Exento (5% Mensual SAT)</span>
                            </div>
                            <div class="border-top border-200 pt-2 d-flex justify-content-between align-items-center">
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
        <div class="modal-content border border-200">
            <div class="modal-header border-bottom border-200">
                <h5 class="modal-title"><span class="fa-solid fa-pen-to-square me-2 text-primary"></span>Agregar Concepto Personalizado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Clasificación SAT</label>
                    <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modal_tipo" id="modal_tipo_s" value="S" checked>
                            <label class="form-check-label" for="modal_tipo_s"><strong>Servicio (S)</strong> - Desarrollo, renta, asesoría</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modal_tipo" id="modal_tipo_b" value="B">
                            <label class="form-check-label" for="modal_tipo_b"><strong>Bien (B)</strong> - Licencia, hardware</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción / Texto Libre</label>
                    <textarea class="form-control" id="modal_desc" rows="3" placeholder="Describa a detalle el alcance, periodo o condiciones..."></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-bold">Cantidad</label>
                        <input class="form-control" type="number" id="modal_cant" value="1" min="1" step="1">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-bold">Precio Unitario</label>
                        <input class="form-control" type="number" id="modal_precio" value="0.00" min="0" step="0.01" onfocus="this.select()">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-200">
                <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarLineaManual()">Agregar al Ticket</button>
            </div>
        </div>
    </div>
</div>

<script>
    let carrito = [];
    let modalManual = null;

    document.addEventListener('DOMContentLoaded', function() {
        modalManual = new bootstrap.Modal(document.getElementById('modalLineaManual'));

        @if($cotizacionOrigen && $cotizacionOrigen->items->isNotEmpty())
            @foreach($cotizacionOrigen->items as $it)
                agregarAlCarrito(
                    'S',
                    '{{ addslashes($it->titulo . ($it->descripcion ? " — " . $it->descripcion : "")) }}',
                    {{ (int)($it->cantidad ?? 1) }},
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
            div.className = 'border border-200 rounded-3 bg-light p-2.5 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2 cart-row-animate';
            div.innerHTML = `
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge ${item.tipo === 'B' ? 'badge-phoenix-primary' : 'badge-phoenix-info'} fs--2">${item.tipo}</span>
                        <input type="hidden" name="items[${index}][tipo]" value="${item.tipo}">
                        <span class="fw-bold fs--1 text-truncate text-1000 d-inline-block" style="max-width: 220px;" title="${item.descripcion}">${item.descripcion}</span>
                    </div>
                    <textarea class="d-none" name="items[${index}][descripcion]">${item.descripcion}</textarea>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="fs--2 text-600">P.U:</span>
                        <input type="number" step="0.01" class="form-control form-control-sm font-monospace p-1 text-end" style="width: 82px; height: 26px; font-size: 11px;" value="${item.precio.toFixed(2)}" onfocus="this.select()" onchange="cambiarPrecioItem(${index}, this.value)" name="items[${index}][precio_unitario]">
                    </div>
                </div>

                {{-- Controles de Cantidad oficiales Phoenix (+/-) con clase input-spin-none --}}
                <div class="d-flex align-items-center justify-content-between justify-content-sm-end gap-2 flex-shrink-0 pt-2 pt-sm-0 border-top border-200 border-top-sm-0">
                    <div class="input-group input-group-sm flex-nowrap border border-300 rounded-2 bg-white" style="width: 95px;">
                        <button type="button" class="btn btn-sm px-2 text-700 border-0" onclick="ajustarCantidad(${index}, -1)">-</button>
                        <input type="number" class="form-control text-center input-spin-none bg-transparent border-0 px-0 font-monospace fw-bold fs--1" value="${item.cantidad}" min="1" step="1" onchange="fijarCantidadItem(${index}, this.value)" name="items[${index}][cantidad]">
                        <button type="button" class="btn btn-sm px-2 text-700 border-0" onclick="ajustarCantidad(${index}, 1)">+</button>
                    </div>

                    <div class="font-monospace fw-bold fs--1 text-end text-primary" style="min-width: 65px;">
                        ${simbolo}${subtotal.toFixed(2)}
                    </div>

                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" onclick="quitarItem(${index})" title="Eliminar">
                        <span class="fa-solid fa-trash-can"></span>
                    </button>
                </div>
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
            b.classList.remove('btn-phoenix-primary', 'active');
            b.classList.add('btn-phoenix-secondary');
        });
        btn.classList.remove('btn-phoenix-secondary');
        btn.classList.add('btn-phoenix-primary', 'active');
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
