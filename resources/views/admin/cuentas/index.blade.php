@extends('layouts.admin', ['titulo' => 'Cuentas de clientes'])

@use('App\Http\Controllers\Admin\CuentaClienteController')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Cuentas de clientes</h2>
            <p class="text-700 mb-0">Personas que entran a «Mi cuenta» en el sitio: ven sus sistemas, pruebas, solicitudes, chats y manuales privados. {{ $totales['activas'] }} activas de {{ $totales['todas'] }}.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.cuentas.create') }}"><span class="fa-solid fa-user-plus me-2"></span>Invitar cliente</a>
    </div>

    <ul class="nav nav-links mb-3 mx-n2">
        <li class="nav-item"><a class="nav-link px-2 py-1 {{ $filtro ? '' : 'active' }}" href="{{ route('admin.cuentas.index', array_filter(['buscar' => $buscar])) }}">Todas</a></li>
        @foreach (CuentaClienteController::FILTROS as $clave => $texto)
            <li class="nav-item"><a class="nav-link px-2 py-1 {{ $filtro === $clave ? 'active' : '' }}" href="{{ route('admin.cuentas.index', array_filter(['estado' => $clave, 'buscar' => $buscar])) }}">{{ $texto }}</a></li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-body">
            <form class="mb-3" method="GET" action="{{ route('admin.cuentas.index') }}">
                @if ($filtro)<input type="hidden" name="estado" value="{{ $filtro }}" />@endif
                <div class="search-box w-100" style="max-width: 360px">
                    <div class="position-relative">
                        <input class="form-control search-input" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, correo o empresa" aria-label="Buscar" />
                        <span class="fas fa-search search-box-icon"></span>
                    </div>
                </div>
            </form>
            @if ($cuentas->isEmpty())
                <p class="text-700 mb-0">{{ $buscar !== '' || $filtro ? 'Sin resultados.' : 'Todavía no hay cuentas. Se crean cuando alguien se registra en el sitio o cuando invitas a un cliente.' }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr><th class="ps-0">Cliente</th><th>Empresa</th><th>Estado</th><th class="text-end">Chats</th><th class="text-end">Solicitudes</th><th>Último acceso</th><th class="pe-0"></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($cuentas as $c)
                                @php [$estadoTexto, $estadoColor] = $c->estadoInfo(); @endphp
                                <tr>
                                    <td class="ps-0">
                                        <a class="fw-semi-bold text-900" href="{{ route('admin.cuentas.edit', $c) }}">{{ $c->nombre }}</a>
                                        <div class="text-600">{{ $c->correo }}</div>
                                    </td>
                                    <td>
                                        @if ($c->cliente)
                                            <a href="{{ route('admin.clientes.edit', $c->cliente) }}"><span class="fa-solid fa-building me-1 text-600"></span>{{ $c->cliente->empresa }}</a>
                                        @else
                                            {{ $c->empresa ?: '—' }}
                                        @endif
                                    </td>
                                    <td><span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span></td>
                                    <td class="text-end">{{ $c->conversaciones_count }}</td>
                                    <td class="text-end">{{ $c->solicitudes_count }}</td>
                                    <td>{{ $c->ultimo_acceso?->diffForHumans() ?? 'Nunca' }}</td>
                                    <td class="text-end pe-0"><a class="btn btn-sm btn-phoenix-secondary" href="{{ route('admin.cuentas.edit', $c) }}">Abrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $cuentas->links() }}</div>
            @endif
        </div>
    </div>
@endsection
