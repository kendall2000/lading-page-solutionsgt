{{-- Sistema contratado: estado, vigencia, acceso y (opcional) sus manuales. --}}
@php $diasContrato = $contrato->diasRestantes(); $vigenteContrato = $contrato->vigente(); @endphp
<div class="card h-100 {{ $vigenteContrato ? '' : 'opacity-75' }}">
    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-start gap-3 mb-3">
            <span class="sgt-icono sgt-icono-sm"><span class="{{ $contrato->sistema->icono }}"></span></span>
            <div class="flex-1">
                <h5 class="mb-1">{{ $contrato->sistema->nombre }}</h5>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge badge-phoenix badge-phoenix-{{ $vigenteContrato ? 'success' : 'secondary' }}">{{ $vigenteContrato ? 'Activo' : 'Vencido' }}</span>
                    @if ($contrato->plan)<span class="badge badge-phoenix badge-phoenix-info">{{ $contrato->plan }}</span>@endif
                    @if ($contrato->cliente_id)<span class="badge badge-phoenix badge-phoenix-secondary" title="Contratado por tu empresa">Empresa</span>@endif
                </div>
            </div>
        </div>
        <p class="fs--1 text-700 mb-2">
            @if ($contrato->desde)Desde {{ $contrato->desde->translatedFormat('j M Y') }} · @endif
            @if ($contrato->hasta)
                {{ $vigenteContrato ? 'Vence el' : 'Venció el' }} {{ $contrato->hasta->translatedFormat('j M Y') }}
                @if ($vigenteContrato && $diasContrato <= 30)<span class="text-warning fw-semi-bold">({{ $diasContrato }} {{ $diasContrato === 1 ? 'día' : 'días' }})</span>@endif
            @else
                Sin vencimiento
            @endif
        </p>
        @if ($contrato->notas)<p class="fs--1 text-800 mb-3" style="white-space: pre-line;">{{ $contrato->notas }}</p>@endif
        @if (! empty($manualesSistema) && $manualesSistema->isNotEmpty())
            <ul class="list-unstyled fs--1 mb-3">
                @foreach ($manualesSistema as $m)
                    <li class="mb-1"><a href="{{ route('manual', $m->slug) }}"><span class="fa-solid {{ $m->solo_clientes ? 'fa-lock' : 'fa-book' }} me-2 text-600"></span>{{ $m->titulo }}</a></li>
                @endforeach
            </ul>
        @endif
        <div class="mt-auto d-flex flex-wrap gap-2">
            @if ($contrato->url_acceso && $vigenteContrato)
                <a class="btn btn-primary btn-sm" href="{{ $contrato->url_acceso }}" target="_blank" rel="noopener noreferrer"><span class="fa-solid fa-arrow-up-right-from-square me-2"></span>Entrar al sistema</a>
            @endif
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('cuenta.conversaciones') }}#nueva"><span class="fa-solid fa-headset me-2"></span>Soporte</a>
        </div>
    </div>
</div>
