@extends('layouts.admin', ['titulo' => 'Correos'])

{{-- Como «Correos» del ERP. Estructura: pestañas de modules/components/tabs + tarjetas de add-product.html y tabla de products.html. --}}
@section('contenido')
    @php $estados = \App\Http\Controllers\Admin\CorreoController::ESTADOS; @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">Panel</li>
            <li class="breadcrumb-item active">Correos</li>
        </ol>
    </nav>
    <div class="row g-3 flex-between-end mb-4">
        <div class="col-auto">
            <h2 class="mb-2">Correos</h2>
            <h5 class="text-700 fw-semi-bold">Servidor de envío, plantillas y registro de cada correo que envía el sistema.</h5>
        </div>
        <div class="col-auto">
            @if ($cfg->is_active)
                <span class="badge badge-phoenix badge-phoenix-success fs--1"><span class="fas fa-check me-1"></span>Servidor activo{{ $cfg->probado_en ? ' · probado el '.$cfg->probado_en->format('d/m/Y') : '' }}</span>
            @else
                <span class="badge badge-phoenix badge-phoenix-warning fs--1">Servidor apagado: no se envían correos</span>
            @endif
        </div>
    </div>

    <ul class="nav nav-underline mb-4" role="tablist">
        @foreach (['servidor' => ['server', 'Servidor'], 'plantillas' => ['file-text', 'Plantillas'], 'bitacora' => ['list', 'Bitácora']] as $id => [$icono, $texto])
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $pestana === $id ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $id }}" role="tab"><span class="me-1" data-feather="{{ $icono }}" style="width: 16px; height: 16px;"></span>{{ $texto }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content mb-9">
        {{-- Servidor --}}
        <div class="tab-pane fade {{ $pestana === 'servidor' ? 'show active' : '' }}" id="tab-servidor" role="tabpanel">
            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <form class="card" method="POST" action="{{ route('admin.correos.guardar') }}">
                        @csrf
                        <div class="card-body">
                            <div class="d-flex flex-between-center mb-3">
                                <h4 class="card-title mb-0">Servidor SMTP</h4>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $cfg->is_active)) />
                                    <label class="form-check-label fw-semi-bold" for="is_active">Usar este servidor</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="host">Host</label>
                                    <input class="form-control" id="host" name="host" maxlength="150" value="{{ old('host', $cfg->host) }}" placeholder="smtp.gmail.com" />
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="puerto">Puerto</label>
                                    <input class="form-control" id="puerto" name="puerto" type="number" min="1" max="65535" value="{{ old('puerto', $cfg->puerto) }}" placeholder="587" />
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="cifrado">Cifrado</label>
                                    <select class="form-select" id="cifrado" name="cifrado">
                                        <option value="" @selected(! old('cifrado', $cfg->cifrado))>Ninguno</option>
                                        <option value="tls" @selected(old('cifrado', $cfg->cifrado) === 'tls')>TLS (587)</option>
                                        <option value="ssl" @selected(old('cifrado', $cfg->cifrado) === 'ssl')>SSL (465)</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="usuario">Usuario</label>
                                    <input class="form-control" id="usuario" name="usuario" maxlength="200" value="{{ old('usuario', $cfg->usuario) }}" autocomplete="off" />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="clave">Contraseña</label>
                                    <input class="form-control" id="clave" name="clave" type="password" maxlength="500" autocomplete="new-password"
                                           placeholder="{{ $cfg->getRawOriginal('clave') ? '•••••••• (guardada; vacío = no cambia)' : 'Contraseña o clave de aplicación' }}" />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="remitente_correo">Correo del remitente</label>
                                    <input class="form-control" id="remitente_correo" name="remitente_correo" type="email" maxlength="150" value="{{ old('remitente_correo', $cfg->remitente_correo) }}" placeholder="no-responder@solutionsgt.com" />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="remitente_nombre">Nombre del remitente</label>
                                    <input class="form-control" id="remitente_nombre" name="remitente_nombre" maxlength="120" value="{{ old('remitente_nombre', $cfg->remitente_nombre) }}" placeholder="{{ \App\Support\Sitio::nombre() }}" />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="responder_a">Responder a <span class="text-600 fw-normal">(opcional)</span></label>
                                    <input class="form-control" id="responder_a" name="responder_a" type="email" maxlength="150" value="{{ old('responder_a', $cfg->responder_a) }}" />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="avisos_a">Avisos del sistema a</label>
                                    <input class="form-control" id="avisos_a" name="avisos_a" maxlength="500" value="{{ old('avisos_a', $cfg->avisos_a) }}" placeholder="tu@correo.com, otro@correo.com" />
                                    <div class="form-text">Reciben el aviso de cada mensaje nuevo del formulario de contacto. Separados por coma.</div>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-4" type="submit">Guardar servidor</button>
                        </div>
                    </form>
                </div>
                <div class="col-12 col-xl-4">
                    <form class="card mb-3" method="POST" action="{{ route('admin.correos.probar') }}">
                        @csrf
                        <div class="card-body">
                            <h4 class="card-title mb-2">Correo de prueba</h4>
                            <p class="fs--1 text-700 mb-3">Guarda primero; luego envía una prueba para confirmar que llega.</p>
                            <input class="form-control mb-2" name="destinatario" type="email" required value="{{ auth()->user()->email }}" aria-label="Destinatario" />
                            <button class="btn btn-phoenix-primary w-100" type="submit"><span class="fas fa-paper-plane me-2"></span>Enviar prueba</button>
                        </div>
                    </form>
                    <div class="card">
                        <div class="card-body fs--1 text-700">
                            <h5 class="mb-2 text-1000">Ejemplos</h5>
                            <p class="mb-1"><strong>Gmail:</strong> smtp.gmail.com · 587 · TLS · con «contraseña de aplicación».</p>
                            <p class="mb-1"><strong>Outlook / 365:</strong> smtp.office365.com · 587 · TLS.</p>
                            <p class="mb-0">La contraseña se guarda cifrada y no se vuelve a mostrar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Plantillas --}}
        <div class="tab-pane fade {{ $pestana === 'plantillas' ? 'show active' : '' }}" id="tab-plantillas" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <a class="btn btn-primary" href="{{ route('admin.correos.plantillas.create') }}"><span class="fas fa-plus me-2"></span>Nueva plantilla</a>
            </div>
            <div class="row g-3">
                @foreach ($plantillas as $p)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <h5 class="mb-0 flex-1 text-1000">{{ $p->nombre }}</h5>
                                    @if ($p->del_sistema)<span class="badge badge-phoenix badge-phoenix-info fs--2">Del sistema</span>@endif
                                    <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $p->is_active ? 'success' : 'secondary' }}">{{ $p->is_active ? 'Activa' : 'Inactiva' }}</span>
                                </div>
                                <p class="fs--2 text-600 font-monospace mb-2">{{ $p->codigo }}</p>
                                <p class="fs--1 text-700 mb-2">{{ $p->descripcion }}</p>
                                <p class="fs--1 mb-3"><span class="text-600">Asunto:</span> {{ $p->asunto }}</p>
                                <div class="mt-auto d-flex gap-2">
                                    <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.correos.plantillas.edit', $p) }}">Editar</a>
                                    <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('admin.correos.plantillas.previa', $p) }}" target="_blank" rel="noopener">Vista previa</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Bitácora --}}
        <div class="tab-pane fade {{ $pestana === 'bitacora' ? 'show active' : '' }}" id="tab-bitacora" role="tabpanel">
            <ul class="nav nav-links mb-3 mx-n3">
                <li class="nav-item"><a class="nav-link {{ $estado === null ? 'active' : '' }}" href="{{ route('admin.correos.index', ['ver' => 'bitacora']) }}">Todos <span class="text-700 fw-semi-bold">({{ $conteos->sum() }})</span></a></li>
                @foreach ($estados as $clave => [$color, $texto])
                    <li class="nav-item"><a class="nav-link {{ $estado === $clave ? 'active' : '' }}" href="{{ route('admin.correos.index', ['estado' => $clave]) }}">{{ $texto }} <span class="text-700 fw-semi-bold">({{ $conteos[$clave] ?? 0 }})</span></a></li>
                @endforeach
            </ul>
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table fs--1 mb-0">
                        <thead><tr><th class="ps-0">FECHA</th><th>PARA</th><th>ASUNTO</th><th>PLANTILLA</th><th class="pe-0">ESTADO</th></tr></thead>
                        <tbody>
                        @forelse ($bitacora as $b)
                            <tr>
                                <td class="ps-0 text-700 white-space-nowrap">{{ \Illuminate\Support\Carbon::parse($b->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $b->destinatario }}</td>
                                <td>{{ $b->asunto ?? '—' }}</td>
                                <td class="font-monospace text-600">{{ $b->plantilla ?? '—' }}</td>
                                <td class="pe-0">
                                    <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $estados[$b->estado][0] ?? 'secondary' }}">{{ $estados[$b->estado][1] ?? $b->estado }}</span>
                                    @if ($b->error)<p class="text-danger fs--2 mb-0 mt-1" style="max-width: 28rem;">{{ \Illuminate\Support\Str::limit($b->error, 220) }}</p>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-600 py-5">Todavía no se ha enviado ningún correo.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $bitacora->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
@endsection
