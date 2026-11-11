@extends('layouts.admin', ['titulo' => 'Emitir factura libre'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Emitir factura manual / Venta libre</h2>
            <p class="text-700 mb-0">Cree facturas comerciales o fiscales sin depender de un producto web o pago en línea.</p>
        </div>
        <div>
            <a href="{{ route('admin.facturas.index') }}" class="btn btn-phoenix-secondary">
                <span class="fa-solid fa-arrow-left me-1"></span>Volver a facturas
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <h6 class="text-danger fw-bold mb-1">Por favor revise los errores:</h6>
            <ul class="mb-0 ps-3">
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
            {{-- Datos del Receptor y Generales --}}
            <div class="col-12 col-xl-8">
                <div class="card mb-4">
                    <div class="card-header bg-soft-primary">
                        <h5 class="mb-0 text-primary"><span class="fa-solid fa-user me-2"></span>Datos del Cliente / Receptor</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold" for="nit_receptor">NIT / Documento</label>
                                <input class="form-control" id="nit_receptor" name="nit_receptor" type="text" placeholder="Ej: 12345678 o CF" value="{{ old('nit_receptor', 'CF') }}" required />
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold" for="nombre_receptor">Nombre o Razón Social</label>
                                <input class="form-control" id="nombre_receptor" name="nombre_receptor" type="text" placeholder="Nombre de la empresa o cliente" value="{{ old('nombre_receptor') }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="correo_receptor">Correo Electrónico</label>
                                <input class="form-control" id="correo_receptor" name="correo_receptor" type="email" placeholder="cliente@correo.com" value="{{ old('correo_receptor') }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="direccion_receptor">Dirección</label>
                                <input class="form-control" id="direccion_receptor" name="direccion_receptor" type="text" placeholder="Ciudad o dirección fiscal" value="{{ old('direccion_receptor', 'Ciudad') }}" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ítems / Renglones de Facturación --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center bg-soft-primary">
                        <h5 class="mb-0 text-primary"><span class="fa-solid fa-list-check me-2"></span>Líneas / Conceptos a Facturar</h5>
                        <button type="button" class="btn btn-xs btn-primary" onclick="agregarFila()">
                            <span class="fa-solid fa-plus me-1"></span>Agregar línea
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="tablaItems">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 130px;">Tipo</th>
                                        <th>Descripción detallada (Texto libre)</th>
                                        <th style="width: 100px;">Cant.</th>
                                        <th style="width: 130px;">Precio Unit.</th>
                                        <th style="width: 120px;" class="text-end">Total</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoItems">
                                    <tr class="fila-item">
                                        <td>
                                            <select class="form-select form-select-sm" name="items[0][tipo]">
                                                <option value="S" selected>Servicio (S)</option>
                                                <option value="B">Bien (B)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <textarea class="form-control form-control-sm" name="items[0][descripcion]" rows="2" placeholder="Ej: Desarrollo de módulo a la medida o Renta mensual..." required></textarea>
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm text-center cant" type="number" step="1" min="1" name="items[0][cantidad]" value="1" oninput="recalcularTotales()" required />
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm text-end precio" type="number" step="0.01" min="0" name="items[0][precio_unitario]" value="0.00" oninput="recalcularTotales()" required />
                                        </td>
                                        <td class="text-end fw-bold subt">0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-link text-danger p-0" onclick="eliminarFila(this)">
                                                <span class="fa-solid fa-trash-can"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resumen y Condiciones --}}
            <div class="col-12 col-xl-4">
                <div class="card mb-4">
                    <div class="card-header bg-soft-primary">
                        <h5 class="mb-0 text-primary"><span class="fa-solid fa-sliders me-2"></span>Condiciones de Venta</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="moneda">Moneda</label>
                            <select class="form-select" id="moneda" name="moneda" onchange="recalcularTotales()">
                                <option value="USD" selected>USD ($) Dólares Estadounidenses</option>
                                <option value="GTQ">GTQ (Q) Quetzales de Guatemala</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="condicion_pago">Condición</label>
                            <select class="form-select" id="condicion_pago" name="condicion_pago">
                                <option value="contado" selected>Contado</option>
                                <option value="renta">Renta mensual / recurrente</option>
                                <option value="credito">Crédito</option>
                                <option value="suscripcion">Suscripción anual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="metodo_pago">Método de Pago</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago">
                                <option value="transferencia" selected>Transferencia bancaria</option>
                                <option value="deposito">Depósito bancario</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta de crédito / débito</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="notas">Notas / Términos especiales</label>
                            <textarea class="form-control form-control-sm" id="notas" name="notas" rows="3" placeholder="Garantía, cuentas bancarias, etc."></textarea>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2 fs--1">
                            <span class="text-700">Régimen:</span>
                            <span class="fw-bold">Pequeño Contribuyente</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 fs--1">
                            <span class="text-700">Subtotal:</span>
                            <span class="fw-bold" id="resumenSubtotal">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 fs-2 fw-bolder border-top pt-2">
                            <span>TOTAL:</span>
                            <span class="text-primary" id="resumenTotal">0.00</span>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <span class="fa-solid fa-floppy-disk me-1"></span>Guardar y emitir factura
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        let indexFila = 1;

        function agregarFila() {
            const cuerpo = document.getElementById('cuerpoItems');
            const tr = document.createElement('tr');
            tr.className = 'fila-item';
            tr.innerHTML = `
                <td>
                    <select class="form-select form-select-sm" name="items[${indexFila}][tipo]">
                        <option value="S" selected>Servicio (S)</option>
                        <option value="B">Bien (B)</option>
                    </select>
                </td>
                <td>
                    <textarea class="form-control form-control-sm" name="items[${indexFila}][descripcion]" rows="2" placeholder="Descripción del bien o servicio..." required></textarea>
                </td>
                <td>
                    <input class="form-control form-control-sm text-center cant" type="number" step="1" min="1" name="items[${indexFila}][cantidad]" value="1" oninput="recalcularTotales()" required />
                </td>
                <td>
                    <input class="form-control form-control-sm text-end precio" type="number" step="0.01" min="0" name="items[${indexFila}][precio_unitario]" value="0.00" oninput="recalcularTotales()" required />
                </td>
                <td class="text-end fw-bold subt">0.00</td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="eliminarFila(this)">
                        <span class="fa-solid fa-trash-can"></span>
                    </button>
                </td>
            `;
            cuerpo.appendChild(tr);
            indexFila++;
            recalcularTotales();
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

        document.addEventListener('DOMContentLoaded', recalcularTotales);
    </script>
@endsection
