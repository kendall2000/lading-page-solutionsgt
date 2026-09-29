{{-- Lista corta de movimientos de la bitácora (inicio y estadísticas). --}}
@forelse ($registros as $r)
    @php [$accionTexto, $accionColor, $accionIcono] = $r->accionInfo(); @endphp
    <div class="d-flex align-items-start py-2 {{ $loop->last ? '' : 'border-bottom border-200' }}">
        <div class="d-flex flex-center rounded-circle bg-soft-{{ $accionColor }} me-2 flex-shrink-0" style="width:2rem;height:2rem;">
            <span class="text-{{ $accionColor }}" data-feather="{{ $accionIcono }}" style="width:14px;height:14px;"></span>
        </div>
        <div class="flex-1 fs--1">
            <span class="fw-semi-bold text-900">{{ $r->usuario }}</span>
            <span class="text-700">{{ \Illuminate\Support\Str::lower($accionTexto) }}</span>
            @if (in_array($r->accion, ['crear', 'editar', 'borrar'], true))<span class="text-900">{{ $r->descripcion }}</span>@endif
            @if ($r->cambios)<span class="text-600">({{ implode(', ', array_keys($r->cambios)) }})</span>@endif
            <div class="text-600 fs--2">{{ $r->modulo }} · {{ $r->created_at->diffForHumans() }}</div>
        </div>
    </div>
@empty
    <p class="text-700 fs--1 mb-0">Sin movimientos en este periodo.</p>
@endforelse
