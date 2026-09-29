@extends('layouts.admin', ['titulo' => 'Seguridad de mi cuenta'])

@section('contenido')
    <h2 class="mb-5 text-1100">Seguridad de mi cuenta</h2>

    <div class="row g-4">
        {{-- Contraseña --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-3">Cambiar contraseña</h4>
                    <form method="POST" action="{{ route('user-password.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Contraseña actual</label>
                            <input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Nueva contraseña</label>
                            <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                        </div>
                        <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Verificación en dos pasos --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-2">Verificación en dos pasos</h4>
                    <p class="text-700 fs--1">Además de tu contraseña, pide un código de tu teléfono (Google Authenticator, Microsoft Authenticator, Authy…). Recomendado para el panel.</p>

                    @if (! $user->two_factor_secret)
                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf
                            <button class="btn btn-primary" type="submit">Activar</button>
                        </form>
                    @elseif (! $user->two_factor_confirmed_at)
                        <p class="fs--1 mb-2">1. Escanea este código QR con tu aplicación:</p>
                        <div class="bg-white p-3 d-inline-block rounded mb-2">{!! $user->twoFactorQrCodeSvg() !!}</div>
                        <p class="fs--2 text-700">O escribe esta clave: <code>{{ decrypt($user->two_factor_secret) }}</code></p>
                        <p class="fs--1 mb-2">2. Escribe el código de 6 dígitos que aparece:</p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="d-flex gap-2 mb-3">
                            @csrf
                            <input class="form-control" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required style="max-width: 10rem" aria-label="Código" />
                            <button class="btn btn-primary" type="submit">Confirmar</button>
                        </form>
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link px-0 fs--1" type="submit">Cancelar</button>
                        </form>
                    @else
                        <p><span class="badge badge-phoenix badge-phoenix-success">Activa</span></p>
                        @if (in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true))
                            <p class="fs--1 mb-2">Guarda estos códigos de recuperación. Cada uno sirve una sola vez si pierdes tu teléfono:</p>
                            <div class="bg-light p-3 rounded mb-3 font-monospace fs--1">
                                @foreach ($user->recoveryCodes() as $codigo)
                                    <div>{{ $codigo }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                                @csrf
                                <button class="btn btn-phoenix-secondary btn-sm" type="submit">Generar códigos de recuperación nuevos</button>
                            </form>
                            <form method="POST" action="{{ route('two-factor.disable') }}" onsubmit="return confirm('¿Desactivar la verificación en dos pasos?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-phoenix-danger btn-sm" type="submit">Desactivar</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sesiones abiertas --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <h4 class="mb-0">Sesiones abiertas</h4>
                        @if ($sesiones->count() > 1)
                            <form method="POST" action="{{ route('admin.cuenta.sesiones') }}" class="d-flex gap-2">
                                @csrf
                                @method('DELETE')
                                <input class="form-control form-control-sm" name="password" type="password" placeholder="Tu contraseña" required autocomplete="current-password" aria-label="Contraseña" />
                                <button class="btn btn-phoenix-danger btn-sm text-nowrap" type="submit">Cerrar las demás</button>
                            </form>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0">
                            <thead><tr><th class="ps-0">Dispositivo</th><th>IP</th><th>Última actividad</th></tr></thead>
                            <tbody>
                            @foreach ($sesiones as $s)
                                <tr>
                                    <td class="ps-0">{{ $s['dispositivo'] }} @if ($s['actual'])<span class="badge badge-phoenix badge-phoenix-primary ms-1">Esta sesión</span>@endif</td>
                                    <td>{{ $s['ip'] }}</td>
                                    <td>{{ $s['actividad'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
