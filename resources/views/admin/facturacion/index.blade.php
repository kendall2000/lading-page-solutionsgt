@extends('layouts.admin', ['titulo' => 'Facturas y comprobantes'])

@use('App\Models\Pago')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Facturas y comprobantes</h2>
            <p class="text-700 mb-0">Emisión de facturas corporativas en formato A4, Tickets de 80mm y configuración de certificación FEL (INFILE).</p>
        </div>
        <div>
            @if ($cfgDte->activo)
                <span class="badge badge-phoenix badge-phoenix-{{ $cfgDte->ambiente === 'produccion' ? 'success' : 'warning' }} fs--1">
                    <span class="fa-solid fa-circle-check me-1"></span>Certificación FEL {{ ucfirst($cfgDte->ambiente) }}
                </span>
            @else
                <span class="badge badge-phoenix badge-phoenix-secondary fs--1">
                    <span class="fa-solid fa-circle-pause me-1"></span>FEL / INFILE Inactivo
                </span>
            @endif
        </div>
    </div>

    @if (session('exito'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <span class="fa-solid fa-check-circle me-2"></span>{{ session('exito') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Métricas Rápidas --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="d-flex align-items-center justify-content-center rounded-circle bg-soft-primary text-primary" style="width:48px;height:48px">
                    <span class="fa-solid fa-file-invoice fs-1"></span>
                </span>
                <div>
                    <p class="fs--1 text-700 mb-0">Comprobantes emitidos</p>
                    <h3 class="mb-0">{{ $conteos['facturados'] }}</h3>
                    <p class="fs--2 text-600 mb-0">Disponibles en A4 y Ticket 80mm</p>
                </div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="d-flex align-items-center justify-content-center rounded-circle bg-soft-success text-success" style="width:48px;height:48px">
                    <span class="fa-solid fa-qrcode fs-1"></span>
                </span>
                <div>
                    <p class="fs--1 text-700 mb-0">Validación con QR propio</p>
                    <h3 class="mb-0">Activo</h3>
                    <p class="fs--2 text-600 mb-0">Verificación inmediata en su portal</p>
                </div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="d-flex align-items-center justify-content-center rounded-circle bg-soft-info text-info" style="width:48px;height:48px">
                    <span class="fa-solid fa-stamp fs-1"></span>
                </span>
                <div>
                    <p class="fs--1 text-700 mb-0">Certificaciones SAT FEL</p>
                    <h3 class="mb-0">{{ $conteos['dtes_certificados'] }}</h3>
                    <p class="fs--2 text-600 mb-0">Documentos tributarios autorizados</p>
                </div>
            </div></div>
        </div>
    </div>

    {{-- Navegación por pestañas --}}
    <ul class="nav nav-underline mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $pestana === 'comprobantes' ? 'active' : '' }}" href="{{ route('admin.facturacion.index', ['ver' => 'comprobantes']) }}">
                <span class="fa-solid fa-file-invoice me-2"></span>Comprobantes y Facturas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $pestana === 'configuracion' ? 'active' : '' }}" href="{{ route('admin.facturacion.index', ['ver' => 'configuracion']) }}">
                <span class="fa-solid fa-sliders me-2"></span>Configuración FEL (INFILE & Frases SAT)
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Pestaña 1: Comprobantes y Facturas --}}
        @if ($pestana === 'comprobantes')
            <div class="card">
                <div class="card-body">
                    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.facturacion.index') }}">
                        <input type="hidden" name="ver" value="comprobantes">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input class="form-control form-control-sm" type="text" name="q" value="{{ $buscar }}" placeholder="Buscar por cliente, correo o ref..." />
                                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Buscar</button>
                            </div>
                        </div>
                    </form>

                    @if ($pagos->isEmpty())
                        <p class="text-700 mb-0">No se encontraron pagos o comprobantes registrados.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-0">No. Factura</th>
                                        <th>Fecha</th>
                                        <th>Cliente / Receptor</th>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto</th>
                                        <th>Estado</th>
                                        <th class="pe-0 text-end">Acciones / Formatos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pagos as $p)
                                        @php
                                            $p->asegurarComprobante();
                                            [$estadoTexto, $estadoColor] = $p->estadoInfo();
                                        @endphp
                                        <tr>
                                            <td class="ps-0 font-monospace fw-bold text-primary">{{ $p->numero_comprobante }}</td>
                                            <td class="text-nowrap">{{ ($p->pagado_en ?? $p->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="fw-semi-bold">{{ $p->receptor_nombre ?: ($p->cuenta?->nombre ?: 'Consumidor Final') }}</div>
                                                <div class="text-600 fs--2">{{ $p->correo }}</div>
                                            </td>
                                            <td>{{ $p->descripcion }}</td>
                                            <td class="text-end text-nowrap fw-bold">{{ $p->montoTexto() }}</td>
                                            <td><span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span></td>
                                            <td class="pe-0 text-end text-nowrap">
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.pagos.imprimir', $p) }}?formato=factura" target="_blank" class="btn btn-xs btn-phoenix-primary" title="Imprimir / Ver Formato A4">
                                                        <span class="fa-solid fa-file-invoice me-1"></span>A4
                                                    </a>
                                                    <a href="{{ route('admin.pagos.imprimir', $p) }}?formato=ticket" target="_blank" class="btn btn-xs btn-phoenix-secondary" title="Imprimir / Ver Ticket 80mm">
                                                        <span class="fa-solid fa-receipt me-1"></span>Ticket
                                                    </a>
                                                    <a href="{{ route('admin.pagos.descargar-pdf', $p) }}?formato=factura" class="btn btn-xs btn-phoenix-info" title="Descargar PDF">
                                                        <span class="fa-solid fa-download"></span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $pagos->links() }}</div>
                    @endif
                </div>
            </div>

        {{-- Pestaña 2: Configuración INFILE (DTE / SAT / Frases / Establecimientos) --}}
        @elseif ($pestana === 'configuracion')
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.facturacion.configuracion') }}">
                        @csrf
                        <div class="d-flex flex-between-center mb-4">
                            <div>
                                <h4 class="card-title mb-1">Credenciales y Parámetros SAT / INFILE</h4>
                                <p class="text-700 fs--1 mb-0">Configure los datos de su empresa emisora, establecimientos, frases obligatorias SAT y tipos de documentos.</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $cfgDte->activo)) />
                                <label class="form-check-label fw-semi-bold" for="activo">Habilitar Certificación FEL</label>
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Sección 1: Emisor y Establecimiento --}}
                            <div class="col-12"><h5 class="text-primary border-bottom pb-2 mb-2"><span class="fa-solid fa-building me-2"></span>Datos del Emisor y Establecimiento</h5></div>

                            <div class="col-md-3">
                                <label class="form-label" for="ambiente">Ambiente de Operación</label>
                                <select class="form-select" id="ambiente" name="ambiente" required>
                                    <option value="pruebas" @selected(old('ambiente', $cfgDte->ambiente) === 'pruebas')>Pruebas (Sandbox)</option>
                                    <option value="produccion" @selected(old('ambiente', $cfgDte->ambiente) === 'produccion')>Producción (SAT Real)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="afiliacion_iva">Afiliación IVA</label>
                                <select class="form-select" id="afiliacion_iva" name="afiliacion_iva" required>
                                    <option value="PEQ" @selected(old('afiliacion_iva', $cfgDte->afiliacion_iva) === 'PEQ')>PEQ (Pequeño Contribuyente 5%)</option>
                                    <option value="GEN" @selected(old('afiliacion_iva', $cfgDte->afiliacion_iva) === 'GEN')>GEN (Régimen General 12%)</option>
                                    <option value="EXE" @selected(old('afiliacion_iva', $cfgDte->afiliacion_iva) === 'EXE')>EXE (Exento de IVA)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="codigo_establecimiento">Código Establecimiento SAT</label>
                                <input class="form-control font-monospace" id="codigo_establecimiento" name="codigo_establecimiento" type="text" value="{{ old('codigo_establecimiento', $cfgDte->codigo_establecimiento ?? '1') }}" placeholder="Ej: 1" required />
                                <div class="fs--2 text-muted">Según su RTU de SAT (Generalmente 1)</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="nit_emisor">NIT Emisor</label>
                                <input class="form-control font-monospace" id="nit_emisor" name="nit_emisor" type="text" value="{{ old('nit_emisor', $cfgDte->nit_emisor) }}" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="nombre_emisor">Nombre o Razón Social</label>
                                <input class="form-control" id="nombre_emisor" name="nombre_emisor" type="text" value="{{ old('nombre_emisor', $cfgDte->nombre_emisor) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="nombre_comercial">Nombre Comercial</label>
                                <input class="form-control" id="nombre_comercial" name="nombre_comercial" type="text" value="{{ old('nombre_comercial', $cfgDte->nombre_comercial) }}" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="correo_emisor">Correo de Notificaciones</label>
                                <input class="form-control" id="correo_emisor" name="correo_emisor" type="email" value="{{ old('correo_emisor', $cfgDte->correo_emisor) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="direccion">Dirección Comercial</label>
                                <input class="form-control" id="direccion" name="direccion" type="text" value="{{ old('direccion', $cfgDte->direccion) }}" required />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="codigo_postal">Código Postal</label>
                                <input class="form-control" id="codigo_postal" name="codigo_postal" type="text" value="{{ old('codigo_postal', $cfgDte->codigo_postal) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="municipio">Municipio</label>
                                <input class="form-control" id="municipio" name="municipio" type="text" value="{{ old('municipio', $cfgDte->municipio) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="departamento">Departamento</label>
                                <input class="form-control" id="departamento" name="departamento" type="text" value="{{ old('departamento', $cfgDte->departamento) }}" required />
                            </div>

                            {{-- Sección 2: Configuración Dinámica de Frases SAT --}}
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <h5 class="text-primary mb-0"><span class="fa-solid fa-quote-left me-2"></span>Frases Obligatorias SAT (Tipo de Frase &amp; Escenario)</h5>
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="agregarFraseFila()">
                                        <span class="fa-solid fa-plus me-1"></span>Añadir Frase
                                    </button>
                                </div>
                                <p class="text-700 fs--2 mb-3">Defina los códigos de TipoFrase y CodigoEscenario según la normativa de la SAT para no tocar código en el futuro.</p>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle table-bordered" id="tablaFrases">
                                        <thead class="bg-body-secondary">
                                            <tr>
                                                <th style="width: 150px;">Tipo de Frase</th>
                                                <th style="width: 170px;">Código de Escenario</th>
                                                <th>Descripción / Texto de Referencia</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="cuerpoFrases">
                                            @php $frasesActuales = $cfgDte->obtenerFrases(); @endphp
                                            @foreach($frasesActuales as $idx => $fr)
                                                <tr class="fila-frase">
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm font-monospace text-center" name="frases[{{ $idx }}][tipo_frase]" value="{{ $fr['tipo_frase'] ?? '1' }}" placeholder="Ej: 1" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm font-monospace text-center" name="frases[{{ $idx }}][codigo_escenario]" value="{{ $fr['codigo_escenario'] ?? '1' }}" placeholder="Ej: 2" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="frases[{{ $idx }}][descripcion]" value="{{ $fr['descripcion'] ?? '' }}" placeholder="Ej: No genera derecho a crédito fiscal">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove()">
                                                            <span class="fa-solid fa-trash-can"></span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Sección 3: Tipos de Documentos y Operaciones Habilitadas --}}
                            <div class="col-12 mt-4">
                                <h5 class="text-primary border-bottom pb-2 mb-2"><span class="fa-solid fa-file-contract me-2"></span>Operaciones &amp; Documentos Especiales</h5>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" id="permite_anulacion_directa" name="permite_anulacion_directa" type="checkbox" value="1" @checked(old('permite_anulacion_directa', $cfgDte->permite_anulacion_directa)) />
                                            <label class="form-check-label fw-bold" for="permite_anulacion_directa">Anulación Directa FEL</label>
                                        </div>
                                        <div class="fs--2 text-muted">Permite anular DTEs certificados enviando motivo a INFILE</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" id="habilitar_notas_credito" name="habilitar_notas_credito" type="checkbox" value="1" @checked(old('habilitar_notas_credito', $cfgDte->habilitar_notas_credito)) />
                                            <label class="form-check-label fw-bold" for="habilitar_notas_credito">Notas de Crédito (NCRE)</label>
                                        </div>
                                        <div class="fs--2 text-muted">Para devoluciones o ajustes de precio</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" id="habilitar_notas_debito" name="habilitar_notas_debito" type="checkbox" value="1" @checked(old('habilitar_notas_debito', $cfgDte->habilitar_notas_debito)) />
                                            <label class="form-check-label fw-bold" for="habilitar_notas_debito">Notas de Débito (NDEB)</label>
                                        </div>
                                        <div class="fs--2 text-muted">Para recargos o cobros adicionales</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" id="habilitar_notas_abono" name="habilitar_notas_abono" type="checkbox" value="1" @checked(old('habilitar_notas_abono', $cfgDte->habilitar_notas_abono)) />
                                            <label class="form-check-label fw-bold" for="habilitar_notas_abono">Notas de Abono (NABN)</label>
                                        </div>
                                        <div class="fs--2 text-muted">Documentos especiales sin IVA para abonos</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sección 4: Credenciales WebService INFILE --}}
                            <div class="col-12 mt-4">
                                <h5 class="text-primary border-bottom pb-2 mb-1"><span class="fa-solid fa-key me-2"></span>Credenciales WebService (FEEL / INFILE)</h5>
                                <p class="text-600 fs--2 mb-3">Obtenidas en el portal de emisores de INFILE.</p>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="alias_ws">Alias WS (Usuario)</label>
                                <input class="form-control" id="alias_ws" name="alias_ws" type="text" value="{{ old('alias_ws', $cfgDte->alias_ws) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="token_signer">Token Signer (Firma)</label>
                                <input class="form-control" id="token_signer" name="token_signer" type="password" value="{{ old('token_signer', $cfgDte->token_signer) }}" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="llave_ws">Llave WS (Certificación)</label>
                                <input class="form-control" id="llave_ws" name="llave_ws" type="password" value="{{ old('llave_ws', $cfgDte->llave_ws) }}" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="url_firma">URL Firma</label>
                                <input class="form-control form-control-sm font-monospace" id="url_firma" name="url_firma" type="text" value="{{ old('url_firma', $cfgDte->url_firma) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="url_certificacion">URL Certificación</label>
                                <input class="form-control form-control-sm font-monospace" id="url_certificacion" name="url_certificacion" type="text" value="{{ old('url_certificacion', $cfgDte->url_certificacion) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="url_anulacion">URL Anulación</label>
                                <input class="form-control form-control-sm font-monospace" id="url_anulacion" name="url_anulacion" type="text" value="{{ old('url_anulacion', $cfgDte->url_anulacion) }}" required />
                            </div>

                            <div class="col-12 mt-4 text-end">
                                <button class="btn btn-primary px-4 py-2" type="submit">
                                    <span class="fa-solid fa-floppy-disk me-1"></span>Guardar Toda la Configuración
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <script>
        let idxFrase = {{ isset($frasesActuales) ? count($frasesActuales) : 2 }};
        function agregarFraseFila() {
            const cuerpo = document.getElementById('cuerpoFrases');
            const tr = document.createElement('tr');
            tr.className = 'fila-frase';
            tr.innerHTML = `
                <td>
                    <input type="text" class="form-control form-control-sm font-monospace text-center" name="frases[${idxFrase}][tipo_frase]" value="1" placeholder="Ej: 1" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm font-monospace text-center" name="frases[${idxFrase}][codigo_escenario]" value="1" placeholder="Ej: 1" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="frases[${idxFrase}][descripcion]" value="" placeholder="Descripción de la frase...">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove()">
                        <span class="fa-solid fa-trash-can"></span>
                    </button>
                </td>
            `;
            cuerpo.appendChild(tr);
            idxFrase++;
        }
    </script>
@endsection
