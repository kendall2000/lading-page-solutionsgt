@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Solicitudes y pruebas'])

@section('portal')
    @if ($pruebas->isNotEmpty())
        <h4 class="mb-3">Pruebas activas</h4>
        <div class="row g-3 mb-5">
            @foreach ($pruebas as $prueba)
                <div class="col-md-6 col-xl-4">@include('publico.cuenta.partes.prueba')</div>
            @endforeach
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Todas tus solicitudes</h4>
        <a class="btn btn-phoenix-primary btn-sm" href="{{ \App\Support\Sitio::enlaceContacto() }}"><span class="fa-solid fa-plus me-1"></span>Nueva solicitud</a>
    </div>
    <div class="card">
        <div class="card-body">
            @if ($solicitudes->isEmpty())
                <p class="text-700 mb-0">Todavía no has enviado solicitudes. Pide una demostración o una prueba desde la página de cada sistema.</p>
            @else
                <div class="table-responsive">
                    <table class="table fs--1 mb-0 align-middle">
                        <thead>
                            <tr><th class="ps-0">Fecha</th><th>Tipo</th><th>Sistema</th><th>Tu mensaje</th><th class="text-end pe-0">Estado</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($solicitudes as $s)
                                @php [$estadoTexto, $estadoColor] = $s->estadoCliente(); [$tipoTexto, $tipoColor] = $s->tipoInfo(); @endphp
                                <tr>
                                    <td class="ps-0 text-nowrap">{{ $s->created_at->translatedFormat('j M Y') }}</td>
                                    <td><span class="badge badge-phoenix badge-phoenix-{{ $tipoColor }}">{{ $tipoTexto }}</span></td>
                                    <td>@if ($s->sistema)<a href="{{ route('sistema', $s->sistema->slug) }}">{{ $s->sistema->nombre }}</a>@else — @endif</td>
                                    <td class="text-700" style="min-width: 14rem;">{{ \Illuminate\Support\Str::limit($s->mensaje, 120) }}</td>
                                    <td class="text-end pe-0">
                                        <span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span>
                                        @if ($s->tipo === 'prueba' && $s->credenciales_enviadas_en && $s->vence_el && $s->vence_el->isPast() && ! $s->vence_el->isToday())
                                            <div class="fs--2 text-600 mt-1">Prueba vencida</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $solicitudes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
