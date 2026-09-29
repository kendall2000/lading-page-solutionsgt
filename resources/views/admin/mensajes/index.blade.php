@extends('layouts.admin', ['titulo' => 'Solicitudes y mensajes'])

@section('contenido')
    <h2 class="mb-2 text-1100">Solicitudes y mensajes</h2>
    <p class="text-700 mb-4">Lo que llega por los formularios del sitio: mensajes, pedidos de demostración y de prueba.</p>

    @php $filtro = fn (array $cambios) => route('admin.mensajes.index', array_filter(array_merge(['estado' => $estado, 'tipo' => $tipo], $cambios))); @endphp
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-sm {{ $tipo ? 'btn-phoenix-secondary' : 'btn-primary' }}" href="{{ $filtro(['tipo' => null]) }}">Todo</a>
        @foreach (\App\Models\MensajeContacto::TIPOS as $clave => [$texto])
            <a class="btn btn-sm {{ $tipo === $clave ? 'btn-primary' : 'btn-phoenix-secondary' }}" href="{{ $filtro(['tipo' => $clave]) }}">{{ $texto }} <span class="opacity-75">({{ $conteoTipos[$clave] ?? 0 }})</span></a>
        @endforeach
    </div>

    <ul class="nav nav-links mb-3 mx-n2">
        <li class="nav-item"><a class="nav-link px-2 py-1 {{ $estado ? '' : 'active' }}" href="{{ $filtro(['estado' => null]) }}">Todos los estados <span class="text-700 fw-semi-bold">({{ $conteo->sum() }})</span></a></li>
        @foreach (\App\Models\MensajeContacto::ESTADOS as $clave => [$texto])
            <li class="nav-item"><a class="nav-link px-2 py-1 {{ $estado === $clave ? 'active' : '' }}" href="{{ $filtro(['estado' => $clave]) }}">{{ $texto }} <span class="text-700 fw-semi-bold">({{ $conteo[$clave] ?? 0 }})</span></a></li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-body">
            @include('admin.mensajes.tabla')
            <div class="mt-3">{{ $mensajes->links() }}</div>
        </div>
    </div>
@endsection
