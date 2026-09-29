@extends('layouts.admin', ['titulo' => 'Clientes'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Clientes</h2>
            <p class="text-700 mb-0">Tus clientes, qué sistema usan y si su logo o testimonio se muestra en el sitio.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.clientes.create') }}"><span class="fa-solid fa-plus me-2"></span>Nuevo cliente</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form class="mb-3" method="GET" action="{{ route('admin.clientes.index') }}">
                <div class="search-box w-100" style="max-width: 360px">
                    <div class="position-relative">
                        <input class="form-control search-input" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por empresa, contacto o correo" aria-label="Buscar" />
                        <span class="fas fa-search search-box-icon"></span>
                    </div>
                </div>
            </form>
            @if ($clientes->isEmpty())
                <p class="text-700 mb-0">{{ $buscar !== '' ? 'Sin resultados.' : 'Todavía no hay clientes.' }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                        <tr>
                            <th class="ps-0">Empresa</th>
                            <th>Contacto</th>
                            <th>Sistema</th>
                            <th>En el sitio</th>
                            <th class="text-end pe-0"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($clientes as $c)
                            <tr>
                                <td class="ps-0">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-m me-2">
                                            @if ($c->logo)
                                                <img class="rounded-circle border border-200" src="{{ $c->url('logo') }}" alt="" style="object-fit: contain; background: #fff;" />
                                            @else
                                                <div class="avatar-name rounded-circle"><span>{{ $c->iniciales() }}</span></div>
                                            @endif
                                        </div>
                                        <a class="fw-bold text-1000" href="{{ route('admin.clientes.edit', $c) }}">{{ $c->empresa }}</a>
                                    </div>
                                </td>
                                <td>
                                    {{ $c->contacto ?: '—' }}
                                    <div class="fs--2 text-700">{{ collect([$c->telefono, $c->correo])->filter()->implode(' · ') }}</div>
                                </td>
                                <td>{{ $c->sistema?->nombre ?? '—' }}</td>
                                <td>
                                    @if ($c->mostrar_logo && $c->logo)<span class="badge badge-phoenix badge-phoenix-info">Logo</span>@endif
                                    @if ($c->mostrar_testimonio && $c->testimonio)<span class="badge badge-phoenix badge-phoenix-success">Testimonio</span>@endif
                                </td>
                                <td class="text-end pe-0"><a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.clientes.edit', $c) }}">Editar</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $clientes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
