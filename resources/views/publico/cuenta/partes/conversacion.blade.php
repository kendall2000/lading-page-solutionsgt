{{-- Fila de una conversación del cliente ($c). --}}
<a class="d-flex align-items-start gap-3 py-2 text-decoration-none {{ $loop->last ? '' : 'border-bottom border-200' }}" href="{{ route('cuenta.conversacion', $c) }}">
    <span class="sgt-icono sgt-icono-sm flex-shrink-0"><span class="fa-solid {{ $c->estado === 'cerrada' ? 'fa-comment-slash' : 'fa-comments' }}"></span></span>
    <div class="flex-1 min-w-0">
        <div class="d-flex justify-content-between gap-2">
            <span class="fw-semi-bold text-1000 fs--1">
                {{ $c->estado === 'cerrada' ? 'Conversación cerrada' : 'Conversación abierta' }}
                @if ($c->no_leidos_visitante)<span class="badge badge-phoenix badge-phoenix-danger ms-1">{{ $c->no_leidos_visitante }} nuevo{{ $c->no_leidos_visitante === 1 ? '' : 's' }}</span>@endif
            </span>
            <span class="text-600 fs--2 text-nowrap">{{ ($c->ultimo_mensaje_en ?? $c->created_at)->diffForHumans() }}</span>
        </div>
        <p class="text-700 fs--1 mb-0 text-truncate">{{ \Illuminate\Support\Str::limit($c->ultimoMensaje?->cuerpo ?? '', 90) }}</p>
    </div>
</a>
