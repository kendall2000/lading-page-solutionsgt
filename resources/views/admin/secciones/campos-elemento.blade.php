{{-- Campos de un elemento según el tipo de sección. Parámetros: campos (campo => etiqueta), e, sufijo. --}}
<div class="row g-2">
    @foreach ($campos as $campo => $etiqueta)
        @php $id = $campo.'_'.$sufijo; @endphp
        @switch($campo)
            @case('imagen')
                <div class="col-12">
                    @include('admin.partes.imagen', ['campo' => 'imagen', 'idCampo' => $id, 'titulo' => $etiqueta, 'ayuda' => null, 'url' => $e->exists ? $e->url() : null])
                </div>
                @break
            @case('texto')
                <div class="col-12">
                    <label class="form-label" for="{{ $id }}">{{ $etiqueta }}</label>
                    <textarea class="form-control form-control-sm" id="{{ $id }}" name="texto" rows="3" maxlength="5000">{{ $e->texto }}</textarea>
                </div>
                @break
            @case('icono')
                <div class="col-md-6">
                    <label class="form-label" for="{{ $id }}">{{ $etiqueta }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="width: 2.5rem"><span class="{{ $e->icono }}" id="vista_{{ $id }}"></span></span>
                        <input class="form-control" id="{{ $id }}" name="icono" value="{{ $e->icono }}" maxlength="60" placeholder="fa-solid fa-code" oninput="document.getElementById('vista_{{ $id }}').className = this.value" />
                    </div>
                    <div class="form-text">De <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" rel="noopener">Font Awesome</a>: p. ej. <code>fa-brands fa-php</code>, <code>fa-solid fa-cart-shopping</code>.</div>
                </div>
                @break
            @case('grupo')
                <div class="col-md-6">
                    <label class="form-label" for="{{ $id }}">{{ $etiqueta }}</label>
                    <input class="form-control form-control-sm" id="{{ $id }}" name="grupo" value="{{ $e->grupo }}" maxlength="80" list="grupos{{ $seccion->id }}" />
                </div>
                @break
            @default
                <div class="{{ in_array($campo, ['titulo', 'enlace'], true) ? 'col-md-6' : 'col-md-3' }}">
                    <label class="form-label" for="{{ $id }}">{{ $etiqueta }}</label>
                    <input class="form-control form-control-sm" id="{{ $id }}" name="{{ $campo }}" value="{{ $e->{$campo} }}" maxlength="{{ $campo === 'enlace' ? 255 : 200 }}"
                           @if ($campo === 'enlace') placeholder="/software  o  https://…" @endif />
                </div>
        @endswitch
    @endforeach
</div>
