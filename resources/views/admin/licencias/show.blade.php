@extends('layouts.admin', ['titulo' => $i->nombre])

@php
    [$etapaTexto, $etapaColor] = $i->etapaInfo($estado['etapa']);
    $f = fn ($d) => $d?->format('d/m/Y') ?? '—';
    $esRenta = $i->tipo === 'renta';
@endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.licencias.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Licencias Nexus</a>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $i->nombre }}</h2>
            <p class="text-700 mb-0">
                <span class="badge badge-phoenix badge-phoenix-{{ $etapaColor }} fs--1 me-1">{{ $etapaTexto }}</span>
                @if ($i->modo !== 'NORMAL')<span class="badge badge-phoenix badge-phoenix-{{ \App\Models\Instalacion::MODOS[$i->modo][1] }} fs--1 me-1">{{ \App\Models\Instalacion::MODOS[$i->modo][0] }}</span>@endif
                <span class="font-monospace">{{ $i->identificador }}</span> · <a href="{{ $i->urlBase() }}" target="_blank" rel="noopener noreferrer">{{ $i->urlBase() }}</a>
            </p>
        </div>
        <a class="btn btn-phoenix-primary" href="{{ route('admin.licencias.edit', $i) }}"><span class="fa-solid fa-pen me-2"></span>Editar</a>
    </div>

    @if ($token)
        <div class="card border border-warning mb-4">
            <div class="card-body">
                <h4 class="mb-1"><span class="fa-solid fa-key me-2 text-warning"></span>Configuración para su <code>.env</code></h4>
                <p class="text-700 fs--1">Cópiala ahora: <strong>el token no se vuelve a mostrar</strong>. Ponla en el <code>.env</code> de la instalación y reinicia su contenedor (<code>docker compose up -d --force-recreate</code>).</p>
                <pre class="bg-body-highlight p-3 rounded fs--1 mb-0 user-select-all">NEXUS_LICENCIA_URL={{ rtrim(config('app.url'), '/') }}
