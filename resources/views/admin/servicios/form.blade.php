@extends('layouts.admin', ['titulo' => $servicio->exists ? $servicio->nombre : 'Nuevo servicio'])

@php $v = fn ($c) => old($c, $servicio->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.servicios.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Servicios</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $servicio->exists ? $servicio->nombre : 'Nuevo servicio' }}</h2>

    <div class="card" style="max-width: 820px">
        <div class="card-body">
            <form method="POST" action="{{ $servicio->exists ? route('admin.servicios.update', $servicio) : route('admin.servicios.store') }}">
                @csrf
                @if ($servicio->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nombre">Nombre *</label>
                        <input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="120" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="precio">Precio</label>
                        <input class="form-control" id="precio" name="precio" value="{{ $v('precio') }}" maxlength="60" placeholder="Desde Q1,500" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="periodo">Periodo</label>
                        <input class="form-control" id="periodo" name="periodo" value="{{ $v('periodo') }}" maxlength="40" placeholder="/ mes" />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="descripcion">Descripción corta</label>
                        <input class="form-control" id="descripcion" name="descripcion" value="{{ $v('descripcion') }}" maxlength="300" />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="incluye">Qué incluye</label>
                        <textarea class="form-control" id="incluye" name="incluye" rows="7" maxlength="3000" placeholder="Uno por línea">{{ $v('incluye') }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="orden">Orden</label>
                        <input class="form-control" id="orden" name="orden" type="number" min="0" value="{{ $v('orden') }}" />
                    </div>
                    <div class="col-md-9 d-flex align-items-end gap-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="visible" name="visible" type="checkbox" value="1" @checked(old('visible', $servicio->visible)) />
                            <label class="form-check-label" for="visible">Visible</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="destacado" name="destacado" type="checkbox" value="1" @checked(old('destacado', $servicio->destacado)) />
                            <label class="form-check-label" for="destacado">Destacado (recuadro de color)</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button class="btn btn-primary" type="submit">{{ $servicio->exists ? 'Guardar cambios' : 'Crear servicio' }}</button>
                </div>
            </form>
            @if ($servicio->exists)
                <form class="mt-3" method="POST" action="{{ route('admin.servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este servicio?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar servicio</button>
                </form>
            @endif
        </div>
    </div>
@endsection
