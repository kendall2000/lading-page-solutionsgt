{{-- Tecnologías agrupadas por categoría (Lenguajes, Frameworks, Bases de datos…), con pestañas si hay varias. --}}
@php $grupos = $s->elementos->groupBy(fn ($e) => $e->grupo ?: 'Tecnologías'); @endphp
<section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        @include('publico.bloques._titulo')
        @if ($grupos->count() > 1)
            <ul class="nav nav-pills justify-content-center flex-wrap gap-2 mb-6" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#tec{{ $s->id }}-todo" type="button" role="tab">Todas</button></li>
                @foreach ($grupos->keys() as $i => $grupo)
                    <li class="nav-item" role="presentation"><button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#tec{{ $s->id }}-{{ $i }}" type="button" role="tab">{{ $grupo }}</button></li>
                @endforeach
            </ul>
        @endif
        @php
            $tarjeta = fn ($e) => view('publico.bloques._tecnologia', ['e' => $e]);
        @endphp
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tec{{ $s->id }}-todo" role="tabpanel">
                <div class="row g-3 justify-content-center">
                    @foreach ($s->elementos as $e){{ $tarjeta($e) }}@endforeach
                </div>
            </div>
            @if ($grupos->count() > 1)
                @foreach ($grupos->values() as $i => $items)
                    <div class="tab-pane fade" id="tec{{ $s->id }}-{{ $i }}" role="tabpanel">
                        <div class="row g-3 justify-content-center">
                            @foreach ($items as $e){{ $tarjeta($e) }}@endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
