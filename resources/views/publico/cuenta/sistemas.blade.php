@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Mis sistemas'])

@section('portal')
    @if ($contratos->isEmpty())
        <div class="card"><div class="card-body text-center py-6">
            <span class="fa-solid fa-laptop-code fs-3 text-400 mb-3 d-block"></span>
            <h5>Todavía no tienes sistemas contratados</h5>
            <p class="text-700 mb-4">Cuando contrates uno, aquí verás su acceso, hasta cuándo está vigente y sus manuales, incluidos los exclusivos para clientes.</p>
            <a class="btn btn-primary" href="{{ \App\Support\Sitio::enlaceBloque('sistemas') }}">Ver nuestro software</a>
        </div></div>
    @else
        <div class="row g-3">
            @foreach ($contratos as $contrato)
                <div class="col-md-6 col-xl-4">@include('publico.cuenta.partes.sistema', ['manualesSistema' => $manuales[$contrato->sistema_id] ?? collect()])</div>
            @endforeach
        </div>
    @endif
@endsection
