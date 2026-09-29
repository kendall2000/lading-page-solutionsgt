@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Mi cuenta'])

@section('portal')
    @if ($pruebas->isNotEmpty())
        <h4 class="mb-3">Tus pruebas activas</h4>
        <div class="row g-3 mb-5">
            @foreach ($pruebas as $prueba)
                <div class="col-md-6 col-xl-4">@include('publico.cuenta.partes.prueba')</div>
            @endforeach
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tus sistemas</h4>
        @if ($contratos->isNotEmpty())<a class="fs--1 fw-bold" href="{{ route('cuenta.sistemas') }}">Ver todos</a>@endif
    </div>
    @if ($contratos->isEmpty())
        <div class="card mb-5"><div class="card-body text-center py-5">
            <span class="fa-solid fa-laptop-code fs-3 text-400 mb-3 d-block"></span>
            <p class="text-700 mb-3">Todavía no tienes sistemas contratados. Cuando contrates uno, aquí verás su acceso, vigencia y manuales.</p>
            <a class="btn btn-phoenix-primary btn-sm" href="{{ \App\Support\Sitio::enlaceBloque('sistemas') }}">Ver nuestro software</a>
        </div></div>
    @else
        <div class="row g-3 mb-5">
            @foreach ($contratos->take(3) as $contrato)
                <div class="col-md-6 col-xl-4">@include('publico.cuenta.partes.sistema', ['manualesSistema' => null])</div>
            @endforeach
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Conversaciones</h4>
                    <a class="fs--1 fw-bold" href="{{ route('cuenta.conversaciones') }}">Ver todas</a>
                </div>
                @forelse ($conversaciones as $c)
                    @include('publico.cuenta.partes.conversacion')
                @empty
                    <p class="text-700 fs--1 mb-3">Todavía no has chateado con nosotros.</p>
                    <a class="btn btn-phoenix-primary btn-sm" href="{{ route('cuenta.conversaciones') }}#nueva"><span class="fa-solid fa-comment-dots me-2"></span>Escribirnos</a>
                @endforelse
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Últimas solicitudes</h4>
                    <a class="fs--1 fw-bold" href="{{ route('cuenta.solicitudes') }}">Ver todas</a>
                </div>
                @forelse ($solicitudes as $s)
                    @php [$estadoTexto, $estadoColor] = $s->estadoCliente(); @endphp
                    <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom border-200' }}">
                        <div class="fs--1">
                            <span class="fw-semi-bold text-1000">{{ $s->tipoInfo()[0] }}</span>
                            @if ($s->sistema)<span class="text-700">· {{ $s->sistema->nombre }}</span>@endif
                            <div class="text-600 fs--2">{{ $s->created_at->translatedFormat('j M Y') }}</div>
                        </div>
                        <span class="badge badge-phoenix badge-phoenix-{{ $estadoColor }}">{{ $estadoTexto }}</span>
                    </div>
                @empty
                    <p class="text-700 fs--1 mb-3">No has enviado solicitudes. ¿Quieres ver un sistema funcionando?</p>
                    <a class="btn btn-phoenix-primary btn-sm" href="{{ \App\Support\Sitio::enlaceContacto() }}">Pedir una demostración</a>
                @endforelse
            </div></div>
        </div>
        <div class="col-12">
            <a class="card text-decoration-none" href="{{ route('cuenta.manuales') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="sgt-icono sgt-icono-sm"><span class="fa-solid fa-book"></span></span>
                    <div class="flex-1">
                        <h5 class="mb-0 text-1000">Manuales</h5>
                        <p class="fs--1 text-700 mb-0">{{ $manuales }} {{ $manuales === 1 ? 'manual disponible' : 'manuales disponibles' }} para tus sistemas.</p>
                    </div>
                    <span class="fa-solid fa-chevron-right text-600"></span>
                </div>
            </a>
        </div>
    </div>
@endsection
