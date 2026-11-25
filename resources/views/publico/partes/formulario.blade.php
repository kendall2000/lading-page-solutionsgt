{{--
    Formulario de contacto / demostración / prueba. Parámetros: sistemasForm (lista para elegir),
    sistemaFijo (Sistema, en la página de un sistema), ancla (a dónde vuelve), corto (bool, versión compacta).
--}}
@php
    $fijo = $sistemaFijo ?? null;
    $esCorto = (bool) ($corto ?? false);
    $tipoInicial = old('tipo', request('tipo', $fijo ? 'demo' : 'contacto'));
    $tipoInicial = array_key_exists($tipoInicial, \App\Models\MensajeContacto::TIPOS) ? $tipoInicial : 'contacto';
    if ($tipoInicial === 'prueba' && $fijo && ! $fijo->ofrecePrueba()) {
        $tipoInicial = 'demo';
    }
    if ($tipoInicial === 'demo' && $fijo && ! $fijo->ofreceDemo()) {
        $tipoInicial = $fijo->ofrecePrueba() ? 'prueba' : 'contacto';
    }
    $ok = session('contacto_ok');
    // Cliente con sesión: sus datos ya escritos (la solicitud queda en «Mi cuenta»).
    $cuentaForm = auth('cliente')->user();
    $textosBoton = [
        'demo' => 'Quiero mi demo gratis',
        'prueba' => 'Quiero probarlo gratis',
        'contacto' => 'Enviar consulta',
    ];
    $textoBotonInicial = $textosBoton[$tipoInicial] ?? 'Enviar consulta';
@endphp
@if ($ok)
    <div class="alert alert-soft-success" role="alert">
        <span class="fa-solid fa-circle-check me-2"></span>
        {{ match ($ok) {
            'demo' => '¡Gracias! Recibimos tu solicitud de demostración y te contactaremos para agendarla.',
            'prueba' => '¡Gracias! Recibimos tu solicitud de prueba. Te enviaremos tus datos de acceso pronto.',
            default => '¡Gracias! Recibimos tu mensaje y te contactaremos pronto.',
        } }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-soft-danger py-2 fs--1" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif
<form class="row g-3" method="POST" action="{{ route('contacto') }}" data-sgt-form-contacto>
    @csrf
    <input type="hidden" name="ancla" value="{{ $ancla ?? 'contacto' }}" />
    @if ($esCorto)
        <input type="hidden" name="corto" value="1" />
    @endif
    {{-- Campo trampa para robots: oculto a las personas. --}}
    <div style="position:absolute; left:-10000px;" aria-hidden="true">
        <label for="empresa_web_{{ $ancla ?? 'c' }}">No llenar</label>
        <input type="text" id="empresa_web_{{ $ancla ?? 'c' }}" name="empresa_web" tabindex="-1" autocomplete="off" />
    </div>
    <input type="hidden" name="llegada" value="{{ \App\Support\Antispam::marca() }}" />
    @if ($fijo?->proximamente || $esCorto)
        {{-- Sistema en desarrollo o versión corta: tipo fijo. --}}
        <input type="hidden" name="tipo" value="contacto" />
    @else
        <div class="col-12">
            <label class="form-label">¿Qué necesitas?</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach (['contacto' => ['fa-envelope', 'Información'], 'demo' => ['fa-display', 'Una demostración'], 'prueba' => ['fa-flask', 'Probarlo gratis']] as $clave => [$icono, $texto])
                    @continue($clave === 'prueba' && $fijo && ! $fijo->ofrecePrueba())
                    @continue($clave === 'demo' && $fijo && ! $fijo->ofreceDemo())
                    @continue($clave === 'contacto' && $fijo && ($fijo->ofreceDemo() || $fijo->ofrecePrueba()))
                    <input class="btn-check" type="radio" name="tipo" id="tipo_{{ $clave }}_{{ $ancla ?? 'c' }}" value="{{ $clave }}" @checked($tipoInicial === $clave) />
                    <label class="btn btn-phoenix-secondary btn-sm" for="tipo_{{ $clave }}_{{ $ancla ?? 'c' }}"><span class="fa-solid {{ $icono }} me-1"></span>{{ $texto }}@if ($clave === 'prueba' && $fijo) ({{ $fijo->dias_prueba }} días)@endif</label>
                @endforeach
            </div>
        </div>
    @endif
    <div class="{{ $esCorto ? 'col-12' : 'col-sm-6' }}">
        <input class="form-control bg-white dark__bg-1100" type="text" name="nombre" value="{{ old('nombre', $cuentaForm?->nombre) }}" placeholder="Tu nombre *" required maxlength="120" aria-label="Nombre" />
    </div>
    @unless ($esCorto)
        <div class="col-sm-6">
            <input class="form-control bg-white dark__bg-1100" type="text" name="empresa" value="{{ old('empresa', $cuentaForm?->cliente?->empresa ?? $cuentaForm?->empresa) }}" placeholder="Empresa o negocio" maxlength="150" aria-label="Empresa" />
        </div>
    @endunless
    <div class="col-sm-6">
        <input class="form-control bg-white dark__bg-1100" type="tel" name="telefono" value="{{ old('telefono', $cuentaForm?->telefono) }}" placeholder="WhatsApp" maxlength="30" aria-label="WhatsApp" />
    </div>
    <div class="col-sm-6">
        <input class="form-control bg-white dark__bg-1100" type="email" name="correo" value="{{ old('correo', $cuentaForm?->correo) }}" placeholder="Correo (opcional)" maxlength="150" aria-label="Correo" />
    </div>
    <div class="col-12 mt-1">
        <p class="fs--1 text-700 mb-0">Déjanos tu WhatsApp o tu correo para responderte.</p>
    </div>
    @if ($fijo)
        <input type="hidden" name="sistema_id" value="{{ $fijo->id }}" />
    @elseif (($sistemasForm ?? collect())->isNotEmpty())
        <div class="col-12">
            <select class="form-select bg-white dark__bg-1100" name="sistema_id" aria-label="Sistema de interés">
                <option value="">¿Qué sistema te interesa?{{ $esCorto ? ' (opcional)' : ' (obligatorio para demo o prueba)' }}</option>
                @foreach ($sistemasForm as $sf)
                    <option value="{{ $sf->id }}" @selected((string) old('sistema_id', request('sistema')) === (string) $sf->id)>{{ $sf->nombre }}{{ $sf->ofrecePrueba() ? ' · prueba '.$sf->dias_prueba.' días' : '' }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @unless ($esCorto)
        <div class="col-12">
            <textarea class="form-control bg-white dark__bg-1100" rows="4" name="mensaje" placeholder="Cuéntanos sobre tu negocio o qué necesitas{{ $fijo ? ' (opcional)' : '' }}" maxlength="3000" aria-label="Mensaje">{{ old('mensaje') }}</textarea>
        </div>
    @endunless
    <div class="col-12 d-grid">
        <button class="btn btn-primary" type="submit"><span class="fa-solid fa-paper-plane me-2"></span><span data-sgt-btn-texto>{{ $textoBotonInicial }}</span></button>
        @include('publico.partes.aviso-privacidad')
    </div>
</form>
@once
<script>
    document.addEventListener('change', function (e) {
        if (!e.target.matches('[data-sgt-form-contacto] input[name="tipo"]')) return;
        var textos = { demo: 'Quiero mi demo gratis', prueba: 'Quiero probarlo gratis', contacto: 'Enviar consulta' };
        var span = e.target.closest('form').querySelector('[data-sgt-btn-texto]');
        if (span) span.textContent = textos[e.target.value] || 'Enviar consulta';
    });
</script>
@endonce
