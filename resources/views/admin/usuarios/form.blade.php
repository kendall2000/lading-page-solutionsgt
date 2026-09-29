@extends('layouts.admin', ['titulo' => $usuario->exists ? $usuario->name : 'Nuevo usuario'])

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.usuarios.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Usuarios</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $usuario->exists ? $usuario->name : 'Nuevo usuario' }}</h2>

    <div class="card" style="max-width: 640px">
        <div class="card-body">
            <form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" autocomplete="off">
                @csrf
                @if ($usuario->exists) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label" for="name">Nombre *</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name', $usuario->name) }}" required maxlength="120" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Correo *</label>
                    <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="150" autocomplete="off" />
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">{{ $usuario->exists ? 'Nueva contraseña' : 'Contraseña *' }}</label>
                    <input class="form-control" id="password" name="password" type="password" {{ $usuario->exists ? '' : 'required' }} autocomplete="new-password" />
                    <div class="form-text">{{ $usuario->exists ? 'Déjala en blanco para no cambiarla. ' : '' }}Mínimo 10 caracteres, con letras y números.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $usuario->is_active)) />
                    <label class="form-check-label" for="is_active">Activo (puede entrar al panel)</label>
                </div>
                <button class="btn btn-primary" type="submit">{{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}</button>
            </form>
        </div>
    </div>
@endsection
