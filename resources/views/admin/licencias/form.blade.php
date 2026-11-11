@extends('layouts.admin', ['titulo' => $instalacion->exists ? $instalacion->nombre : 'Nueva instalación'])

@php $v = fn ($c) => old($c, $instalacion->{$c}); @endphp

@section('contenido')
    <a class="fs--1 fw-bold" href="{{ $instalacion->exists ? route('admin.licencias.show', $instalacion) : route('admin.licencias.index') }}"><span class="fa-solid fa-angle-left me-1"></span>{{ $instalacion->exists ? $instalacion->nombre : 'Licencias Nexus' }}</a>
    <h2 class="mt-2 mb-4 text-1100">{{ $instalacion->exists ? 'Editar instalación' : 'Nueva instalación' }}</h2>

    <form method="POST" action="{{ $instalacion->exists ? route('admin.licencias.update', $instalacion) : route('admin.licencias.store') }}">
        @csrf
        @if ($instalacion->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Cliente e instalación</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nombre">Cliente *</label>
                                <input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="150" placeholder="Seguridad Acme" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="identificador">Identificador *</label>
                                <input class="form-control font-monospace" id="identificador" name="identificador" value="{{ $v('identificador') }}" required maxlength="40" pattern="[a-z0-9][a-z0-9\-]{1,39}" placeholder="acme" />
                                <div class="form-text">El nombre corto del cliente: el mismo <code>NEXUS_LICENCIA_ID</code> de su <code>.env</code>.</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="url">Dirección de su Nexus *</label>
                                <input class="form-control" id="url" name="url" type="url" value="{{ $v('url') }}" required maxlength="255" placeholder="https://acme.tudominio.com" />
                                <div class="form-text">A esta dirección se le avisa «consulta tu licencia ya» al registrar un pago.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="tipo">Tipo *</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    @foreach (\App\Models\Instalacion::TIPOS as $clave => $texto)
                                        <option value="{{ $clave }}" @selected($v('tipo') === $clave)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="correo_cobro">Correo para avisos de cobro</label>
                                <input class="form-control" id="correo_cobro" name="correo_cobro" type="email" value="{{ $v('correo_cobro') }}" maxlength="150" />
                                <div class="form-text">Le llegan los avisos de vencimiento y de cada etapa.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="telefono">Teléfono</label>
                                <input class="form-control" id="telefono" name="telefono" value="{{ $v('telefono') }}" maxlength="30" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notas">Notas internas</label>
                                <textarea class="form-control" id="notas" name="notas" rows="3" maxlength="3000" placeholder="Lo acordado en persona: precio, día de pago, descuentos…">{{ $v('notas') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Cobro</h4>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="monto">Monto acordado</label>
                                <input class="form-control" id="monto" name="monto" type="number" step="0.01" min="0" value="{{ $v('monto') }}" />
                            </div>
                            <div class="col-3">
                                <label class="form-label" for="moneda">Moneda</label>
                                <select class="form-select" id="moneda" name="moneda">
                                    @foreach (['GTQ', 'USD'] as $m)<option @selected($v('moneda') === $m)>{{ $m }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label" for="meses_periodo">Cada (meses)</label>
                                <input class="form-control" id="meses_periodo" name="meses_periodo" type="number" min="1" max="36" value="{{ $v('meses_periodo') }}" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="vence_el">Pagado hasta</label>
                                <input class="form-control" id="vence_el" name="vence_el" type="date" value="{{ old('vence_el', $instalacion->vence_el?->format('Y-m-d')) }}" />
                                <div class="form-text">Último día pagado. Vacío y sin contrato: sin vencimiento hasta registrar el primer pago. Después lo corre «Registrar pago».</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="contrato_id">Contrato vinculado (PayPal)</label>
                                <select class="form-select" id="contrato_id" name="contrato_id">
                                    <option value="">— Ninguno: solo pagos a mano —</option>
                                    @foreach ($contratos as $id => $texto)
                                        <option value="{{ $id }}" @selected((string) $v('contrato_id') === (string) $id)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Si el cliente paga con suscripción de PayPal, al cobrar se extiende el contrato y con él esta licencia, sola.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-1">Etapas</h4>
                        <p class="text-700 fs--1">Vacío = el valor de la configuración ({{ $cfgLic->dias_aviso }} / {{ $cfgLic->dias_gracia }} / {{ $cfgLic->dias_restringido }}).</p>
                        <div class="row g-3">
                            <div class="col-4">
                                <label class="form-label" for="dias_aviso">Aviso</label>
                                <input class="form-control" id="dias_aviso" name="dias_aviso" type="number" min="0" max="365" value="{{ $v('dias_aviso') }}" placeholder="{{ $cfgLic->dias_aviso }}" />
                            </div>
                            <div class="col-4">
                                <label class="form-label" for="dias_gracia">Gracia</label>
                                <input class="form-control" id="dias_gracia" name="dias_gracia" type="number" min="0" max="365" value="{{ $v('dias_gracia') }}" placeholder="{{ $cfgLic->dias_gracia }}" />
                            </div>
                            <div class="col-4">
                                <label class="form-label" for="dias_restringido">Restringido</label>
                                <input class="form-control" id="dias_restringido" name="dias_restringido" type="number" min="0" max="365" value="{{ $v('dias_restringido') }}" placeholder="{{ $cfgLic->dias_restringido }}" />
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="bloquear_facturacion" name="bloquear_facturacion" type="checkbox" value="1" @checked(old('bloquear_facturacion', $instalacion->bloquear_facturacion)) />
                                    <label class="form-check-label" for="bloquear_facturacion">En «Restringido» tampoco puede facturar</label>
                                </div>
                                <div class="form-text">Apagado (recomendado): puede seguir facturando y cobrando hasta «Solo lectura».</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="contacto_i">Contacto para pagar</label>
                                <input class="form-control" id="contacto_i" name="contacto" value="{{ $v('contacto') }}" maxlength="255" placeholder="{{ $cfgLic->contacto ?: 'El de la configuración' }}" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="enlace_pago_i">Enlace de pago</label>
                                <input class="form-control" id="enlace_pago_i" name="enlace_pago" type="url" value="{{ $v('enlace_pago') }}" maxlength="500" placeholder="{{ $cfgLic->enlace_pago ?: 'https://' }}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">{{ $instalacion->exists ? 'Guardar' : 'Crear instalación' }}</button>
    </form>

    @if ($instalacion->exists)
        <form class="mt-3" method="POST" action="{{ route('admin.licencias.destroy', $instalacion) }}" onsubmit="return confirm('¿Eliminar esta instalación y sus pagos registrados? La instalación conserva lo último que supo y deja de recibir cambios.')">
            @csrf @method('DELETE')
            <button class="btn btn-link text-danger p-0" type="submit">Eliminar instalación</button>
        </form>
    @endif
@endsection
