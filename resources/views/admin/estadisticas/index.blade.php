@extends('layouts.admin', ['titulo' => 'Estadísticas'])

{{-- Diseño: dashboard/crm.html de Phoenix (tarjetas con variación, listas y gráficas ECharts). --}}
@use('App\Http\Controllers\Admin\EstadisticaController')
@use('App\Models\MensajeContacto')
@use('App\Services\Estadisticas')
@use('App\Services\Visitas')
@php
    $n =fn ($v) => number_format((float) $v, is_float($v) && floor($v) != $v ? 1 : 0);
    // [texto, color] de la variación contra el periodo anterior.
    $cambio = function ($actual, $antes) {
        $v = Estadisticas::variacion($actual, $antes);
        if ($v === null) {
            return [$actual > 0 ? 'Nuevo' : 'Sin datos antes', 'secondary'];
        }

        return [($v > 0 ? '+' : '').$v.'%', $v > 0 ? 'success' : ($v < 0 ? 'danger' : 'secondary')];
    };
    $minutos = fn (?int $m) => $m === null ? '—' : ($m < 60 ? $m.' min' : ($m < 1440 ? round($m / 60, 1).' h' : round($m / 1440, 1).' días'));
    $filtroFechas = ['desde' => $e->desde->toDateString(), 'hasta' => $e->hasta->toDateString()];
    $tarjeta = fn (string $icono, string $color, string $texto, $valor, ?array $var = null, ?string $nota = null) => compact('icono', 'color', 'texto', 'valor', 'var', 'nota');
