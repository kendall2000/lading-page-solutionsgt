{{-- Contacto: datos, direcciones, redes, mapa y formulario (contacto, demostración o prueba). --}}
@php
    $direcciones = $datos['direcciones'];
    $principal = $direcciones->first();
    $sistemasForm = $datos['sistemas'];
@endphp
<section class="py-10 {{ $fondo }}" id="contacto">
    <div class="container-small px-lg-7 px-xxl-3">
        @include('publico.bloques._titulo', ['conTexto' => false])
        @if ($principal && $s->opcion('mapa', true))
            <div class="mb-10">
                <iframe class="sgt-mapa" src="{{ $principal->mapaEmbebido() }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa: {{ $principal->nombre }}"></iframe>
            </div>
        @endif
        <div class="row g-5">
            <div class="col-md-6 text-center text-md-start">
                <h3 class="mb-3">Estemos en contacto</h3>
                <div class="mb-5 sgt-contenido">
                    @if ($s->contenido){{ $s->contenidoHtml() }}@else<p>Escríbenos y te respondemos lo antes posible. Con gusto te mostramos los sistemas funcionando.</p>@endif
                </div>
                <div class="d-flex flex-column align-items-center align-items-md-start gap-3">
                    @if ($cfg->telefono)
                        <div class="d-md-flex align-items-center">
                            <div class="icon-wrapper shadow-info"><span class="uil uil-phone text-primary light fs-4 z-index-1 ms-2"></span></div>
                            <div class="flex-1 ms-3"><a class="link-900" href="tel:{{ preg_replace('/[^\d+]/', '', $cfg->telefono) }}">{{ $cfg->telefono }}</a></div>
                        </div>
                    @endif
                    @if ($wa = $cfg->enlaceWhatsapp())
                        <div class="d-md-flex align-items-center">
                            <div class="icon-wrapper shadow-info"><span class="uil uil-whatsapp text-primary light fs-4 z-index-1 ms-2"></span></div>
                            <div class="flex-1 ms-3"><a class="fw-semi-bold text-900" href="{{ $wa }}" target="_blank" rel="noopener">WhatsApp {{ $cfg->whatsapp }}</a></div>
                        </div>
                    @endif
                    @if ($cfg->correo)
                        <div class="d-md-flex align-items-center">
                            <div class="icon-wrapper shadow-info"><span class="uil uil-envelope text-primary light fs-4 z-index-1 ms-2"></span></div>
                            <div class="flex-1 ms-3"><a class="fw-semi-bold text-900" href="mailto:{{ $cfg->correo }}">{{ $cfg->correo }}</a></div>
                        </div>
                    @endif
                    @if ($cfg->horario)
                        <div class="d-md-flex align-items-center">
                            <div class="icon-wrapper shadow-info"><span class="uil uil-clock text-primary light fs-4 z-index-1 ms-2"></span></div>
                            <div class="flex-1 ms-3 text-900">{{ $cfg->horario }}</div>
                        </div>
                    @endif
                    @foreach ($direcciones as $dir)
                        <div class="d-md-flex align-items-center">
                            <div class="icon-wrapper shadow-info"><span class="uil uil-map-marker text-primary light fs-4 z-index-1 ms-2"></span></div>
                            <div class="flex-1 ms-3">
                                <a class="fw-semi-bold text-900" href="{{ $dir->enlaceMapa() }}" target="_blank" rel="noopener">{{ $dir->nombre }}</a>
                                <div class="fs--1 text-700">{{ $dir->direccion }}{{ $dir->ciudad ? ', '.$dir->ciudad : '' }}</div>
                                @if ($dir->telefono || $dir->horario)
                                    <div class="fs--1 text-700">{{ collect([$dir->telefono, $dir->horario])->filter()->implode(' · ') }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if ($cfg->redes())
                        <div class="d-flex mt-3">
                            @foreach ($cfg->redes() as $red => [$enlace, $icono])
                                <a href="{{ $enlace }}" target="_blank" rel="noopener" title="{{ ucfirst($red) }}"><span class="{{ $icono }} fs-2 text-primary me-3"></span></a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <h3 class="mb-3 text-center text-md-start">Envíanos un mensaje</h3>
                @include('publico.partes.formulario', ['sistemasForm' => $sistemasForm, 'ancla' => 'contacto'])
            </div>
        </div>
    </div>
</section>
