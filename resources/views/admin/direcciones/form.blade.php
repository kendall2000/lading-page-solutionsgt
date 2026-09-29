@extends('layouts.admin', ['titulo' => $direccion->exists ? $direccion->nombre : 'Nueva dirección'])

@php $v = fn ($c) => old($c, $direccion->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.direcciones.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Direcciones</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $direccion->exists ? $direccion->nombre : 'Nueva dirección' }}</h2>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $direccion->exists ? route('admin.direcciones.update', $direccion) : route('admin.direcciones.store') }}">
                        @csrf
                        @if ($direccion->exists) @method('PUT') @endif
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nombre">Nombre *</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="120" placeholder="Oficina central" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="ciudad">Ciudad</label>
                                <input class="form-control" id="ciudad" name="ciudad" value="{{ $v('ciudad') }}" maxlength="120" placeholder="Guatemala" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="direccion">Dirección *</label>
                                <input class="form-control" id="direccion" name="direccion" value="{{ $v('direccion') }}" required maxlength="300" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono">Teléfono</label>
                                <input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="30" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="horario">Horario</label>
                                <input class="form-control" id="horario" name="horario" value="{{ $v('horario') }}" maxlength="150" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="latitud">Latitud</label>
                                <input class="form-control" id="latitud" name="latitud" value="{{ $v('latitud') }}" inputmode="decimal" placeholder="14.6349" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="longitud">Longitud</label>
                                <input class="form-control" id="longitud" name="longitud" value="{{ $v('longitud') }}" inputmode="decimal" placeholder="-90.5069" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="orden">Orden</label>
                                <input class="form-control" id="orden" name="orden" type="number" min="0" value="{{ $v('orden') }}" />
                            </div>
                            <div class="col-12">
                                <div class="form-text mt-0">Coordenadas opcionales: en Google Maps haz clic derecho sobre el lugar y copia los números. Sin ellas, el mapa busca la dirección escrita.</div>
                            </div>
                            <div class="col-12 d-flex gap-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="visible" name="visible" type="checkbox" value="1" @checked(old('visible', $direccion->visible)) />
                                    <label class="form-check-label" for="visible">Visible en el sitio</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="principal" name="principal" type="checkbox" value="1" @checked(old('principal', $direccion->principal)) />
                                    <label class="form-check-label" for="principal">Principal (mapa grande)</label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4" type="submit">{{ $direccion->exists ? 'Guardar cambios' : 'Crear dirección' }}</button>
                    </form>
                    @if ($direccion->exists)
                        <form class="mt-3" method="POST" action="{{ route('admin.direcciones.destroy', $direccion) }}" onsubmit="return confirm('¿Eliminar esta dirección?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar dirección</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @if ($direccion->exists)
            <div class="col-xl-5">
                <div class="card overflow-hidden">
                    <iframe src="{{ $direccion->mapaEmbebido() }}" loading="lazy" style="width:100%; height:360px; border:0;" title="Mapa"></iframe>
                </div>
            </div>
        @endif
    </div>
@endsection
