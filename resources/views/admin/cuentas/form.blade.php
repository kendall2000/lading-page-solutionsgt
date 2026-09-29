@extends('layouts.admin', ['titulo' => $cuenta->exists ? $cuenta->nombre : 'Invitar cliente'])

@php $v = fn ($c) => old($c, $cuenta->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ route('admin.cuentas.index') }}"><span class="fa-solid fa-angle-left me-1"></span>Cuentas de clientes</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-2 mb-4">
        <h2 class="mb-0 text-1100">
            {{ $cuenta->exists ? $cuenta->nombre : 'Invitar cliente' }}
            @if ($cuenta->exists)
                @php [$estadoTexto, $estadoColor] = $cuenta->estadoInfo(); @endphp
                <span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }} fs--1 align-middle ms-2">{{ $estadoTexto }}</span>
            @endif
        </h2>
        @if ($cuenta->exists && $cuenta->activa && ! $cuenta->verificada())
            <form method="POST" action="{{ route('admin.cuentas.invitar', $cuenta) }}">
                @csrf
                <button class="btn btn-phoenix-primary" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>{{ $cuenta->invitada_en ? 'Reenviar invitación' : 'Enviar invitación' }}</button>
            </form>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <form class="card mb-4" method="POST" action="{{ $cuenta->exists ? route('admin.cuentas.update', $cuenta) : route('admin.cuentas.store') }}">
                @csrf
                @if ($cuenta->exists) @method('PUT') @endif
                <div class="card-body">
                    <h4 class="mb-3">Datos de la cuenta</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre">Nombre *</label>
                            <input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="120" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="correo">Correo *</label>
                            <input class="form-control" id="correo" name="correo" type="email" value="{{ $v('correo') }}" required maxlength="150" />
                            @if ($cuenta->exists)<div class="form-text">Si lo cambias, tendrá que confirmar el correo nuevo.</div>@endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="telefono">Teléfono</label>
                            <input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="30" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="empresa">Empresa (como la escribió)</label>
                            <input class="form-control" id="empresa" name="empresa" value="{{ $v('empresa') }}" maxlength="150" />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cliente_id">Ligar a un cliente (empresa)</label>
                            <select class="form-select" id="cliente_id" name="cliente_id">
                                <option value="">— Sin ligar —</option>
                                @foreach ($clientes as $id => $empresa)
                                    <option value="{{ $id }}" @selected((string) $v('cliente_id') === (string) $id)>{{ $empresa }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Las personas de la misma empresa comparten los sistemas contratados «para toda la empresa». Las empresas se crean en <a href="{{ route('admin.clientes.index') }}">Clientes</a>.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="activa" name="activa" type="checkbox" value="1" @checked(old('activa', $cuenta->activa ?? true)) />
                                <label class="form-check-label" for="activa">Cuenta activa (si la apagas, no puede entrar)</label>
                            </div>
                            @unless ($cuenta->exists)
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="invitar" name="invitar" type="checkbox" value="1" @checked(old('invitar', true)) />
                                    <label class="form-check-label" for="invitar">Enviarle la invitación por correo (vale {{ \App\Services\Cuentas::DIAS_INVITACION }} días)</label>
                                </div>
                            @endunless
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">{{ $cuenta->exists ? 'Guardar' : 'Crear e invitar' }}</button>
                </div>
            </form>

            @if ($cuenta->exists)
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-1">Sistemas contratados</h4>
                        <p class="fs--1 text-700 mb-3">Aparecen en «Mi cuenta» con su acceso y vigencia, y abren los manuales «solo para clientes» de ese sistema.</p>
                        @forelse ($contratos as $contrato)
                            <details class="border border-200 rounded-3 p-3 mb-2">
                                <summary class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="cursor: pointer; list-style: none;">
                                    <span class="fw-semi-bold text-1000">
                                        {{ $contrato->sistema->nombre }}
                                        @if ($contrato->plan)<span class="text-700 fw-normal">· {{ $contrato->plan }}</span>@endif
                                        @if ($contrato->cliente_id)<span class="badge badge-phoenix badge-phoenix-info ms-1">Toda la empresa</span>@endif
                                    </span>
                                    <span class="fs--1 {{ $contrato->vigente() ? 'text-success' : 'text-danger' }}">
                                        {{ $contrato->hasta ? ($contrato->vigente() ? 'Vence '.$contrato->hasta->format('d/m/Y') : 'Venció '.$contrato->hasta->format('d/m/Y')) : 'Sin vencimiento' }}
                                    </span>
                                </summary>
                                <form class="row g-2 mt-2" method="POST" action="{{ route('admin.contratos.update', $contrato) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-6"><label class="form-label fs--1">Dirección de acceso</label><input class="form-control form-control-sm" name="url_acceso" type="url" value="{{ $contrato->url_acceso }}" placeholder="https://" maxlength="255" /></div>
                                    <div class="col-md-6"><label class="form-label fs--1">Plan</label><input class="form-control form-control-sm" name="plan" value="{{ $contrato->plan }}" maxlength="120" /></div>
                                    <div class="col-6"><label class="form-label fs--1">Desde</label><input class="form-control form-control-sm" name="desde" type="date" value="{{ $contrato->desde?->toDateString() }}" /></div>
                                    <div class="col-6"><label class="form-label fs--1">Vence (vacío = nunca)</label><input class="form-control form-control-sm" name="hasta" type="date" value="{{ $contrato->hasta?->toDateString() }}" /></div>
                                    <div class="col-12"><label class="form-label fs--1">Notas (las ve el cliente)</label><textarea class="form-control form-control-sm" name="notas" rows="2" maxlength="2000">{{ $contrato->notas }}</textarea></div>
                                    <div class="col-12 d-flex justify-content-between">
                                        <button class="btn btn-sm btn-phoenix-primary" type="submit">Guardar</button>
                                        <button class="btn btn-sm btn-link text-danger" type="submit" form="quitar{{ $contrato->id }}">Quitar sistema</button>
                                    </div>
                                </form>
                                <form id="quitar{{ $contrato->id }}" method="POST" action="{{ route('admin.contratos.destroy', $contrato) }}" onsubmit="return confirm('¿Quitar este sistema de la cuenta{{ $contrato->cliente_id ? ' (y de toda la empresa)' : '' }}?')">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </details>
                        @empty
                            <p class="text-700 fs--1">Todavía no tiene sistemas contratados.</p>
                        @endforelse

                        <h5 class="mt-4 mb-2">Agregar un sistema</h5>
                        <form class="row g-2" method="POST" action="{{ route('admin.cuentas.contratos.store', $cuenta) }}">
                            @csrf
                            <div class="col-md-6">
                                <select class="form-select form-select-sm" name="sistema_id" required aria-label="Sistema">
                                    <option value="">Sistema…</option>
                                    @foreach ($sistemas as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><input class="form-control form-control-sm" name="plan" maxlength="120" placeholder="Plan (opcional)" aria-label="Plan" /></div>
                            <div class="col-12"><input class="form-control form-control-sm" name="url_acceso" type="url" maxlength="255" placeholder="Dirección para entrar al sistema: https://…" aria-label="Dirección de acceso" /></div>
                            <div class="col-6"><label class="form-label fs--1">Desde</label><input class="form-control form-control-sm" name="desde" type="date" value="{{ now()->toDateString() }}" /></div>
                            <div class="col-6"><label class="form-label fs--1">Vence (vacío = nunca)</label><input class="form-control form-control-sm" name="hasta" type="date" /></div>
                            <div class="col-12"><textarea class="form-control form-control-sm" name="notas" rows="2" maxlength="2000" placeholder="Notas para el cliente (opcional): horario de soporte, instrucciones…" aria-label="Notas"></textarea></div>
                            @if ($cuenta->cliente_id)
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" id="para_empresa" name="para_empresa" type="checkbox" value="1" />
                                        <label class="form-check-label fs--1" for="para_empresa">Para toda la empresa ({{ $cuenta->cliente->empresa }}): lo ven todas sus cuentas</label>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12"><button class="btn btn-sm btn-primary" type="submit"><span class="fa-solid fa-plus me-1"></span>Agregar sistema</button></div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        @if ($cuenta->exists)
            <div class="col-xl-5">
                <div class="card mb-4">
                    <div class="card-body fs--1">
                        <h4 class="mb-3">Resumen</h4>
                        <dl class="row mb-0">
                            <dt class="col-6 text-700 fw-normal">Creada</dt><dd class="col-6">{{ $cuenta->created_at->format('d/m/Y') }}</dd>
                            <dt class="col-6 text-700 fw-normal">Correo confirmado</dt><dd class="col-6">{{ $cuenta->correo_verificado_en?->format('d/m/Y') ?? 'No' }}</dd>
                            <dt class="col-6 text-700 fw-normal">Invitada</dt><dd class="col-6">{{ $cuenta->invitada_en?->format('d/m/Y H:i') ?? '—' }}</dd>
                            <dt class="col-6 text-700 fw-normal">Tiene contraseña</dt><dd class="col-6">{{ $cuenta->password ? 'Sí' : 'No (entra con enlace)' }}</dd>
                            <dt class="col-6 text-700 fw-normal">Último acceso</dt><dd class="col-6 mb-0">{{ $cuenta->ultimo_acceso?->diffForHumans() ?? 'Nunca' }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Conversaciones</h4>
                        @forelse ($conversaciones as $c)
                            <a class="d-flex justify-content-between fs--1 py-2 {{ $loop->last ? '' : 'border-bottom border-200' }}" href="{{ route('admin.chat.index', ['c' => $c->id]) }}">
                                <span>{{ $c->estado === 'cerrada' ? 'Cerrada' : 'Abierta' }} · {{ $c->created_at->format('d/m/Y') }}</span>
                                <span class="text-600">{{ ($c->ultimo_mensaje_en ?? $c->created_at)->diffForHumans() }}</span>
                            </a>
                        @empty
                            <p class="text-700 fs--1 mb-0">Sin conversaciones.</p>
                        @endforelse
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Solicitudes</h4>
                        @forelse ($solicitudes as $s)
                            <a class="d-flex justify-content-between fs--1 py-2 {{ $loop->last ? '' : 'border-bottom border-200' }}" href="{{ route('admin.mensajes.show', $s) }}">
                                <span>{{ $s->tipoInfo()[0] }}{{ $s->sistema ? ' · '.$s->sistema->nombre : '' }}</span>
                                <span class="text-600">{{ $s->created_at->format('d/m/Y') }}</span>
                            </a>
                        @empty
                            <p class="text-700 fs--1 mb-0">Sin solicitudes.</p>
                        @endforelse
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.cuentas.destroy', $cuenta) }}" onsubmit="return confirm('¿Eliminar esta cuenta? Sus chats y solicitudes se quedan en el panel.')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger px-0 fs--1" type="submit"><span class="fa-solid fa-trash me-1"></span>Eliminar cuenta</button>
                </form>
            </div>
        @endif
    </div>
@endsection
