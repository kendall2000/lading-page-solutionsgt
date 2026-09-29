@extends('layouts.admin', ['titulo' => 'Mensajes'])

@section('contenido')
    <h2 class="mb-2 text-1100">Mensajes</h2>
    <p class="text-700 mb-4">Lo que te escriben desde el formulario de contacto del sitio.</p>

    <ul class="nav nav-links mb-3 mx-n2">
        <li class="nav-item"><a class="nav-link px-2 py-1 {{ $estado ? '' : 'active' }}" href="{{ route('admin.mensajes.index') }}">Todos <span class="text-700 fw-semi-bold">({{ $conteo->sum() }})</span></a></li>
        @foreach (\App\Models\MensajeContacto::ESTADOS as $clave => [$texto])
            <li class="nav-item"><a class="nav-link px-2 py-1 {{ $estado === $clave ? 'active' : '' }}" href="{{ route('admin.mensajes.index', ['estado' => $clave]) }}">{{ $texto }} <span class="text-700 fw-semi-bold">({{ $conteo[$clave] ?? 0 }})</span></a></li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-body">
            @include('admin.mensajes.tabla')
            <div class="mt-3">{{ $mensajes->links() }}</div>
        </div>
    </div>
@endsection
