@extends('layouts.admin', ['titulo' => 'Licencias Nexus'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Licencias Nexus</h2>
            <p class="text-700 mb-0">Instalaciones de Nexus ERP de tus clientes: vencimiento, pagos y etapas de mora. Cada instalación consulta aquí su licencia.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.licencias.create') }}"><span class="fa-solid fa-plus me-2"></span>Nueva instalación</a>
    </div>

    <ul class="nav nav-underline mb-3">
        @foreach (\App\Http\Controllers\Admin\LicenciaController::FILTROS as $clave => $texto)
            <li class="nav-item">
                <a class="nav-link {{ $filtro === $clave ? 'active' : '' }}" href="{{ route('admin.licencias.index', ['filtro' => $clave]) }}">
                    {{ $texto }} <span class="badge badge-phoenix badge-phoenix-{{ $clave === 'mora' && $conteo[$clave] ? 'danger' : ($clave === 'por_vencer' && $conteo[$clave] ? 'warning' : 'secondary') }} ms-1">{{ $conteo[$clave] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card mb-4">
        <div class="card-body">
            @if ($filas->isEmpty())
                <p class="text-700 mb-0">{{ $conteo['todas'] ? 'Ninguna instalación en este filtro.' : 'Todavía no hay instalaciones. Crea una por cada cliente con Nexus.' }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                        <tr>
                            <th class="ps-0">Cliente</th>
                            <th>Etapa</th>
                            <th>Pagado hasta</th>
                            <th>Última consulta</th>
                            <th>Versión</th>
                            <th class="text-end">Usuarios</th>
                            <th class="text-end pe-0"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($filas as $fila)
                            @php [$i, $e] = [$fila['i'], $fila['estado']]; [$etapaTexto, $etapaColor] = $i->etapaInfo($e['etapa']); @endphp
                            <tr>
                                <td class="ps-0">
                                    <a class="fw-bold text-1000" href="{{ route('admin.licencias.show', $i) }}">{{ $i->nombre }}</a>
                                    <div class="fs--2 text-700">{{ $i->identificador }} · {{ $i->tipo === 'venta' ? 'venta' : 'renta' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-phoenix badge-phoenix-{{ $etapaColor }}">{{ $etapaTexto }}</span>
                                    @if ($i->modo !== 'NORMAL')<div class="fs--2 text-700">{{ \App\Models\Instalacion::MODOS[$i->modo][0] }}</div>@endif
                                </td>
                                <td>
                                    {{ $e['vence_el']?->format('d/m/Y') ?? 'Sin vencimiento' }}
                                    @if ($e['dias'] !== null && $e['dias'] > 0)<div class="fs--2 text-danger">{{ $e['dias'] }} {{ $e['dias'] === 1 ? 'día' : 'días' }} de mora</div>@endif
                                </td>
                                <td class="{{ $i->sinComunicacion() ? 'text-danger' : '' }}">
                                    {{ $i->ultima_consulta_en?->diffForHumans() ?? 'Nunca' }}
                                </td>
                                <td>{{ $i->version ?? '—' }}</td>
                                <td class="text-end">{{ $i->usuarios_activos ?? '—' }}</td>
                                <td class="text-end pe-0"><a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.licencias.show', $i) }}">Abrir</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="mb-1">Configuración</h4>
            <p class="text-700 fs--1">Valores por defecto de todas las instalaciones (cada una puede tener los suyos). El contacto y el enlace de pago aparecen en el aviso de Nexus y en los correos.</p>
            <form method="POST" action="{{ route('admin.licencias.configuracion') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="contacto">Contacto para pagar</label>
                        <input class="form-control" id="contacto" name="contacto" value="{{ old('contacto', $cfgLic->contacto) }}" maxlength="255" placeholder="WhatsApp 5555-5555 · pagos@solutionsgt.com" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="enlace_pago">Enlace de pago</label>
                        <input class="form-control" id="enlace_pago" name="enlace_pago" type="url" value="{{ old('enlace_pago', $cfgLic->enlace_pago) }}" maxlength="500" placeholder="https://" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="dias_aviso">Días de aviso antes de vencer</label>
                        <input class="form-control" id="dias_aviso" name="dias_aviso" type="number" min="0" max="365" value="{{ old('dias_aviso', $cfgLic->dias_aviso) }}" required />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="dias_gracia">Días de gracia (todo funciona)</label>
                        <input class="form-control" id="dias_gracia" name="dias_gracia" type="number" min="0" max="365" value="{{ old('dias_gracia', $cfgLic->dias_gracia) }}" required />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="dias_restringido">Días restringido (luego, solo lectura)</label>
                        <input class="form-control" id="dias_restringido" name="dias_restringido" type="number" min="0" max="365" value="{{ old('dias_restringido', $cfgLic->dias_restringido) }}" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Clave pública del panel</label>
                        <input class="form-control font-monospace fs--1" value="{{ $cfgLic->clave_publica }}" readonly />
                        <div class="form-text">Va en el <code>.env</code> de cada Nexus como <code>NEXUS_LICENCIA_CLAVE_PUBLICA</code>. Con ella la instalación comprueba que la licencia la firmó este panel.</div>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Guardar configuración</button>
            </form>
        </div>
    </div>
@endsection
