{{-- Preguntas frecuentes (acordeón de Bootstrap). --}}
<section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
    <div class="container-small px-lg-7 px-xxl-3">
        @include('publico.bloques._titulo')
        <div class="accordion mx-auto" id="preguntas{{ $s->id }}" style="max-width: 52rem">
            @foreach ($s->elementos as $e)
                <div class="accordion-item border-top border-300 {{ $loop->last ? 'border-bottom' : '' }}">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fs-0 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#p{{ $e->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="p{{ $e->id }}">{{ $e->titulo }}</button>
                    </h2>
                    <div class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" id="p{{ $e->id }}" data-bs-parent="#preguntas{{ $s->id }}">
                        <div class="accordion-body pt-0 text-800 sgt-contenido">{{ \App\Models\Seccion::markdown($e->texto) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
