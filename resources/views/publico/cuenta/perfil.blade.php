@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Mi perfil'])

@section('portal')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100"><div class="card-body">
                <h4 class="mb-3">Mis datos</h4>
                <form method="POST" action="{{ route('cuenta.perfil.actualizar') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $cuenta->nombre) }}" required maxlength="120" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo</label>
                        <input class="form-control" id="correo" value="{{ $cuenta->correo }}" disabled />
                        <div class="form-text">Para cambiar tu correo, escríbenos por el chat: así protegemos tu cuenta.</div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label" for="empresa">Empresa o negocio</label>
                            @if ($cuenta->cliente)
                                <input class="form-control" id="empresa" value="{{ $cuenta->cliente->empresa }}" disabled />
                                <input type="hidden" name="empresa" value="{{ $cuenta->empresa }}" />
                            @else
                                <input class="form-control" id="empresa" name="empresa" value="{{ old('empresa', $cuenta->empresa) }}" maxlength="150" />
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="telefono">Teléfono / WhatsApp</label>
                            <input class="form-control" id="telefono" name="telefono" type="tel" value="{{ old('telefono', $cuenta->telefono) }}" maxlength="30" />
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar mis datos</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body">
                <h4 class="mb-2">{{ $cuenta->password ? 'Cambiar contraseña' : 'Crear contraseña' }}</h4>
                <p class="fs--1 text-700">
                    @if ($cuenta->password)
                        Siempre puedes entrar también con un enlace por correo.
                    @else
                        Hoy entras con enlaces por correo. Si prefieres, crea una contraseña.
                    @endif
                </p>
                <form method="POST" action="{{ route('cuenta.clave') }}">
                    @csrf
                    @method('PUT')
                    @if ($pideActual)
                        <div class="mb-3">
                            <label class="form-label" for="actual">Contraseña actual</label>
                            <input class="form-control" id="actual" name="actual" type="password" required autocomplete="current-password" />
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña nueva</label>
                        <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" />
                        <div class="form-text">Mínimo 10 caracteres, con letras y números.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Repítela</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                    </div>
                    <button class="btn btn-phoenix-primary" type="submit">Guardar contraseña</button>
                </form>
            </div></div>
        </div>
    </div>
@endsection
