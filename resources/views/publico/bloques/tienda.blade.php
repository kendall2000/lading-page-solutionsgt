{{-- Productos en venta (Panel → Ventas → Productos y precios), con botones de compra o suscripción por PayPal. --}}
@php
    $productosBloque = $datos['productos']
        ->when($s->opcion('tipo_producto'), fn ($c, $tipo) => $c->where('tipo', $tipo))
        ->when((int) $s->opcion('limite') > 0, fn ($c) => $c->take((int) $s->opcion('limite')));
@endphp
@if ($productosBloque->isNotEmpty())
    <section class="py-10 {{ $fondo }}" id="s{{ $s->id }}">
        <div class="container-small px-lg-7 px-xxl-3">
            @include('publico.bloques._titulo')
            @include('publico.partes.productos', ['productos' => $productosBloque])
        </div>
    </section>
@endif
