{{-- Elementos repetibles de la sección (fotos, tarjetas, tecnologías, cifras, preguntas…). --}}
@php
    $campos = $elem['campos'];
    $grupos = $seccion->elementos->pluck('grupo')->filter()->unique()->values();
@endphp
<div class="card mt-4" id="elementos">
    <div class="card-body">
        <h4 class="mb-1">{{ $elem['nombre'] }} <span class="text-600 fw-normal fs-0">({{ $seccion->elementos->count() }})</span></h4>
        <p class="text-700 fs--1 mb-4">Se muestran en este orden. Toca uno para editarlo.</p>

        @if ($grupos->isNotEmpty())
            <datalist id="grupos{{ $seccion->id }}">
                @foreach ($grupos as $g)<option value="{{ $g }}"></option>@endforeach
            </datalist>
        @endif

        <div class="accordion mb-4" id="listaElementos">
            @foreach ($seccion->elementos as $e)
                <div class="accordion-item border border-300 rounded-3 mb-2 {{ $e->visible ? '' : 'opacity-75' }}" id="e{{ $e->id }}">
                    <div class="d-flex align-items-center gap-2 px-3 py-2">
                        <div class="d-flex flex-column">
                            @foreach (['arriba' => 'up', 'abajo' => 'down'] as $dir => $flecha)
                                <form method="POST" action="{{ route('admin.elementos.mover', [$e, $dir]) }}">
                                    @csrf
                                    <button class="btn btn-link p-0 text-600 lh-1" type="submit" title="Mover {{ $dir }}" @disabled(($dir === 'arriba' && $loop->parent->first) || ($dir === 'abajo' && $loop->parent->last))><span class="fa-solid fa-caret-{{ $flecha }}"></span></button>
                                </form>
                            @endforeach
                        </div>
                        <div class="border border-200 rounded-2 d-flex flex-center bg-light flex-shrink-0" style="width: 56px; height: 42px; overflow: hidden;">
                            @if ($e->imagen)
                                <img src="{{ $e->url() }}" alt="" style="max-width: 100%; max-height: 100%; object-fit: cover;" />
                            @elseif ($e->icono)
                                <span class="{{ $e->icono }} text-primary fs-1"></span>
                            @elseif ($e->valor)
                                <span class="fw-bold text-primary">{{ $e->valor }}{{ $e->subtitulo }}</span>
                            @else
                                <span class="fa-regular fa-square text-400"></span>
                            @endif
                        </div>
                        <button class="btn btn-link text-start text-1000 fw-bold flex-1 text-decoration-none collapsed px-2" type="button" data-bs-toggle="collapse" data-bs-target="#editar{{ $e->id }}">
                            {{ $e->titulo ?: ($e->texto ? \Illuminate\Support\Str::limit($e->texto, 60) : 'Sin título') }}
                            @if ($e->grupo)<span class="badge badge-phoenix badge-phoenix-secondary ms-2">{{ $e->grupo }}</span>@endif
                            @unless ($e->visible)<span class="badge badge-phoenix badge-phoenix-warning ms-2">Oculto</span>@endunless
                        </button>
                        <form method="POST" action="{{ route('admin.elementos.destroy', $e) }}" onsubmit="return confirm('¿Eliminar?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link text-danger p-1" type="submit" title="Eliminar"><span class="fa-solid fa-trash"></span></button>
                        </form>
                    </div>
                    <div class="collapse {{ old('_elemento') == $e->id ? 'show' : '' }}" id="editar{{ $e->id }}" data-bs-parent="#listaElementos">
                        <form class="border-top border-200 p-3" method="POST" action="{{ route('admin.elementos.update', $e) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_elemento" value="{{ $e->id }}" />
                            @include('admin.secciones.campos-elemento', ['e' => $e, 'sufijo' => $e->id])
                            <div class="d-flex align-items-center gap-3 mt-3">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" id="visible{{ $e->id }}" name="visible" type="checkbox" value="1" @checked($e->visible) />
                                    <label class="form-check-label" for="visible{{ $e->id }}">Visible</label>
                                </div>
                                <button class="btn btn-primary btn-sm ms-auto" type="submit">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border border-dashed border-primary rounded-3 p-3">
            <h5 class="mb-3"><span class="fa-solid fa-plus text-primary me-2"></span>Agregar {{ $elem['uno'] ?? 'elemento' }}</h5>
            <form method="POST" action="{{ route('admin.elementos.store', $seccion) }}" enctype="multipart/form-data">
                @csrf
                @include('admin.secciones.campos-elemento', ['e' => new \App\Models\Elemento(), 'sufijo' => 'nuevo'])
                <button class="btn btn-primary btn-sm mt-3" type="submit">Agregar</button>
            </form>
        </div>
    </div>
</div>
