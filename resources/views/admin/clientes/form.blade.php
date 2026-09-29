@extends('layouts.admin', ['titulo' => $cliente->exists ? $cliente->empresa : 'Nuevo cliente'])

@php $v = fn ($c) => old($c, $cliente->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.clientes.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Clientes</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $cliente->exists ? $cliente->empresa : 'Nuevo cliente' }}</h2>

    <form method="POST" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($cliente->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Datos del cliente</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="empresa">Empresa *</label>
                                <input class="form-control" id="empresa" name="empresa" value="{{ $v('empresa') }}" required maxlength="150" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sistema_id">Sistema que usa</label>
                                <select class="form-select" id="sistema_id" name="sistema_id">
                                    <option value="">—</option>
                                    @foreach ($sistemas as $id => $nombre)
                                        <option value="{{ $id }}" @selected((string) $v('sistema_id') === (string) $id)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contacto">Persona de contacto</label>
                                <input class="form-control" id="contacto" name="contacto" value="{{ $v('contacto') }}" maxlength="120" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cargo">Cargo</label>
                                <input class="form-control" id="cargo" name="cargo" value="{{ $v('cargo') }}" maxlength="120" placeholder="Gerente general" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono">Teléfono</label>
                                <input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="30" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="correo">Correo</label>
                                <input class="form-control" id="correo" name="correo" type="email" value="{{ $v('correo') }}" maxlength="150" />
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="sitio_web">Sitio web</label>
                                <input class="form-control" id="sitio_web" name="sitio_web" type="url" value="{{ $v('sitio_web') }}" maxlength="255" placeholder="https://" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="orden">Orden</label>
                                <input class="form-control" id="orden" name="orden" type="number" min="0" value="{{ $v('orden') }}" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notas">Notas internas</label>
                                <textarea class="form-control" id="notas" name="notas" rows="3" maxlength="3000">{{ $v('notas') }}</textarea>
                                <div class="form-text">Solo las ves tú; no se publican.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">En el sitio</h4>
                        @include('admin.partes.imagen', ['campo' => 'logo', 'titulo' => 'Logo de la empresa', 'ayuda' => 'PNG con fondo transparente, máximo 2 MB.', 'url' => $cliente->url('logo')])
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" id="mostrar_logo" name="mostrar_logo" type="checkbox" value="1" @checked(old('mostrar_logo', $cliente->mostrar_logo)) />
                            <label class="form-check-label" for="mostrar_logo">Mostrar el logo en «Empresas que confían en mi trabajo»</label>
                        </div>
                        <hr />
                        <div class="mb-3">
                            <label class="form-label" for="testimonio">Testimonio</label>
                            <textarea class="form-control" id="testimonio" name="testimonio" rows="4" maxlength="1000" placeholder="Lo que el cliente dice de tu trabajo">{{ $v('testimonio') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="calificacion">Calificación</label>
                            <select class="form-select" id="calificacion" name="calificacion" style="max-width: 10rem">
                                @for ($e = 5; $e >= 1; $e--)
                                    <option value="{{ $e }}" @selected((int) $v('calificacion') === $e)>{{ str_repeat('★', $e) }}</option>
                                @endfor
                            </select>
                        </div>
                        @include('admin.partes.imagen', ['campo' => 'foto', 'titulo' => 'Foto de la persona', 'ayuda' => 'Cuadrada. Opcional: sin foto se muestran las iniciales.', 'url' => $cliente->url('foto')])
                        <div class="form-check form-switch">
                            <input class="form-check-input" id="mostrar_testimonio" name="mostrar_testimonio" type="checkbox" value="1" @checked(old('mostrar_testimonio', $cliente->mostrar_testimonio)) />
                            <label class="form-check-label" for="mostrar_testimonio">Mostrar el testimonio en el sitio</label>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-floppy-disk me-2"></span>{{ $cliente->exists ? 'Guardar cambios' : 'Crear cliente' }}</button>
            </div>
        </div>
    </form>
    @if ($cliente->exists)
        <div class="card mt-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="mb-0">Cuentas de esta empresa</h4>
                    <a class="btn btn-sm btn-phoenix-primary" href="{{ route('admin.cuentas.create', ['cliente' => $cliente->id]) }}"><span class="fa-solid fa-user-plus me-1"></span>Invitar a alguien de {{ $cliente->empresa }}</a>
                </div>
                @forelse ($cliente->cuentas as $cuentaEmpresa)
                    <a class="d-flex justify-content-between fs--1 py-2 {{ $loop->last ? '' : 'border-bottom border-200' }}" href="{{ route('admin.cuentas.edit', $cuentaEmpresa) }}">
                        <span class="text-900">{{ $cuentaEmpresa->nombre }} <span class="text-600">· {{ $cuentaEmpresa->correo }}</span></span>
                        <span class="badge badge-phoenix badge-phoenix-{{ $cuentaEmpresa->estadoInfo()[1] }}">{{ $cuentaEmpresa->estadoInfo()[0] }}</span>
                    </a>
                @empty
                    <p class="text-700 fs--1 mb-0">Nadie de esta empresa tiene cuenta todavía.</p>
                @endforelse
            </div>
        </div>
        <form class="mt-3" method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar este cliente?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar cliente</button>
        </form>
    @endif
@endsection
