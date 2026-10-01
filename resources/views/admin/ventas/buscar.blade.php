{{-- Buscador de la pantalla de ventas (por correo, producto o referencia de PayPal). --}}
<form class="mb-3" method="GET" action="{{ route('admin.ventas.index') }}">
    <input type="hidden" name="ver" value="{{ $ver }}" />
    @if ($ver === 'suscripciones' && $estadoSus)<input type="hidden" name="estado" value="{{ $estadoSus }}" />@endif
    @if ($ver === 'pagos' && $estadoPago)<input type="hidden" name="pago" value="{{ $estadoPago }}" />@endif
    <div class="search-box w-100" style="max-width: 360px">
        <div class="position-relative">
            <input class="form-control search-input" type="search" name="q" value="{{ $buscar }}" placeholder="Correo, producto o referencia de PayPal" aria-label="Buscar" />
            <span class="fas fa-search search-box-icon"></span>
        </div>
    </div>
</form>
