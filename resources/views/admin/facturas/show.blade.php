@extends('layouts.admin', ['titulo' => $doc->nombreTipo() . ' ' . $doc->numero_interno])

@section('contenido')
    @php $ventaReciente = session('venta_exitosa'); @endphp

    {{-- Modal de Confirmación y Decisión Post-Venta (Estilo POS / Ventas) --}}
    @if ($ventaReciente)
        <div class="modal fade show d-block" id="modalPostVenta" tabindex="-1" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content border-0 shadow-lg text-center p-4 rounded-4">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-soft-success text-success" style="width: 64px; height: 64px;">
                        <span class="fa-solid fa-circle-check fs-4"></span>
                    </div>
                    <h3 class="fw-bolder text-1100 mb-1">¡Factura Registrada!</h3>
                    <p class="text-600 fs--1 mb-4 font-monospace">
                        Comprobante <strong>{{ $ventaReciente['numero'] }}</strong> &middot;
                        <strong class="text-primary">{{ $ventaReciente['moneda'] === 'GTQ' ? 'Q' : '$' }}{{ number_format($ventaReciente['total'], 2) }} {{ $ventaReciente['moneda'] }}</strong>
                    </p>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.facturas.documento.descargar-pdf', $doc) }}?formato=factura" class="btn btn-primary py-2 fw-bold">
                            <span class="fa-solid fa-file-arrow-down me-2"></span>Descargar Factura en PDF
                        </a>
                        <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=ticket" target="_blank" class="btn btn-phoenix-primary py-2">
                            <span class="fa-solid fa-receipt me-2"></span>Imprimir Ticket Térmico
                        </a>
                        <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=factura" target="_blank" class="btn btn-phoenix-secondary py-2">
                            <span class="fa-solid fa-eye me-2"></span>Ver Comprobante en Pantalla
                        </a>
                        <a href="{{ route('admin.facturas.create') }}" class="btn btn-outline-secondary py-2 mt-1">
                            <span class="fa-solid fa-arrow-right me-1"></span>Seguir Facturando
                        </a>
                        <button type="button" class="btn btn-link text-muted fs--2 p-0 mt-1" onclick="document.getElementById('modalPostVenta').remove()">
                            Cerrar y ver detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.facturas.index') }}">Facturas</a></li>
                    @if ($doc->origen)
                        <li class="breadcrumb-item"><a href="{{ route('admin.facturas.show', $doc->origen) }}">{{ $doc->origen->numero_interno }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ $doc->numero_interno }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h2 class="mb-0 text-1100">{{ $doc->nombreTipo() }} {{ $doc->numero_interno }}</h2>
                @if ($doc->estado === 'anulado')
                    <span class="badge badge-phoenix badge-phoenix-secondary fs--1">
                        <span class="fa-solid fa-ban me-1"></span>Anulada SAT
                    </span>
                @elseif ($doc->estado === 'certificado')
                    <span class="badge badge-phoenix badge-phoenix-success fs--1">
                        <span class="fa-solid fa-circle-check me-1"></span>Certificada SAT
                    </span>
                @elseif ($doc->estado === 'error')
                    <span class="badge badge-phoenix badge-phoenix-danger fs--1">
                        <span class="fa-solid fa-triangle-exclamation me-1"></span>Error de Certificación
                    </span>
                @else
                    <span class="badge badge-phoenix badge-phoenix-warning fs--1">
                        <span class="fa-solid fa-clock me-1"></span>Sin Certificar SAT
                    </span>
                @endif
                <span class="badge badge-phoenix badge-phoenix-primary fs--2">{{ $doc->moneda }}</span>
            </div>
            <p class="text-700 mb-0">Receptor: <strong>{{ $doc->nombre_receptor }}</strong> &middot; NIT: {{ $doc->nit_receptor ?: 'CF' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if (! in_array($doc->estado, ['certificado', 'anulado'], true))
                <form method="POST" action="{{ route('admin.facturas.certificar-sat', $doc) }}" onsubmit="return confirm('¿Enviar este documento a certificar ante la SAT / INFILE?')">
                    @csrf
                    <button type="submit" class="btn btn-phoenix-warning">
                        <span class="fa-solid fa-stamp me-1"></span>{{ $doc->estado === 'error' ? 'Reintentar Certificación' : 'Certificar en SAT' }}
                    </button>
                </form>
            @endif
            @php
                $puedeAnular = $doc->estaCertificado() && $cfgDte->permite_anulacion_directa;
                $notasHabilitadas = array_filter([
                    'NCRE' => $cfgDte->habilitar_notas_credito,
                    'NDEB' => $cfgDte->habilitar_notas_debito,
                    'NABN' => $cfgDte->habilitar_notas_abono,
                ]);
                $puedeNotas = $doc->esFactura() && $doc->estaCertificado() && $notasHabilitadas;
                $xmls = array_filter([
                    'generado' => $doc->xml_generado ? 'XML generado (enviado a firmar)' : null,
                    'firmado' => $doc->xml_firmado ? 'XML firmado' : null,
                    'certificado' => $doc->xml_certificado ? 'XML certificado por SAT' : null,
                    'anulacion' => $doc->xml_anulacion ? 'XML de anulación (firmado)' : null,
                ]);
            @endphp
            @if ($puedeNotas || $puedeAnular)
                <div class="btn-group">
                    <button type="button" class="btn btn-phoenix-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fa-solid fa-scale-balanced me-1"></span>Ajustes SAT
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if ($puedeNotas)
                            @foreach (\App\Models\DocumentoTributario::NOTAS as $tipoNota => $infoNota)
                                @if (isset($notasHabilitadas[$tipoNota]))
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalNota" data-tipo="{{ $tipoNota }}">
                                            <span class="fa-solid fa-file-circle-plus me-2"></span>{{ $infoNota['nombre'] }} ({{ $tipoNota }})
                                        </button>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                        @if ($puedeNotas && $puedeAnular)
                            <li><hr class="dropdown-divider"></li>
                        @endif
                        @if ($puedeAnular)
                            <li>
                                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#modalAnular">
                                    <span class="fa-solid fa-ban me-2"></span>Anular ante SAT
                                </button>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            @if ($xmls)
                <div class="btn-group">
                    <button type="button" class="btn btn-phoenix-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fa-solid fa-code me-1"></span>XML
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach ($xmls as $cual => $etiqueta)
                            <li class="d-flex align-items-center">
                                <a class="dropdown-item" href="{{ route('admin.facturas.xml', [$doc, $cual]) }}" target="_blank">
                                    <span class="fa-solid fa-eye me-2"></span>{{ $etiqueta }}
                                </a>
                                <a class="btn btn-link btn-sm me-2" href="{{ route('admin.facturas.xml', [$doc, $cual]) }}?descargar=1" title="Descargar">
                                    <span class="fa-solid fa-download"></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=factura" target="_blank" class="btn btn-phoenix-primary">
                <span class="fa-solid fa-file-invoice me-1"></span>Ver A4
            </a>
            <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=ticket" target="_blank" class="btn btn-phoenix-secondary">
                <span class="fa-solid fa-receipt me-1"></span>Ver Ticket
            </a>
            <a href="{{ route('admin.facturas.documento.descargar-pdf', $doc) }}?formato=factura" class="btn btn-phoenix-info">
                <span class="fa-solid fa-download me-1"></span>Descargar PDF
            </a>
            <a href="{{ route('admin.facturas.create') }}" class="btn btn-primary">
                <span class="fa-solid fa-plus me-1"></span>Nueva Factura
            </a>
        </div>
    </div>

    @if (session('exito'))
        <div class="alert alert-subtle-success alert-dismissible fade show mb-4 border-success" role="alert">
            <span class="fa-solid fa-circle-check me-2"></span>{{ session('exito') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error_sat') || $doc->error_mensaje)
        <div class="alert alert-subtle-danger alert-dismissible fade show mb-4 border-danger" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="fa-solid fa-triangle-exclamation text-danger fs-1 mt-1"></span>
                <div class="flex-grow-1">
                    <h6 class="text-danger fw-bold mb-1">Respuesta de FEL (INFILE / SAT):</h6>
                    @php
                        $textoError = (string) (session('error_sat') ?? $doc->error_mensaje);
                        $partes = preg_split('/\s+—\s+/u', $textoError, 2);
                        $detalles = isset($partes[1]) ? array_filter(array_map('trim', explode(' | ', $partes[1]))) : [];
                    @endphp
                    <div class="text-1000 fw-semi-bold">{{ $partes[0] }}</div>
                    @if ($detalles)
                        <ol class="mt-2 mb-0 ps-3 fs--1">
                            @foreach ($detalles as $detalle)
                                <li class="mb-1 text-break">{{ $detalle }}</li>
                            @endforeach
                        </ol>
                    @endif
                    @if (! $doc->estaCertificado() && ! $doc->estaAnulado())
                        <div class="mt-2 fs--2 text-700">
                            Puede corregir los datos o credenciales y presionar el botón <strong>"Reintentar Certificación"</strong> arriba.
                        </div>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-subtle-danger mb-4 border-danger" role="alert">
            @foreach ($errors->all() as $mensaje)
                <div><span class="fa-solid fa-circle-exclamation me-2"></span>{{ $mensaje }}</div>
            @endforeach
        </div>
    @endif

    @if ($doc->estado === 'anulado')
        <div class="alert alert-subtle-secondary mb-4 border-secondary">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <span class="fa-solid fa-ban text-secondary me-2 fs-1"></span>
                    <strong>Documento anulado ante la SAT</strong>
                    <span class="font-monospace ms-2">UUID: {{ $doc->uuid_sat }}</span>
                </div>
                <span class="fs--2 text-muted">Anulado el {{ $doc->fecha_anulacion ? $doc->fecha_anulacion->format('d/m/Y H:i') : '' }}</span>
            </div>
            @if ($doc->motivo_anulacion)
                <div class="mt-2 fs--1"><strong>Motivo:</strong> {{ $doc->motivo_anulacion }}</div>
            @endif
        </div>
    @endif

    @if ($doc->origen)
        <div class="alert alert-subtle-info mb-4 border-info fs--1">
            <span class="fa-solid fa-link me-2"></span>
            {{ $doc->nombreTipo() }} sobre la factura
            <a href="{{ route('admin.facturas.show', $doc->origen) }}" class="fw-bold">{{ $doc->origen->numero_interno }}</a>
            @if ($doc->origen->uuid_sat)
                <span class="ms-2 text-700">(Serie {{ $doc->origen->serie_sat }} · No. {{ $doc->origen->numero_sat }} · UUID <span class="font-monospace">{{ $doc->origen->uuid_sat }}</span>)</span>
            @endif
        </div>
    @endif

    @if ($doc->estado === 'certificado')
        <div class="alert alert-subtle-success mb-4 border-success d-flex align-items-center justify-content-between">
            <div>
                <span class="fa-solid fa-stamp text-success me-2 fs-1"></span>
                <strong class="text-success">Documento Autorizado por SAT:</strong>
                <span class="font-monospace ms-2">UUID: {{ $doc->uuid_sat }}</span>
                <span class="ms-3 text-700">Serie: <strong>{{ $doc->serie_sat }}</strong></span>
                <span class="ms-3 text-700">Número: <strong>{{ $doc->numero_sat }}</strong></span>
            </div>
            <span class="fs--2 text-muted">Certificado el {{ $doc->fecha_certificacion ? $doc->fecha_certificacion->format('d/m/Y H:i') : '' }}</span>
        </div>
    @endif

    <div class="row g-4 mb-4">
        {{-- Detalle de ítems facturados (Lo que se consumió) --}}
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-body-highlight py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-1100"><span class="fa-solid fa-box-open me-2 text-primary"></span>Conceptos y Servicios Consumidos ({{ $doc->items->count() }})</h5>
                    <span class="fs--2 text-600">Régimen Pequeño Contribuyente</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body-secondary">
                                <tr>
                                    <th style="width: 50px;" class="ps-3 text-center">#</th>
                                    <th style="width: 100px;">Tipo SAT</th>
                                    <th>Descripción detallada del consumo</th>
                                    <th class="text-center" style="width: 90px;">Cant.</th>
                                    <th class="text-end" style="width: 130px;">P. Unitario</th>
                                    <th class="text-end pe-3" style="width: 130px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doc->items as $it)
                                    <tr>
                                        <td class="ps-3 text-center text-600 font-monospace">{{ $it->numero_linea }}</td>
                                        <td>
                                            <span class="badge {{ $it->tipo === 'B' ? 'badge-phoenix-primary' : 'badge-phoenix-info' }} fs--2">
                                                {{ $it->tipo === 'B' ? 'Bien (B)' : 'Servicio (S)' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-1000">{!! nl2br(e($it->descripcion)) !!}</div>
                                        </td>
                                        <td class="text-center font-monospace">{{ number_format($it->cantidad, 2) }}</td>
                                        <td class="text-end font-monospace">
                                            {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($it->precio_unitario, 2) }}
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-primary">
                                            {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($it->subtotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($doc->notas)
                    <div class="card-footer bg-light py-2">
                        <div class="fs--2 text-700"><strong>Observaciones:</strong> {{ $doc->notas }}</div>
                    </div>
                @endif
            </div>

            @if ($doc->esFactura() && $doc->complementarios->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-body-highlight py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-1100"><span class="fa-solid fa-file-circle-plus me-2 text-danger"></span>Notas emitidas sobre esta factura ({{ $doc->complementarios->count() }})</h5>
                        <span class="fs--2 text-600">Saldo acreditable: <strong class="font-monospace">{{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($saldo, 2) }}</strong></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="bg-body-secondary">
                                    <tr>
                                        <th class="ps-3">Documento</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                        <th class="text-end">Monto</th>
                                        <th class="pe-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($doc->complementarios as $nota)
                                        <tr>
                                            <td class="ps-3 font-monospace fw-bold"><a href="{{ route('admin.facturas.show', $nota) }}">{{ $nota->numero_interno }}</a></td>
                                            <td>{{ $nota->nombreTipo() }}</td>
                                            <td class="text-700 fs--1">{{ $nota->notas }}</td>
                                            <td class="text-end font-monospace">{{ $nota->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($nota->total, 2) }}</td>
                                            <td class="pe-3">@include('admin.facturas.partes.estado', ['d' => $nota])</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Historial de Logs y Auditoría SAT / INFILE para este Documento --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-body-highlight py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-1100">
                        <span class="fa-solid fa-clock-rotate-left me-2 text-info"></span>Historial de Comunicación SAT / INFILE ({{ $doc->logs->count() }})
                    </h5>
                    <span class="fs--2 text-muted">Auditoría JSON / XML</span>
                </div>
                <div class="card-body p-0">
                    @if($doc->logs->isEmpty())
                        <div class="p-4 text-center text-muted fs--1">
                            <span class="fa-solid fa-file-circle-check fa-2x mb-2 text-300 d-block"></span>
                            No hay registros de llamadas a INFILE / SAT aún para esta factura.
                        </div>
                    @else
                        <div class="accordion accordion-flush" id="accordionLogsDoc">
                            @foreach($doc->logs->sortByDesc('id') as $l)
                                <div class="accordion-item border-bottom">
                                    <h2 class="accordion-header" id="headingLog{{ $l->id }}">
                                        <button class="accordion-button collapsed py-2 px-3 fs--1" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLog{{ $l->id }}">
                                            <div class="d-flex align-items-center flex-wrap gap-2 w-100">
                                                <span class="badge {{ $l->exito ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $l->exito ? 'ÉXITO' : 'RECHAZO' }} (HTTP {{ $l->codigo_http ?: 'ERR' }})
                                                </span>
                                                <span class="badge badge-phoenix badge-phoenix-secondary">{{ $l->nombreAccion() }}</span>
                                                @if ($l->resumenError())
                                                    <span class="text-danger fs--2 text-truncate" style="max-width: 420px;" title="{{ $l->resumenError() }}">{{ $l->resumenError() }}</span>
                                                @endif
                                                <span class="text-600 font-monospace">{{ $l->created_at ? $l->created_at->format('d/m/Y H:i:s') : '—' }}</span>
                                                <span class="text-muted ms-auto pe-3 font-monospace fs--2">{{ $l->duracion_ms ? $l->duracion_ms . ' ms' : '' }}</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapseLog{{ $l->id }}" class="accordion-collapse collapse" data-bs-parent="#accordionLogsDoc">
                                        <div class="accordion-body p-3">
                                            @include('admin.facturacion.partes.log-detalle', ['log' => $l])
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel lateral: Datos del Cliente, Totales y QR --}}
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-body-highlight py-3 border-bottom">
                    <h5 class="mb-0 text-1100"><span class="fa-solid fa-address-card me-2 text-primary"></span>Datos del Receptor</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="text-600 fs--2 d-block">Razón Social:</span>
                        <div class="fw-bold text-1000">{{ $doc->nombre_receptor }}</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-600 fs--2 d-block">NIT / Documento:</span>
                        <div class="font-monospace fw-bold text-primary">{{ $doc->nit_receptor ?: 'CF' }}</div>
                    </div>
                    @if($doc->correo_receptor)
                        <div class="mb-2">
                            <span class="text-600 fs--2 d-block">Correo:</span>
                            <div>{{ $doc->correo_receptor }}</div>
                        </div>
                    @endif
                    @if($doc->direccion_receptor)
                        <div class="mb-2">
                            <span class="text-600 fs--2 d-block">Dirección:</span>
                            <div>{{ $doc->direccion_receptor }}</div>
                        </div>
                    @endif
                    <hr>
                    <div class="mb-2">
                        <span class="text-600 fs--2 d-block">Condición &amp; Método:</span>
                        <div>{{ ucfirst($doc->condicion_pago) }} &middot; {{ ucfirst($doc->metodo_pago) }}</div>
                    </div>
                    <div>
                        <span class="text-600 fs--2 d-block">Fecha de Emisión:</span>
                        <div>{{ $doc->fecha_emision ? $doc->fecha_emision->format('d/m/Y H:i') : date('d/m/Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Resumen Económico --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 bg-body-highlight rounded-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-700">Subtotal Ítems:</span>
                        <span class="font-monospace fw-bold">{{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($doc->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 fs--1 text-muted">
                        <span>Desglose IVA:</span>
                        <span>0.00 (Régimen PEQ)</span>
                    </div>
                    <div class="border-top my-2"></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-1 fw-bold text-1100">TOTAL:</span>
                        <span class="fs-3 fw-bolder text-primary font-monospace">
                            {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($doc->total, 2) }} {{ $doc->moneda }}
                        </span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.facturas.documento.imprimir', $doc) }}?formato=factura" target="_blank" class="btn btn-primary">
                            <span class="fa-solid fa-print me-1"></span>Imprimir Documento
                        </a>
                        <a href="{{ route('admin.facturas.create') }}" class="btn btn-outline-secondary">
                            <span class="fa-solid fa-plus me-1"></span>Seguir Facturando
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($puedeAnular)
        <div class="modal fade" id="modalAnular" tabindex="-1" aria-labelledby="modalAnularTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.facturas.anular', $doc) }}" class="modal-content" onsubmit="this.querySelector('[type=submit]').disabled = true">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAnularTitulo"><span class="fa-solid fa-ban text-danger me-2"></span>Anular {{ $doc->nombreTipo() }} {{ $doc->numero_interno }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-subtle-warning fs--1 mb-3">
                            La anulación se envía a la SAT y <strong>no se puede deshacer</strong>. El UUID
                            <span class="font-monospace">{{ $doc->uuid_sat }}</span> quedará sin validez.
                        </div>
                        <label class="form-label" for="motivoAnulacion">Motivo de la anulación</label>
                        <textarea class="form-control" id="motivoAnulacion" name="motivo" rows="3" minlength="5" maxlength="255" required placeholder="Ej.: Error en los datos del receptor">{{ old('motivo') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><span class="fa-solid fa-ban me-1"></span>Anular ante SAT</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($puedeNotas)
        @php $simbolo = $doc->moneda === 'GTQ' ? 'Q' : '$'; @endphp
        <div class="modal fade" id="modalNota" tabindex="-1" aria-labelledby="modalNotaTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.facturas.notas', $doc) }}" class="modal-content" onsubmit="this.querySelector('[type=submit]').disabled = true">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNotaTitulo"><span class="fa-solid fa-file-circle-plus text-primary me-2"></span>Nueva nota sobre {{ $doc->numero_interno }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="tipoNota">Tipo de documento</label>
                            <select class="form-select" id="tipoNota" name="tipo" required>
                                @foreach (\App\Models\DocumentoTributario::NOTAS as $tipoNota => $infoNota)
                                    @if (isset($notasHabilitadas[$tipoNota]))
                                        <option value="{{ $tipoNota }}" @selected(old('tipo') === $tipoNota)>{{ $infoNota['nombre'] }} ({{ $tipoNota }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text" id="ayudaNota"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="montoNota">Monto ({{ $doc->moneda }})</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $simbolo }}</span>
                                <input type="number" class="form-control font-monospace" id="montoNota" name="monto" step="0.01" min="0.01" required value="{{ old('monto', number_format($saldo, 2, '.', '')) }}" data-saldo="{{ number_format($saldo, 2, '.', '') }}">
                            </div>
                            <div class="form-text">Total de la factura: {{ $simbolo }}{{ number_format($doc->total, 2) }} · saldo que aún se puede rebajar: {{ $simbolo }}{{ number_format($saldo, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="motivoNota">Motivo del ajuste</label>
                            <input type="text" class="form-control" id="motivoNota" name="motivo" minlength="5" maxlength="255" required value="{{ old('motivo') }}" placeholder="Ej.: Descuento acordado con el cliente">
                        </div>
                        <div>
                            <label class="form-label" for="descripcionNota">Descripción de la línea <span class="text-muted">(opcional)</span></label>
                            <input type="text" class="form-control" id="descripcionNota" name="descripcion" maxlength="500" value="{{ old('descripcion') }}" placeholder="Por defecto: «Nota de … - Factura serie número»">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><span class="fa-solid fa-stamp me-1"></span>Emitir y certificar</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function () {
                var modal = document.getElementById('modalNota');
                var tipo = document.getElementById('tipoNota');
                var monto = document.getElementById('montoNota');
                var ayuda = document.getElementById('ayudaNota');
                var textos = {
                    NCRE: 'Rebaja o devuelve parte del valor de la factura. No puede superar el saldo.',
                    NDEB: 'Cargo adicional al cliente sobre la misma factura (aumenta lo que debe).',
                    NABN: 'Abono sin IVA ni frases. No puede superar el saldo.'
                };
                function actualizar() {
                    ayuda.textContent = textos[tipo.value] || '';
                    if (tipo.value === 'NDEB') { monto.removeAttribute('max'); } else { monto.max = monto.dataset.saldo; }
                }
                modal.addEventListener('show.bs.modal', function (e) {
                    var boton = e.relatedTarget;
                    if (boton && boton.dataset.tipo) { tipo.value = boton.dataset.tipo; }
                    actualizar();
                });
                tipo.addEventListener('change', actualizar);
            })();
        </script>
    @endif
@endsection