@endphp

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h2 class="mb-2 text-1100">Estadísticas</h2>
            <h5 class="text-700 fw-semi-bold mb-0">
                Del {{ $e->desde->translatedFormat('j \d\e F Y') }} al {{ $e->hasta->translatedFormat('j \d\e F Y') }}
                <span class="fs--1 text-600 fw-normal">· comparado con los {{ $e->dias()->count() }} días anteriores</span>
            </h5>
        </div>
    </div>

    {{-- Rango de fechas --}}
    <div class="card mb-4">
        <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex flex-wrap gap-2">
                @foreach (EstadisticaController::RANGOS as $clave => $texto)
                    <a class="btn btn-sm {{ $rango === (string) $clave ? 'btn-primary' : 'btn-phoenix-secondary' }}" href="{{ route('admin.estadisticas', ['rango' => $clave]) }}">{{ $texto }}</a>
                @endforeach
            </div>
            <form class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto" method="GET" action="{{ route('admin.estadisticas') }}">
                <input type="hidden" name="rango" value="personalizado" />
                <label class="fs--1 text-700 mb-0" for="desde">Desde</label>
                <input class="form-control form-control-sm w-auto" id="desde" name="desde" type="date" value="{{ $e->desde->toDateString() }}" max="{{ now()->toDateString() }}" required />
                <label class="fs--1 text-700 mb-0" for="hasta">hasta</label>
                <input class="form-control form-control-sm w-auto" id="hasta" name="hasta" type="date" value="{{ $e->hasta->toDateString() }}" max="{{ now()->toDateString() }}" required />
                <button class="btn btn-sm {{ $rango === 'personalizado' ? 'btn-primary' : 'btn-phoenix-primary' }}" type="submit">Ver</button>
            </form>
        </div>
    </div>
    @if ($errors->any())
        <div class="alert alert-soft-danger py-2 fs--1">{{ $errors->first() }}</div>
    @endif

    <ul class="nav nav-underline mb-4" id="sgtPestanas" role="tablist">
        @foreach (['resumen' => 'Resumen', 'visitas' => 'Visitas', 'solicitudes' => 'Solicitudes', 'chat' => 'Chat', 'correos' => 'Correos', 'usuarios' => 'Usuarios y bitácora'] as $id => $texto)
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $id }}" data-bs-toggle="tab" href="#p-{{ $id }}" role="tab" aria-controls="p-{{ $id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $texto }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- ============ RESUMEN ============ --}}
        <div class="tab-pane fade show active" id="p-resumen" role="tabpanel" aria-labelledby="tab-resumen">
            @include('admin.estadisticas.tarjetas', ['tarjetas' => [
                $tarjeta('users', 'primary', 'Visitantes', $n($visitas['visitantes']), $cambio($visitas['visitantes'], $visitas['antes']['visitantes'])),
                $tarjeta('eye', 'info', 'Páginas vistas', $n($visitas['vistas']), $cambio($visitas['vistas'], $visitas['antes']['vistas'])),
                $tarjeta('inbox', 'success', 'Solicitudes', $n($solicitudes['total']), $cambio($solicitudes['total'], $solicitudes['antes']),
                    $solicitudes['conversion'] !== null ? $solicitudes['conversion'].'% de los visitantes' : null),
                $tarjeta('message-circle', 'warning', 'Chats', $n($chat['conversaciones']), $cambio($chat['conversaciones'], $chat['antes']),
                    'Primera respuesta: '.$minutos($chat['primeraRespuesta'])),
            ]])

            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-1">Visitantes y solicitudes por día</h4>
                            <p class="text-700 fs--1 mb-3">Cuántas personas entraron y cuántas pidieron algo.</p>
                            <div data-grafica="resumen" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">Tu sitio hoy</h4>
                            @php
                                $filasContenido = [
                                    ['layout', 'Páginas publicadas', $contenido['paginas'][0].' de '.$contenido['paginas'][1], route('admin.paginas.index')],
                                    ['package', 'Sistemas publicados', $contenido['sistemas'][0].' de '.$contenido['sistemas'][1].($contenido['proximamente'] ? ' ('.$contenido['proximamente'].' próximamente)' : ''), route('admin.sistemas.index')],
                                    ['book-open', 'Manuales publicados', $contenido['manuales'][0].' de '.$contenido['manuales'][1], route('admin.manuales.index')],
                                    ['users', 'Clientes', $contenido['clientes'].' ('.$contenido['testimonios'].' con testimonio)', route('admin.clientes.index')],
                                    ['tag', 'Planes', $contenido['planes'], route('admin.servicios.index')],
                                    ['mail', 'Solicitudes sin atender', $solicitudes['pendientes'], route('admin.mensajes.index', ['estado' => 'nuevo'])],
                                    ['message-circle', 'Chats abiertos ahora', $chat['abiertasAhora'], route('admin.chat.index')],
                                ];
                            @endphp
                            <ul class="list-group list-group-flush">
                                @foreach ($filasContenido as [$icono, $texto, $valor, $enlace])
                                    <li class="list-group-item bg-transparent list-group-crm px-0 py-2 fs--1">
                                        <a class="d-flex justify-content-between align-items-center text-900" href="{{ $enlace }}">
                                            <span><span class="me-2 text-600" data-feather="{{ $icono }}" style="width:16px;height:16px;"></span>{{ $texto }}</span>
                                            <span class="fw-bold">{{ $valor }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">Lo más visto</h4>
                            @include('admin.estadisticas.paginas', ['paginas' => $visitas['paginas']->take(8), 'corto' => true])
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0">Actividad en el panel</h4>
                                <a class="fs--1 fw-bold" href="{{ route('admin.bitacora.index', $filtroFechas) }}">Ver bitácora</a>
                            </div>
                            @include('admin.bitacora.lista', ['registros' => $usuarios['recientes']->take(8)])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ VISITAS ============ --}}
        <div class="tab-pane fade" id="p-visitas" role="tabpanel" aria-labelledby="tab-visitas">
            @include('admin.estadisticas.tarjetas', ['tarjetas' => [
                $tarjeta('users', 'primary', 'Visitantes únicos', $n($visitas['visitantes']), $cambio($visitas['visitantes'], $visitas['antes']['visitantes']), 'Una persona cuenta una vez por día'),
                $tarjeta('eye', 'info', 'Páginas vistas', $n($visitas['vistas']), $cambio($visitas['vistas'], $visitas['antes']['vistas'])),
                $tarjeta('log-in', 'success', 'Entradas al sitio', $n($visitas['entradas']), null, 'Llegadas desde afuera (no navegación interna)'),
                $tarjeta('layers', 'warning', 'Páginas por visitante', $n($visitas['paginasPorVisitante'])),
            ]])
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="mb-1">Visitas por día</h4>
                    <p class="text-700 fs--1 mb-3">No cuenta robots, vistas previas de enlaces ni a quien tiene sesión en el panel.</p>
                    <div data-grafica="visitas" style="min-height: 300px;"></div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">¿De dónde llegan?</h4>
                        <p class="text-700 fs--1 mb-2">Primera página de cada visita.</p>
                        <div data-grafica="origenes" style="min-height: 260px;"></div>
                        @if ($visitas['sitios']->isNotEmpty())
                            <h6 class="text-700 mt-3 mb-2">Otros sitios y campañas</h6>
                            <ul class="list-group list-group-flush">
                                @foreach ($visitas['sitios'] as $sitio => $total)
                                    <li class="list-group-item bg-transparent list-group-crm px-0 py-1 fs--1 d-flex justify-content-between"><span class="text-truncate me-2">{{ $sitio }}</span><span class="fw-bold">{{ $n($total) }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div></div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Dispositivos</h4>
                        <p class="text-700 fs--1 mb-2">Visitantes por tipo de equipo.</p>
                        <div data-grafica="dispositivos" style="min-height: 260px;"></div>
                    </div></div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Navegadores</h4>
                        <p class="text-700 fs--1 mb-2">Visitantes por navegador.</p>
                        <div data-grafica="navegadores" style="min-height: 260px;"></div>
                    </div></div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Hora del día</h4>
                        <p class="text-700 fs--1 mb-2">Páginas vistas por hora (Guatemala).</p>
                        <div data-grafica="horas" style="min-height: 240px;"></div>
                    </div></div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Día de la semana</h4>
                        <p class="text-700 fs--1 mb-2">Páginas vistas por día.</p>
                        <div data-grafica="semana" style="min-height: 240px;"></div>
                    </div></div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Páginas más vistas</h4>
                    @include('admin.estadisticas.paginas', ['paginas' => $visitas['paginas'], 'corto' => false])
                </div>
            </div>
        </div>

        {{-- ============ SOLICITUDES ============ --}}
        <div class="tab-pane fade" id="p-solicitudes" role="tabpanel" aria-labelledby="tab-solicitudes">
            @include('admin.estadisticas.tarjetas', ['tarjetas' => [
                $tarjeta('inbox', 'primary', 'Solicitudes recibidas', $n($solicitudes['total']), $cambio($solicitudes['total'], $solicitudes['antes'])),
                $tarjeta('percent', 'info', 'Conversión', $solicitudes['conversion'] !== null ? $solicitudes['conversion'].'%' : '—', null, 'Solicitudes por cada 100 visitantes'),
                $tarjeta('award', 'success', 'Se volvieron clientes', $n($solicitudes['porEstado']['cliente'] ?? 0), null, 'De las recibidas en el periodo'),
                $tarjeta('clock', 'warning', 'Sin atender (todas)', $n($solicitudes['pendientes'])),
            ]])
            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Solicitudes por día</h4>
                        <p class="text-700 fs--1 mb-3">Mensajes, demostraciones y pruebas.</p>
                        <div data-grafica="solicitudes" style="min-height: 300px;"></div>
                    </div></div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-3">Cómo van</h4>
                        <ul class="list-group list-group-flush">
                            @foreach (MensajeContacto::ESTADOS as $clave => [$texto, $color])
                                <li class="list-group-item bg-transparent list-group-crm px-0 py-2 fs--1">
                                    <a class="d-flex justify-content-between text-900" href="{{ route('admin.mensajes.index', ['estado' => $clave]) }}">
                                        <span><span class="fa-solid fa-square fs--3 me-2 text-{{ $color }}"></span>{{ $texto }}</span>
                                        <span class="fw-bold">{{ $n($solicitudes['porEstado'][$clave] ?? 0) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a class="btn btn-sm btn-phoenix-secondary w-100 mt-3" href="{{ route('admin.mensajes.exportar') }}"><span class="fa-solid fa-file-excel me-2"></span>Descargar todas para Excel</a>
                    </div></div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Sistemas que más piden</h4>
                    @if ($solicitudes['sistemas']->isEmpty())
                        <p class="text-700 fs--1 mb-0">En este periodo nadie pidió información de un sistema en particular.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm fs--1 mb-0">
                                <thead><tr><th class="ps-0">Sistema</th><th class="text-end">Demostraciones</th><th class="text-end">Pruebas</th><th class="text-end pe-0">Total</th></tr></thead>
                                <tbody>
                                    @foreach ($solicitudes['sistemas'] as $fila)
                                        <tr>
                                            <td class="ps-0"><a href="{{ route('admin.sistemas.edit', $fila->id) }}">{{ $fila->nombre }}</a></td>
                                            <td class="text-end">{{ $n($fila->demos) }}</td>
                                            <td class="text-end">{{ $n($fila->pruebas) }}</td>
                                            <td class="text-end pe-0 fw-bold">{{ $n($fila->total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============ CHAT ============ --}}
        <div class="tab-pane fade" id="p-chat" role="tabpanel" aria-labelledby="tab-chat">
            @include('admin.estadisticas.tarjetas', ['tarjetas' => [
                $tarjeta('message-circle', 'primary', 'Conversaciones', $n($chat['conversaciones']), $cambio($chat['conversaciones'], $chat['antes'])),
                $tarjeta('zap', 'success', 'Primera respuesta', $minutos($chat['primeraRespuesta']), null, 'Promedio, de las respondidas'),
                $tarjeta('alert-circle', 'danger', 'Sin respuesta', $n($chat['sinResponder']), null, 'Del periodo'),
                $tarjeta('send', 'info', 'Mensajes', $n(($chat['mensajes']['visitante'] ?? 0) + ($chat['mensajes']['admin'] ?? 0)), null,
                    $n($chat['mensajes']['visitante'] ?? 0).' de visitantes · '.$n($chat['mensajes']['admin'] ?? 0).' del panel'),
            ]])
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Conversaciones por día</h4>
                        <p class="text-700 fs--1 mb-3">Chats iniciados desde el sitio.</p>
                        <div data-grafica="chat" style="min-height: 280px;"></div>
                    </div></div>
                </div>
                <div class="col-xl-4">
                    <div class="card mb-4"><div class="card-body">
                        <h4 class="mb-3">¿Dónde empiezan?</h4>
                        @forelse ($chat['paginas'] as $pagina => $total)
                            <div class="d-flex justify-content-between fs--1 py-1 {{ $loop->last ? '' : 'border-bottom border-200' }}"><span class="text-truncate me-2">{{ $pagina }}</span><span class="fw-bold">{{ $n($total) }}</span></div>
                        @empty
                            <p class="text-700 fs--1 mb-0">Sin chats en este periodo.</p>
                        @endforelse
                    </div></div>
                    <div class="card"><div class="card-body">
                        <h4 class="mb-3">Quién responde</h4>
                        @forelse ($chat['porUsuario'] as $nombre => $total)
                            <div class="d-flex justify-content-between fs--1 py-1 {{ $loop->last ? '' : 'border-bottom border-200' }}"><span>{{ $nombre }}</span><span class="fw-bold">{{ $n($total) }} mensajes</span></div>
                        @empty
                            <p class="text-700 fs--1 mb-0">Nadie respondió chats en este periodo.</p>
                        @endforelse
                    </div></div>
                </div>
            </div>
        </div>

        {{-- ============ CORREOS ============ --}}
        <div class="tab-pane fade" id="p-correos" role="tabpanel" aria-labelledby="tab-correos">
            @include('admin.estadisticas.tarjetas', ['tarjetas' => [
                $tarjeta('send', 'primary', 'Correos intentados', $n($correos['total'])),
                $tarjeta('check-circle', 'success', 'Enviados', $n($correos['porEstado']['enviado'] ?? 0), null, $correos['exito'] !== null ? $correos['exito'].'% de éxito' : null),
                $tarjeta('x-circle', 'danger', 'Fallidos', $n($correos['porEstado']['fallido'] ?? 0), null, 'Revisa Panel → Correos'),
                $tarjeta('slash', 'secondary', 'No enviados', $n(($correos['porEstado']['desactivado'] ?? 0) + ($correos['porEstado']['sin_plantilla'] ?? 0)), null, 'Servidor o plantilla apagados'),
            ]])
            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-3">Correos por día</h4>
                        <div data-grafica="correos" style="min-height: 280px;"></div>
                    </div></div>
                </div>
                <div class="col-xl-5">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-3">Por plantilla</h4>
                        @if ($correos['plantillas']->isEmpty())
                            <p class="text-700 fs--1 mb-0">No se enviaron correos en este periodo.</p>
                        @else
                            <table class="table table-sm fs--1 mb-0">
                                <thead><tr><th class="ps-0">Plantilla</th><th class="text-end">Enviados</th><th class="text-end pe-0">Fallidos</th></tr></thead>
                                <tbody>
                                    @foreach ($correos['plantillas'] as $fila)
                                        <tr><td class="ps-0"><code>{{ $fila->plantilla }}</code></td><td class="text-end">{{ $n($fila->enviados) }}</td><td class="text-end pe-0 {{ $fila->fallidos ? 'text-danger fw-bold' : '' }}">{{ $n($fila->fallidos) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div></div>
                </div>
            </div>
        </div>

        {{-- ============ USUARIOS Y BITÁCORA ============ --}}
        <div class="tab-pane fade" id="p-usuarios" role="tabpanel" aria-labelledby="tab-usuarios">
            @include('admin.estadisticas.tarjetas', ['tarjetas' => [
                $tarjeta('log-in', 'primary', 'Entradas al panel', $n($usuarios['porAccion']['entrar'] ?? 0)),
                $tarjeta('edit-3', 'info', 'Cambios hechos', $n(($usuarios['porAccion']['crear'] ?? 0) + ($usuarios['porAccion']['editar'] ?? 0) + ($usuarios['porAccion']['borrar'] ?? 0)), null,
                    $n($usuarios['porAccion']['crear'] ?? 0).' creados · '.$n($usuarios['porAccion']['editar'] ?? 0).' editados · '.$n($usuarios['porAccion']['borrar'] ?? 0).' borrados'),
                $tarjeta('alert-triangle', $usuarios['fallidos'] ? 'danger' : 'success', 'Accesos fallidos', $n($usuarios['fallidos']), null, $usuarios['fallidos'] ? 'Contraseñas equivocadas o usuarios inexistentes' : 'Ningún intento raro'),
                $tarjeta('shield', 'warning', 'Usuarios activos', $n($usuarios['lista']->where('usuario.is_active', true)->count()), null, 'De '.$usuarios['lista']->count().' en total'),
            ]])
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Actividad por usuario</h4>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead>
                                <tr><th class="ps-0">Usuario</th><th>Último acceso</th><th class="text-end">Entradas</th><th class="text-end">Cambios</th><th class="text-end">Mensajes de chat</th><th>Última acción</th><th class="text-end pe-0"></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($usuarios['lista'] as $fila)
                                    <tr>
                                        <td class="ps-0">
                                            <div class="fw-semi-bold text-900">{{ $fila->usuario->name }}
                                                @unless ($fila->usuario->is_active)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Desactivado</span>@endunless
                                                @if ($fila->usuario->two_factor_confirmed_at)<span class="badge badge-phoenix badge-phoenix-success ms-1" title="Verificación en dos pasos">2 pasos</span>@endif
                                            </div>
                                            <div class="text-600">{{ $fila->usuario->email }}</div>
                                        </td>
                                        <td>{{ $fila->usuario->ultimo_acceso?->diffForHumans() ?? 'Nunca' }}</td>
                                        <td class="text-end">{{ $n($fila->entradas) }}</td>
                                        <td class="text-end">{{ $n($fila->cambios) }}</td>
                                        <td class="text-end">{{ $n($fila->chat) }}</td>
                                        <td>{{ $fila->ultimaAccion?->diffForHumans() ?? '—' }}</td>
                                        <td class="text-end pe-0"><a class="btn btn-sm btn-phoenix-secondary" href="{{ route('admin.bitacora.index', ['usuario' => $fila->usuario->id] + $filtroFechas) }}">Ver lo que hizo</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card h-100"><div class="card-body">
                        <h4 class="mb-1">Cambios por módulo</h4>
                        <p class="text-700 fs--1 mb-2">Qué partes del panel se tocaron más.</p>
                        <div data-grafica="modulos" style="min-height: 260px;"></div>
                    </div></div>
                </div>
                <div class="col-lg-7">
                    <div class="card h-100"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Últimos movimientos</h4>
                            <a class="fs--1 fw-bold" href="{{ route('admin.bitacora.index', $filtroFechas) }}">Bitácora completa</a>
                        </div>
                        @include('admin.bitacora.lista', ['registros' => $usuarios['recientes']])
                    </div></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendors')
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>
@endpush
@push('scripts')
    @php
        $datosGraficas = [
            'dias' => $etiquetas,
            'resumen' => ['visitantes' => $visitas['serie']['visitantes'], 'solicitudes' => collect($solicitudes['serie'])->reduce(fn ($suma, $s) => array_map(fn ($a, $b) => $a + $b, $suma, $s), array_fill(0, count($etiquetas), 0))],
            'visitas' => $visitas['serie'],
            'origenes' => $visitas['origenes']->map(fn ($v, $k) => ['name' => Visitas::NOMBRES_ORIGEN[$k][0] ?? $k, 'value' => $v])->values(),
            'dispositivos' => $visitas['dispositivos']->map(fn ($v, $k) => ['name' => Visitas::NOMBRES_DISPOSITIVO[$k] ?? $k, 'value' => $v])->values(),
            'navegadores' => ['nombres' => $visitas['navegadores']->keys(), 'valores' => $visitas['navegadores']->values()],
            'horas' => $visitas['horas'],
            'semana' => $visitas['semana'],
            'solicitudes' => collect($solicitudes['serie'])->map(fn ($serie, $tipo) => ['name' => MensajeContacto::TIPOS[$tipo][0], 'data' => $serie])->values(),
            'chat' => $chat['serie'],
            'correos' => $correos['serie'],
            'modulos' => ['nombres' => $usuarios['porModulo']->keys(), 'valores' => $usuarios['porModulo']->values()],
        ];
    @endphp
    <script>
        (function () {
            var d = @json($datosGraficas);
            var css = getComputedStyle(document.documentElement);
            var color = function (n, def) { return css.getPropertyValue('--phoenix-' + n).trim() || def; };
            var c = { primary: color('primary', '#3874ff'), info: color('info', '#0097eb'), success: color('success', '#25b003'), warning: color('warning', '#e5780b'), danger: color('danger', '#ec1f00'), gris: color('gray-600', '#8a94ad'), gris2: color('gray-300', '#cbd0dd') };
            var paleta = [c.primary, c.success, c.warning, c.info, c.danger, '#9b59b6', c.gris, '#16a085', '#e67e22', c.gris2];
            var eje = function (datos) { return { type: 'category', data: datos, axisLabel: { color: c.gris }, axisLine: { show: false }, axisTick: { show: false } }; };
            var valores = { type: 'value', minInterval: 1, axisLabel: { color: c.gris }, splitLine: { lineStyle: { type: 'dashed', opacity: .4 } } };
            var base = function (extra) { return Object.assign({ color: paleta, tooltip: { trigger: 'axis' }, grid: { left: 36, right: 12, top: 30, bottom: 30 }, legend: { top: 0, textStyle: { color: c.gris } } }, extra); };
            var barras = function (nombres, vals, horizontal) {
                return base({ legend: { show: false }, grid: { left: horizontal ? 100 : 36, right: 16, top: 10, bottom: 30 },
                    xAxis: horizontal ? valores : eje(nombres), yAxis: horizontal ? eje(nombres) : valores,
                    series: [{ type: 'bar', data: vals, barMaxWidth: 22, itemStyle: { color: c.primary, borderRadius: horizontal ? [0, 4, 4, 0] : [4, 4, 0, 0] } }] });
            };
            var dona = function (datos) {
                return { color: paleta, tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' }, legend: { bottom: 0, textStyle: { color: c.gris } },
                    series: [{ type: 'pie', radius: ['50%', '75%'], center: ['50%', '42%'], avoidLabelOverlap: true, label: { show: false }, data: datos.length ? datos : [{ name: 'Sin datos', value: 0 }] }] };
            };
            var linea = function (nombre, datos, col) { return { name: nombre, type: 'line', smooth: true, data: datos, symbol: 'none', lineStyle: { width: 3, color: col }, itemStyle: { color: col }, areaStyle: { opacity: .08, color: col } }; };
            var apilada = function (nombre, datos, col) { return { name: nombre, type: 'bar', stack: 'total', data: datos, barMaxWidth: 18, itemStyle: { color: col } }; };

            var definiciones = {
                resumen: function () { return base({ xAxis: eje(d.dias), yAxis: valores, series: [linea('Visitantes', d.resumen.visitantes, c.primary), { name: 'Solicitudes', type: 'bar', data: d.resumen.solicitudes, barMaxWidth: 14, itemStyle: { color: c.success, borderRadius: [3, 3, 0, 0] } }] }); },
                visitas: function () { return base({ xAxis: eje(d.dias), yAxis: valores, series: [linea('Páginas vistas', d.visitas.vistas, c.info), linea('Visitantes', d.visitas.visitantes, c.primary)] }); },
                origenes: function () { return dona(d.origenes); },
                dispositivos: function () { return dona(d.dispositivos); },
                navegadores: function () { return barras(d.navegadores.nombres, d.navegadores.valores, true); },
                horas: function () { return barras(Array.from({ length: 24 }, function (_, h) { return h + 'h'; }), d.horas); },
                semana: function () { return barras(['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'], d.semana); },
                solicitudes: function () { var cols = [c.gris, c.info, c.warning]; return base({ xAxis: eje(d.dias), yAxis: valores, series: d.solicitudes.map(function (s, i) { return apilada(s.name, s.data, cols[i]); }) }); },
                chat: function () { return base({ legend: { show: false }, xAxis: eje(d.dias), yAxis: valores, series: [{ name: 'Conversaciones', type: 'bar', data: d.chat, barMaxWidth: 18, itemStyle: { color: c.warning, borderRadius: [4, 4, 0, 0] } }] }); },
                correos: function () { return base({ xAxis: eje(d.dias), yAxis: valores, series: [apilada('Enviados', d.correos.enviado, c.success), apilada('Fallidos o no enviados', d.correos.fallido, c.danger)] }); },
                modulos: function () { return barras(d.modulos.nombres, d.modulos.valores, true); }
            };

            var graficas = [];
            // Solo se dibujan las de la pestaña visible (en una oculta ECharts no sabe el tamaño).
            function dibujar(panel) {
                panel.querySelectorAll('[data-grafica]').forEach(function (el) {
                    if (el.dataset.lista) return;
                    el.dataset.lista = '1';
                    var g = echarts.init(el);
                    g.setOption(definiciones[el.dataset.grafica]());
                    graficas.push(g);
                });
            }
            document.addEventListener('DOMContentLoaded', function () {
                var pestanas = document.querySelectorAll('#sgtPestanas [data-bs-toggle="tab"]');
                pestanas.forEach(function (a) {
                    a.addEventListener('shown.bs.tab', function () {
                        dibujar(document.querySelector(a.getAttribute('href')));
                        history.replaceState(null, '', a.getAttribute('href').replace('#p-', '#'));
                    });
                });
                // Volver a la pestaña de la dirección (#visitas…), también al cambiar el rango.
                var inicial = location.hash && document.querySelector('#sgtPestanas [href="#p-' + location.hash.slice(1) + '"]');
                if (inicial) {
                    bootstrap.Tab.getOrCreateInstance(inicial).show();
                } else {
                    dibujar(document.getElementById('p-resumen'));
                }
                document.querySelectorAll('a[href*="/admin/estadisticas?"], form[action$="/admin/estadisticas"]').forEach(function (el) {
                    el.addEventListener(el.tagName === 'FORM' ? 'submit' : 'click', function () {
                        if (!location.hash) return;
                        if (el.tagName === 'FORM') { el.action = el.action.split('#')[0] + location.hash; } else { el.href = el.href.split('#')[0] + location.hash; }
                    });
                });
                window.addEventListener('resize', function () { graficas.forEach(function (g) { g.resize(); }); });
            });
        })();
    </script>
@endpush
