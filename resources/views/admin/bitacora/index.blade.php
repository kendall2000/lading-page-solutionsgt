@extends('layouts.admin', ['titulo' => 'Bitácora de cambios'])

@use('App\Models\BitacoraCambio')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-2 text-1100">Bitácora de cambios</h2>
            <p class="text-700 mb-0">Quién creó, editó o borró qué en el panel, y cada entrada al panel. Las contraseñas y claves nunca se guardan.</p>
        </div>
        <a class="btn btn-phoenix-secondary" href="{{ route('admin.bitacora.exportar', array_filter($filtros)) }}"><span class="fa-solid fa-file-excel me-2"></span>Descargar para Excel</a>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.bitacora.index') }}">
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label fs--1 mb-1" for="f_usuario">Usuario</label>
                    <select class="form-select form-select-sm" id="f_usuario" name="usuario">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $id => $nombre)
                            <option value="{{ $id }}" @selected((string) ($filtros['usuario'] ?? '') === (string) $id)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label fs--1 mb-1" for="f_accion">Acción</label>
                    <select class="form-select form-select-sm" id="f_accion" name="accion">
                        <option value="">Todas</option>
                        @foreach (BitacoraCambio::ACCIONES as $clave => [$texto])
                            <option value="{{ $clave }}" @selected(($filtros['accion'] ?? '') === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label fs--1 mb-1" for="f_modulo">Módulo</label>
                    <select class="form-select form-select-sm" id="f_modulo" name="modulo">
                        <option value="">Todos</option>
                        @foreach ($modulos as $modulo)
                            <option value="{{ $modulo }}" @selected(($filtros['modulo'] ?? '') === $modulo)>{{ $modulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label fs--1 mb-1" for="f_desde">Desde</label>
                    <input class="form-control form-control-sm" id="f_desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}" />
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label fs--1 mb-1" for="f_hasta">Hasta</label>
                    <input class="form-control form-control-sm" id="f_hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}" />
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label fs--1 mb-1" for="f_buscar">Buscar</label>
                    <input class="form-control form-control-sm" id="f_buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Registro, usuario o IP" maxlength="100" />
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" type="submit"><span class="fa-solid fa-filter me-1"></span>Filtrar</button>
                    @if (array_filter($filtros))
                        <a class="btn btn-sm btn-phoenix-secondary" href="{{ route('admin.bitacora.index') }}">Quitar filtros</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($registros->isEmpty())
                <p class="text-700 mb-0">No hay movimientos con estos filtros.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr><th class="ps-0">Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Registro</th><th class="pe-0">IP</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($registros as $r)
                                @php [$accionTexto, $accionColor] = $r->accionInfo(); @endphp
                                <tr>
                                    <td class="ps-0 text-nowrap">{{ $r->created_at->format('d/m/Y H:i') }}<div class="text-600 fs--2">{{ $r->created_at->diffForHumans() }}</div></td>
                                    <td>{{ $r->usuario }}</td>
                                    <td><span class="badge badge-phoenix badge-phoenix-{{ $accionColor }}">{{ $accionTexto }}</span></td>
                                    <td>{{ $r->modulo }}</td>
                                    <td>
                                        {{ $r->descripcion }}
                                        @if ($r->cambios)
                                            <a class="d-block fs--2 mt-1" data-bs-toggle="collapse" href="#cambios{{ $r->id }}" role="button" aria-expanded="false">Ver {{ count($r->cambios) }} {{ count($r->cambios) === 1 ? 'cambio' : 'cambios' }}</a>
                                            <div class="collapse" id="cambios{{ $r->id }}">
                                                <table class="table table-sm table-bordered fs--2 mt-2 mb-0 bg-100">
                                                    <thead><tr><th>Campo</th><th>Antes</th><th>Después</th></tr></thead>
                                                    <tbody>
                                                        @foreach ($r->cambios as $campo => [$antes, $despues])
                                                            <tr>
                                                                <td class="fw-semi-bold">{{ $campo }}</td>
                                                                <td class="text-break" style="max-width: 18rem;">{{ is_bool($antes) ? ($antes ? 'Sí' : 'No') : ($antes ?? '—') }}</td>
                                                                <td class="text-break" style="max-width: 18rem;">{{ is_bool($despues) ? ($despues ? 'Sí' : 'No') : ($despues ?? '—') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="pe-0 text-600">{{ $r->ip }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $registros->links() }}</div>
            @endif
        </div>
    </div>
@endsection
