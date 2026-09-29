@extends('layouts.admin', ['titulo' => 'Usuarios'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Usuarios</h2>
            <p class="text-700 mb-0">Personas que pueden entrar a este panel.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.usuarios.create') }}"><span class="fa-solid fa-plus me-2"></span>Nuevo usuario</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead>
                    <tr>
                        <th class="ps-0">Nombre</th>
                        <th>Correo</th>
                        <th>Dos pasos</th>
                        <th>Último acceso</th>
                        <th>Estado</th>
                        <th class="text-end pe-0"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($usuarios as $u)
                        <tr>
                            <td class="ps-0 fw-bold">{{ $u->name }} @if ($u->is(auth()->user()))<span class="badge badge-phoenix badge-phoenix-primary ms-1">Tú</span>@endif</td>
                            <td>{{ $u->email }}</td>
                            <td>{!! $u->two_factor_confirmed_at ? '<span class="badge badge-phoenix badge-phoenix-success">Activa</span>' : '<span class="text-600">No</span>' !!}</td>
                            <td>{{ $u->ultimo_acceso?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $u->is_active ? 'success' : 'secondary' }}">{{ $u->is_active ? 'Activo' : 'Desactivado' }}</span></td>
                            <td class="text-end pe-0"><a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.usuarios.edit', $u) }}">Editar</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
