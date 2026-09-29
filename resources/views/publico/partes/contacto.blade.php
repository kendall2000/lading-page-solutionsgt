@php $principal = $direcciones->first(); @endphp
<section class="pb-10 pb-xl-14" id="contacto">
    <div class="container-small px-lg-7 px-xxl-3">
        <div class="text-center mb-7">
            <h5 class="text-info mb-3">Contacto</h5>
            <h2 class="mb-2">Hablemos de tu proyecto</h2>
        </div>
        @if ($principal)
            <div class="row">
                <div class="col-12 mb-10">
                    <iframe class="sgt-mapa" src="{{ $principal->mapaEmbebido() }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa: {{ $principal->nombre }}"></iframe>
                </div>
            </div>
        @endif
        <div class="row g-5 g-lg-5">
            <div class="col-md-6 mb-5 mb-md-0 text-center text-md-start">
                <h3 class="mb-3">Estemos en contacto</h3>
                <p class="mb-5">Escríbeme y te respondo lo antes posible. Con gusto te muestro los sistemas funcionando y vemos juntos cuál se adapta a tu negocio.</p>
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
            <div class="col-md-6 text-center text-md-start">
                <h3 class="mb-3">Envíame un mensaje</h3>
                <p class="mb-5">Cuéntame qué necesitas: qué tipo de negocio tienes y qué te gustaría controlar mejor.</p>
                @if (session('contacto_ok'))
                    <div class="alert alert-soft-success text-start" role="alert">
                        <span class="fa-solid fa-circle-check me-2"></span>¡Gracias! Recibí tu mensaje y te contactaré pronto.
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-soft-danger text-start py-2 fs--1" role="alert">
                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif
                <form class="row g-3 text-start" method="POST" action="{{ route('contacto') }}">
                    @csrf
                    {{-- Campo trampa para robots: oculto a las personas. --}}
                    <div style="position:absolute; left:-10000px;" aria-hidden="true">
                        <label for="empresa_web">No llenar</label>
                        <input type="text" id="empresa_web" name="empresa_web" tabindex="-1" autocomplete="off" />
                    </div>
                    <div class="col-sm-6">
                        <input class="form-control bg-white dark__bg-1100" type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Tu nombre *" required maxlength="120" aria-label="Nombre" />
                    </div>
                    <div class="col-sm-6">
                        <input class="form-control bg-white dark__bg-1100" type="text" name="empresa" value="{{ old('empresa') }}" placeholder="Empresa o negocio" maxlength="150" aria-label="Empresa" />
                    </div>
                    <div class="col-sm-6">
                        <input class="form-control bg-white dark__bg-1100" type="email" name="correo" value="{{ old('correo') }}" placeholder="Correo *" required maxlength="150" aria-label="Correo" />
                    </div>
                    <div class="col-sm-6">
                        <input class="form-control bg-white dark__bg-1100" type="tel" name="telefono" value="{{ old('telefono') }}" placeholder="Teléfono" maxlength="30" aria-label="Teléfono" />
                    </div>
                    @if ($sistemas->isNotEmpty())
                        <div class="col-12">
                            <select class="form-select bg-white dark__bg-1100" name="sistema_id" aria-label="Sistema de interés">
                                <option value="">¿Qué sistema te interesa? (opcional)</option>
                                @foreach ($sistemas as $s)
                                    <option value="{{ $s->id }}" @selected((string) old('sistema_id', request('sistema')) === (string) $s->id)>{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-12">
                        <textarea class="form-control bg-white dark__bg-1100" rows="5" name="mensaje" placeholder="Tu mensaje *" required maxlength="3000" aria-label="Mensaje">{{ old('mensaje') }}</textarea>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>Enviar mensaje</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
