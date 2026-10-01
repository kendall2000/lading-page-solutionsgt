@extends('layouts.admin', ['titulo' => 'Suscripciones y pagos'])

@use('App\Models\ConfiguracionPagos')
@use('App\Models\Pago')
@use('App\Models\Precio')
@use('App\Models\Suscripcion')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Suscripciones y pagos</h2>
            <p class="text-700 mb-0">Cobros con PayPal. Cada estado se confirma con PayPal antes de guardarlo.</p>
        </div>
        @if ($cfg->cobrando())
            <span class="badge badge-phoenix badge-phoenix-{{ $cfg->modo === 'live' ? 'success' : 'warning' }} fs--1">
                Cobros encendidos · {{ $cfg->modo === 'live' ? 'dinero real' : 'modo de pruebas' }}
            </span>
        @else
            <span class="badge badge-phoenix badge-phoenix-secondary fs--1">Cobros en línea apagados</span>
        @endif
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Cobrado este mes', Precio::formato($resumen['mes'], $cfg->moneda), 'Mes anterior: '.Precio::formato($resumen['mesAnterior'], $cfg->moneda), 'dollar-sign', 'success'],
            ['Suscripciones activas', $resumen['activas'], ($conteos['suspendida'] ?? 0).' suspendidas por falta de pago', 'repeat', 'primary'],
            ['Ingreso mensual recurrente', Precio::formato($resumen['mensual'], $cfg->moneda), 'Lo que entra al mes con las activas', 'trending-up', 'info'],
        ] as [$tituloTarjeta, $valor, $nota, $icono, $color])
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-soft-{{ $color }} text-{{ $color }}" style="width:48px;height:48px"><span data-feather="{{ $icono }}"></span></span>
                    <div>
                        <p class="fs--1 text-700 mb-0">{{ $tituloTarjeta }}</p>
                        <h3 class="mb-0">{{ $valor }}</h3>
                        <p class="fs--2 text-600 mb-0">{{ $nota }}</p>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    <ul class="nav nav-underline mb-4" role="tablist">
        @foreach (['suscripciones' => ['repeat', 'Suscripciones'], 'pagos' => ['dollar-sign', 'Pagos'], 'paypal' => ['link', 'Conexión con PayPal']] as $id => [$icono, $texto])
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $pestana === $id ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $id }}" role="tab"><span class="me-1" data-feather="{{ $icono }}" style="width: 16px; height: 16px;"></span>{{ $texto }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content mb-9">
        {{-- Suscripciones --}}
        <div class="tab-pane fade {{ $pestana === 'suscripciones' ? 'show active' : '' }}" id="tab-suscripciones" role="tabpanel">
            <ul class="nav nav-links mb-3 mx-n3">
                <li class="nav-item"><a class="nav-link {{ $estadoSus === null ? 'active' : '' }}" href="{{ route('admin.ventas.index', ['ver' => 'suscripciones']) }}">Todas <span class="text-700 fw-semi-bold">({{ $conteos->sum() }})</span></a></li>
                @foreach (Suscripcion::ESTADOS as $clave => [$texto])
                    <li class="nav-item"><a class="nav-link {{ $estadoSus === $clave ? 'active' : '' }}" href="{{ route('admin.ventas.index', ['ver' => 'suscripciones', 'estado' => $clave]) }}">{{ $texto }} <span class="text-700 fw-semi-bold">({{ $conteos[$clave] ?? 0 }})</span></a></li>
                @endforeach
            </ul>
            <div class="card"><div class="card-body">
                @include('admin.ventas.buscar', ['ver' => 'suscripciones'])
                @if ($suscripciones->isEmpty())
                    <p class="text-700 mb-0">Todavía no hay suscripciones{{ $estadoSus || $buscar !== '' ? ' con ese filtro' : '' }}.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th class="ps-0">Cliente</th><th>Producto</th><th class="text-end">Monto</th><th>Estado</th><th>Próximo cobro</th><th>Acceso hasta</th><th class="pe-0"></th></tr></thead>
                            <tbody>
                                @foreach ($suscripciones as $s)
                                    @php [$estadoTexto, $estadoColor] = $s->estadoInfo(); @endphp
                                    <tr>
                                        <td class="ps-0">
                                            @if ($s->cuenta)
                                                <a class="fw-semi-bold text-900" href="{{ route('admin.cuentas.edit', $s->cuenta) }}">{{ $s->cuenta->nombre }}</a>
                                            @else
                                                <span class="fw-semi-bold">Cuenta borrada</span>
                                            @endif
                                            <div class="text-600">{{ $s->correo }}</div>
                                        </td>
                                        <td>{{ $s->descripcion }}@if ($s->paypal_modo !== 'live')<span class="badge badge-phoenix badge-phoenix-warning ms-1">prueba</span>@endif<div class="text-600 fs--2">{{ $s->paypal_id }}</div></td>
                                        <td class="text-end text-nowrap">{{ $s->montoTexto() }}</td>
                                        <td><span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span></td>
                                        <td class="text-nowrap">{{ $s->siguiente_cobro?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $s->contrato?->hasta?->format('d/m/Y') ?? ($s->contrato ? 'Sin vencimiento' : '—') }}</td>
                                        <td class="text-end pe-0 text-nowrap">
                                            <form class="d-inline" method="POST" action="{{ route('admin.suscripciones.sincronizar', $s) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-phoenix-secondary" type="submit" title="Traer de PayPal el estado y los cobros"><span class="fa-solid fa-rotate"></span></button>
                                            </form>
                                            @if ($s->sePuedeCancelar())
                                                <form class="d-inline" method="POST" action="{{ route('admin.suscripciones.cancelar', $s) }}" onsubmit="return confirm('¿Cancelar esta suscripción en PayPal? Ya no se le cobrará; el acceso sigue hasta el final del periodo pagado.')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-phoenix-danger" type="submit">Cancelar</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $suscripciones->links() }}</div>
                @endif
            </div></div>
        </div>

        {{-- Pagos --}}
        <div class="tab-pane fade {{ $pestana === 'pagos' ? 'show active' : '' }}" id="tab-pagos" role="tabpanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <ul class="nav nav-links mx-n3">
                    <li class="nav-item"><a class="nav-link {{ $estadoPago === null ? 'active' : '' }}" href="{{ route('admin.ventas.index', ['ver' => 'pagos']) }}">Todos</a></li>
                    @foreach (Pago::ESTADOS as $clave => [$texto])
                        <li class="nav-item"><a class="nav-link {{ $estadoPago === $clave ? 'active' : '' }}" href="{{ route('admin.ventas.index', ['ver' => 'pagos', 'pago' => $clave]) }}">{{ $texto }}</a></li>
                    @endforeach
                </ul>
                <form class="d-flex flex-wrap align-items-center gap-2" method="GET" action="{{ route('admin.ventas.exportar') }}">
                    <input class="form-control form-control-sm" type="date" name="desde" aria-label="Desde" style="width: 150px" />
                    <input class="form-control form-control-sm" type="date" name="hasta" aria-label="Hasta" style="width: 150px" />
                    <button class="btn btn-sm btn-phoenix-secondary" type="submit"><span class="fa-solid fa-file-excel me-1"></span>Descargar para Excel</button>
                </form>
            </div>
            <div class="card"><div class="card-body">
                @include('admin.ventas.buscar', ['ver' => 'pagos'])
                @if ($pagos->isEmpty())
                    <p class="text-700 mb-0">Todavía no hay pagos{{ $estadoPago || $buscar !== '' ? ' con ese filtro' : '' }}.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th class="ps-0">Fecha</th><th>Cliente</th><th>Producto</th><th>Tipo</th><th class="text-end">Monto</th><th>Estado</th><th class="pe-0">Referencia PayPal</th></tr></thead>
                            <tbody>
                                @foreach ($pagos as $p)
                                    @php [$estadoTexto, $estadoColor] = $p->estadoInfo(); @endphp
                                    <tr>
                                        <td class="ps-0 text-nowrap">{{ ($p->pagado_en ?? $p->created_at)->format('d/m/Y H:i') }}</td>
                                        <td>{{ $p->cuenta?->nombre ?? '—' }}<div class="text-600">{{ $p->correo }}</div></td>
                                        <td>{{ $p->descripcion }}@if ($p->paypal_modo !== 'live')<span class="badge badge-phoenix badge-phoenix-warning ms-1">prueba</span>@endif</td>
                                        <td>{{ $p->suscripcion_id ? 'Suscripción' : 'Pago único' }}</td>
                                        <td class="text-end text-nowrap fw-semi-bold">{{ $p->montoTexto() }}</td>
                                        <td><span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span></td>
                                        <td class="pe-0 text-600">{{ $p->paypal_id ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $pagos->links() }}</div>
                @endif
            </div></div>
        </div>

        {{-- Conexión con PayPal --}}
        <div class="tab-pane fade {{ $pestana === 'paypal' ? 'show active' : '' }}" id="tab-paypal" role="tabpanel">
            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <form class="card" method="POST" action="{{ route('admin.ventas.paypal') }}">
                        @csrf
                        <div class="card-body">
                            <div class="d-flex flex-between-center mb-3">
                                <h4 class="card-title mb-0">PayPal</h4>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked(old('activo', $cfg->activo)) />
                                    <label class="form-check-label fw-semi-bold" for="activo">Cobrar en línea</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="modo">Modo</label>
                                    <select class="form-select" id="modo" name="modo">
                                        @foreach (ConfiguracionPagos::MODOS as $clave => $texto)
                                            <option value="{{ $clave }}" @selected(old('modo', $cfg->modo) === $clave)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Prueba primero en «Pruebas» con cuentas sandbox; luego cambia a «Cobros reales» con las credenciales Live.</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="moneda">Moneda</label>
                                    <select class="form-select" id="moneda" name="moneda">
                                        @foreach (['USD' => 'Dólares (USD)', 'EUR' => 'Euros (EUR)', 'MXN' => 'Pesos MX (MXN)', 'CAD' => 'Dólares CA (CAD)'] as $clave => $texto)
                                            <option value="{{ $clave }}" @selected(old('moneda', $cfg->moneda) === $clave)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="dias_gracia">Días de gracia</label>
                                    <input class="form-control" id="dias_gracia" name="dias_gracia" type="number" min="0" max="30" value="{{ old('dias_gracia', $cfg->dias_gracia) }}" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="client_id">Client ID</label>
                                    <input class="form-control" id="client_id" name="client_id" maxlength="200" value="{{ old('client_id', $cfg->client_id) }}" autocomplete="off" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="client_secret">Secret</label>
                                    <input class="form-control" id="client_secret" name="client_secret" type="password" maxlength="200" autocomplete="new-password"
                                           placeholder="{{ $cfg->getRawOriginal('client_secret') ? '•••••••• (guardado; vacío = no cambia)' : 'Secret de la app de PayPal' }}" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="webhook_id">Webhook ID <span class="text-600 fw-normal">(recomendado)</span></label>
                                    <input class="form-control" id="webhook_id" name="webhook_id" maxlength="60" value="{{ old('webhook_id', $cfg->webhook_id) }}" autocomplete="off" />
                                    <div class="form-text">Con el webhook, los cobros mensuales, cancelaciones y reembolsos llegan al instante. Sin él, se revisan cada 6 horas.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="texto_asesor">Mensaje para «De por vida»</label>
                                    <input class="form-control" id="texto_asesor" name="texto_asesor" maxlength="300" value="{{ old('texto_asesor', $cfg->texto_asesor) }}"
                                           placeholder="Hola, me interesa la licencia de por vida de {producto}." />
                                    <div class="form-text">Es el mensaje con que se abre WhatsApp (o el formulario) al pedir una licencia de por vida. {producto} se cambia por el nombre.</div>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-4" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>
                <div class="col-12 col-xl-4">
                    <form class="card mb-3" method="POST" action="{{ route('admin.ventas.probar') }}">
                        @csrf
                        <div class="card-body">
                            <h4 class="card-title mb-2">Probar conexión</h4>
                            <p class="fs--1 text-700 mb-3">
                                @if ($cfg->probado_en)
                                    <span class="fa-solid fa-circle-check text-success me-1"></span>Funcionó el {{ $cfg->probado_en->format('d/m/Y H:i') }}.
                                @else
                                    Guarda primero; luego prueba que PayPal acepte las credenciales.
                                @endif
                            </p>
                            <button class="btn btn-phoenix-primary w-100" type="submit" @disabled(! $cfg->tieneCredenciales())><span class="fa-solid fa-plug me-2"></span>Probar</button>
                        </div>
                    </form>
                    <div class="card">
                        <div class="card-body fs--1 text-700">
                            <h5 class="mb-2 text-1000">Cómo conectarlo</h5>
                            <ol class="ps-3 mb-3">
                                <li>Entra a <strong>developer.paypal.com</strong> con tu cuenta PayPal Business → <em>Apps &amp; Credentials</em>.</li>
                                <li>Crea una app (primero en <em>Sandbox</em>) y copia el <strong>Client ID</strong> y el <strong>Secret</strong>.</li>
                                <li>En la app, agrega un <strong>Webhook</strong> con esta dirección y elige «All events»:</li>
                            </ol>
                            <input class="form-control form-control-sm mb-2" readonly value="{{ route('paypal.aviso') }}" onclick="this.select()" aria-label="Dirección del webhook" />
                            <p class="mb-0">Copia el <strong>Webhook ID</strong> que te da PayPal y pégalo aquí. El Secret se guarda cifrado y no se vuelve a mostrar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
