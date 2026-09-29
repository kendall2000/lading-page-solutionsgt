<div class="col-6 col-sm-4 col-md-3 col-xl-2">
    <div class="card h-100 border-0 shadow-sm sgt-tarjeta text-center" @if ($e->texto) title="{{ $e->texto }}" @endif>
        <div class="card-body px-2 py-4 d-flex flex-column align-items-center justify-content-center">
            <div class="mb-2 d-flex align-items-center justify-content-center" style="height: 48px">
                @include('publico.bloques._icono', ['e' => $e, 'alto' => 44, 'clase' => 'sgt-icono-sm'])
            </div>
            <h6 class="mb-0 text-1000">{{ $e->titulo }}</h6>
            @if ($e->grupo)<span class="fs--2 text-600">{{ $e->grupo }}</span>@endif
        </div>
    </div>
</div>