NEXUS_LICENCIA_ID={{ $i->identificador }}
NEXUS_LICENCIA_TOKEN={{ $token }}
NEXUS_LICENCIA_CLAVE_PUBLICA={{ $cfgLic->clave_publica }}</pre>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Licencia</h4>
                    <div class="row g-3 fs--1">
                        <div class="col-sm-6"><div class="text-700">Pagado hasta</div><div class="fw-bold fs-0">{{ $esRenta ? ($estado['vence_el'] ? $f($estado['vence_el']) : 'Sin vencimiento') : 'Venta: sin vencimiento' }}</div>
                            @if ($estado['dias'] !== null && $estado['dias'] > 0)<div class="text-danger">{{ $estado['dias'] }} {{ $estado['dias'] === 1 ? 'día' : 'días' }} de mora</div>@endif
                        </div>
                        <div class="col-sm-6"><div class="text-700">Siguiente etapa</div><div class="fw-bold fs-0">{{ $f($estado['siguiente']) }}</div></div>
                        <div class="col-sm-6"><div class="text-700">Pagos a mano hasta</div><div>{{ $f($i->vence_el) }}</div></div>
                        <div class="col-sm-6"><div class="text-700">Contrato (PayPal)</div>
                            <div>{{ $i->contrato ? ($i->contrato->hasta ? 'hasta '.$f($i->contrato->hasta) : 'sin vencimiento') : 'Sin contrato vinculado' }}</div>
                        </div>
                        <div class="col-sm-6"><div class="text-700">Prórroga</div><div>{{ $i->prorroga_hasta ? 'hasta '.$f($i->prorroga_hasta) : '—' }}</div></div>
                        <div class="col-sm-6"><div class="text-700">Días aviso / gracia / restringido</div><div>{{ $i->dias('aviso') }} / {{ $i->dias('gracia') }} / {{ $i->dias('restringido') }}</div></div>
                        <div class="col-sm-6"><div class="text-700">Facturar en «Restringido»</div><div>{{ $i->bloquear_facturacion ? 'No' : 'Sí' }}</div></div>
                        <div class="col-sm-6"><div class="text-700">Cobro acordado</div><div>{{ $i->monto !== null ? $i->moneda.' '.number_format((float) $i->monto, 2).' cada '.($i->meses_periodo === 1 ? 'mes' : $i->meses_periodo.' meses') : '—' }}</div></div>
                        @if ($i->notas)<div class="col-12"><div class="text-700">Notas</div><div style="white-space: pre-line">{{ $i->notas }}</div></div>@endif
                    </div>
                </div>
            </div>

            @if ($esRenta)
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-1">Registrar pago</h4>
                        <p class="text-700 fs--1">Corre el vencimiento los meses pagados <strong>desde el vencimiento anterior</strong> (quien paga tarde no gana días), quita una suspensión manual y avisa a la instalación: vuelve a la normalidad al instante.</p>
                        <form method="POST" action="{{ route('admin.licencias.pago', $i) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="pagado_el">Fecha del pago</label>
                                    <input class="form-control" id="pagado_el" name="pagado_el" type="date" value="{{ old('pagado_el', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="monto">Monto</label>
                                    <div class="input-group">
                                        <select class="form-select" name="moneda" style="max-width: 90px" aria-label="Moneda">
                                            @foreach (['GTQ', 'USD'] as $m)<option @selected(old('moneda', $i->moneda) === $m)>{{ $m }}</option>@endforeach
                                        </select>
                                        <input class="form-control" id="monto" name="monto" type="number" step="0.01" min="0" value="{{ old('monto', $i->monto !== null ? (float) $i->monto * 1 : '') }}" required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="meses">Meses que paga</label>
                                    <input class="form-control" id="meses" name="meses" type="number" min="1" max="36" value="{{ old('meses', $i->meses_periodo) }}" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="metodo">Método</label>
                                    <select class="form-select" id="metodo" name="metodo">
                                        @foreach (\App\Models\PagoLicencia::METODOS as $clave => $texto)<option value="{{ $clave }}" @selected(old('metodo') === $clave)>{{ $texto }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="referencia">Referencia / boleta</label>
                                    <input class="form-control" id="referencia" name="referencia" value="{{ old('referencia') }}" maxlength="120" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notas_pago">Notas</label>
                                    <input class="form-control" id="notas_pago" name="notas" value="{{ old('notas') }}" maxlength="500" />
                                </div>
                            </div>
                            <button class="btn btn-success mt-3" type="submit"><span class="fa-solid fa-check me-2"></span>Registrar pago</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Pagos registrados</h4>
                    @if ($i->pagos->isEmpty())
                        <p class="text-700 fs--1 mb-0">Sin pagos a mano.{{ $i->contrato ? ' Los cobros de PayPal están en Ventas → Suscripciones y pagos.' : '' }}</p>
                    @else
                        @php $ultimo = $i->pagos->firstWhere('anulado_en', null); @endphp
                        <div class="table-responsive">
                            <table class="table table-sm fs--1 mb-0 align-middle">
                                <thead><tr><th class="ps-0">Fecha</th><th>Monto</th><th>Método</th><th>Meses</th><th>Pagado hasta</th><th>Registró</th><th class="pe-0"></th></tr></thead>
                                <tbody>
                                @foreach ($i->pagos as $p)
                                    <tr class="{{ $p->anulado_en ? 'text-500 text-decoration-line-through' : '' }}">
                                        <td class="ps-0">{{ $f($p->pagado_el) }}</td>
                                        <td>{{ $p->montoTexto() }}</td>
                                        <td>{{ \App\Models\PagoLicencia::METODOS[$p->metodo] ?? $p->metodo }}@if ($p->referencia)<div class="fs--2 text-700">{{ $p->referencia }}</div>@endif</td>
                                        <td>{{ $p->meses }}</td>
                                        <td>{{ $f($p->vence_despues) }}</td>
                                        <td>{{ $p->usuario?->name ?? '—' }}</td>
                                        <td class="text-end pe-0">
                                            @if ($ultimo && $ultimo->id === $p->id)
                                                <form method="POST" action="{{ route('admin.licencias.pago.anular', $p) }}" onsubmit="return confirm('¿Anular este pago? El vencimiento vuelve al anterior ({{ $p->vence_antes?->format('d/m/Y') ?? 'sin fecha' }}).')">
                                                    @csrf
                                                    <button class="btn btn-link text-danger p-0 fs--1" type="submit">Anular</button>
                                                </form>
                                            @elseif ($p->anulado_en)
                                                <span class="fs--2">Anulado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            @if ($esRenta)
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Acciones</h4>
                        <form class="mb-3" method="POST" action="{{ route('admin.licencias.prorroga', $i) }}">
                            @csrf
                            <label class="form-label" for="dias">Dar prórroga («paga el viernes»)</label>
                            <div class="input-group">
                                <input class="form-control" id="dias" name="dias" type="number" min="1" max="90" value="7" required />
                                <span class="input-group-text">días</span>
                                <button class="btn btn-phoenix-primary" type="submit">Dar prórroga</button>
                            </div>
                            <div class="form-text">Desde hoy o desde el vencimiento, lo que sea después. No registra un pago.</div>
                        </form>
                        @if ($i->prorroga_hasta)
                            <form class="mb-3" method="POST" action="{{ route('admin.licencias.prorroga.quitar', $i) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-link p-0 fs--1" type="submit">Quitar la prórroga (hasta {{ $f($i->prorroga_hasta) }})</button>
                            </form>
                        @endif
                        <div class="d-flex flex-wrap gap-2">
                            @foreach (['SUSPENDIDO' => ['Suspender ya', 'danger', '¿Pasar la instalación a solo lectura ahora? Sus datos no se tocan.'], 'FORZAR_ACTIVO' => ['Forzar activo', 'info', '¿Dejarla activa pase lo que pase con el vencimiento?'], 'NORMAL' => ['Volver a normal', 'secondary', '¿Volver a aplicar las etapas según el vencimiento?']] as $modo => [$texto, $color, $pregunta])
                                @if ($i->modo !== $modo)
                                    <form method="POST" action="{{ route('admin.licencias.modo', $i) }}" onsubmit="return confirm('{{ $pregunta }}')">
                                        @csrf
                                        <input type="hidden" name="modo" value="{{ $modo }}" />
                                        <button class="btn btn-phoenix-{{ $color }} btn-sm" type="submit">{{ $texto }}</button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Conexión</h4>
                    <div class="row g-3 fs--1">
                        <div class="col-6"><div class="text-700">Última consulta</div>
                            <div class="{{ $i->sinComunicacion() ? 'text-danger fw-bold' : '' }}">{{ $i->ultima_consulta_en ? $i->ultima_consulta_en->format('d/m/Y H:i').' ('.$i->ultima_consulta_en->diffForHumans().')' : 'Nunca' }}</div>
                        </div>
                        <div class="col-6"><div class="text-700">Etapa que aplica</div><div>{{ $i->etapa_reportada ? $i->etapaInfo($i->etapa_reportada)[0] : '—' }}</div></div>
                        <div class="col-4"><div class="text-700">Versión</div><div>{{ $i->version ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-700">Usuarios activos</div><div>{{ $i->usuarios_activos ?? '—' }}</div></div>
                        <div class="col-4"><div class="text-700">Empresas</div><div>{{ $i->empresas_activas ?? '—' }}</div></div>
                        <div class="col-12"><div class="text-700">Último aviso «consulta ya»</div>
                            <div>{{ $i->avisado_en ? $i->avisado_en->format('d/m/Y H:i').' · '.$i->aviso_resultado : '—' }}</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <form method="POST" action="{{ route('admin.licencias.avisar', $i) }}">
                            @csrf
                            <button class="btn btn-phoenix-primary btn-sm" type="submit"><span class="fa-solid fa-rotate me-1"></span>Avisar ahora</button>
                        </form>
                        <form method="POST" action="{{ route('admin.licencias.token', $i) }}" onsubmit="return confirm('¿Generar un token nuevo? El actual deja de servir: hasta que pongas el nuevo en su .env, la instalación no podrá consultar (sigue con lo último que supo).')">
                            @csrf
                            <button class="btn btn-phoenix-secondary btn-sm" type="submit"><span class="fa-solid fa-key me-1"></span>Token nuevo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
