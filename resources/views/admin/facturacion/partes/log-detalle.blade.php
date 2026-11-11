{{--
    Detalle legible de una llamada a INFILE ($log): errores SAT en lista, JSON con sangría
    (sin los XML en base64) y los XML decodificados. Se usa en /admin/facturas/{id} y /admin/facturacion?ver=logs.
--}}
@php
    $erroresSat = $log->erroresSat();
    $descripcion = $log->descripcionRespuesta();
    $xmlsLog = $log->xmls();
    $pref = 'log' . $log->id;
@endphp

<div class="sgt-log">
    <div class="d-flex flex-wrap gap-3 fs--1 text-700 mb-3">
        <span><span class="fa-solid fa-gear me-1 text-500"></span>{{ $log->nombreAccion() }}</span>
        <span><span class="fa-solid fa-server me-1 text-500"></span>HTTP {{ $log->codigo_http ?: '—' }}</span>
        <span><span class="fa-solid fa-stopwatch me-1 text-500"></span>{{ $log->duracion_ms !== null ? $log->duracion_ms . ' ms' : '—' }}</span>
        <span><span class="fa-regular fa-clock me-1 text-500"></span>{{ $log->created_at?->format('d/m/Y H:i:s') }}</span>
        <span class="text-break"><span class="fa-solid fa-link me-1 text-500"></span><span class="font-monospace fs--2">{{ $log->url }}</span></span>
    </div>

    @if (! $log->exito)
        <div class="sgt-log-errores mb-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="fa-solid fa-circle-xmark text-danger"></span>
                <strong class="text-danger">{{ $descripcion ?: 'La llamada no fue exitosa' }}</strong>
            </div>
            @if ($erroresSat)
                <ol class="mb-0 ps-3">
                    @foreach ($erroresSat as $e)
                        <li class="mb-2">
                            <div class="text-1000 fw-semi-bold">{{ $e['mensaje'] }}</div>
                            @if ($e['fuente'] || $e['categoria'] || $e['numeral'] || $e['validacion'])
                                <div class="fs--2 text-600 mt-1">
                                    @if ($e['fuente']) <span class="badge badge-phoenix badge-phoenix-secondary me-1">{{ $e['fuente'] }}</span> @endif
                                    @if ($e['categoria']) Categoría {{ $e['categoria'] }} · @endif
                                    @if ($e['numeral']) Numeral {{ $e['numeral'] }} · @endif
                                    @if ($e['validacion']) Validación {{ $e['validacion'] }} @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @elseif ($log->error_mensaje && $log->error_mensaje !== $descripcion)
                <div class="text-1000 text-break">{{ $log->error_mensaje }}</div>
            @endif
        </div>
    @endif

    <ul class="nav nav-underline fs--1 mb-2" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#{{ $pref }}Resp" role="tab">Respuesta</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#{{ $pref }}Pet" role="tab">Petición enviada</button>
        </li>
        @if ($xmlsLog)
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#{{ $pref }}Xml" role="tab">XML ({{ count($xmlsLog) }})</button>
            </li>
        @endif
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="{{ $pref }}Resp" role="tabpanel">
            @include('admin.facturacion.partes.codigo', ['texto' => $log->respuestaLegible()])
        </div>
        <div class="tab-pane fade" id="{{ $pref }}Pet" role="tabpanel">
            @include('admin.facturacion.partes.codigo', ['texto' => $log->peticionLegible()])
        </div>
        @if ($xmlsLog)
            <div class="tab-pane fade" id="{{ $pref }}Xml" role="tabpanel">
                @foreach ($xmlsLog as $etiqueta => $xml)
                    <div class="fs--2 fw-bold text-700 mb-1 {{ $loop->first ? '' : 'mt-3' }}">{{ $etiqueta }}</div>
                    @include('admin.facturacion.partes.codigo', ['texto' => $xml])
                @endforeach
            </div>
        @endif
    </div>
</div>

@once
    <style>
        .sgt-log-errores {
            border: 1px solid var(--phoenix-danger-border-subtle, #f5c2c7);
            border-left: 4px solid var(--phoenix-danger, #dc3545);
            background: var(--phoenix-danger-bg-subtle, #fdf2f2);
            border-radius: .5rem;
            padding: .85rem 1rem;
            font-size: .875rem;
        }
        .sgt-codigo { position: relative; }
        .sgt-codigo pre {
            margin: 0;
            max-height: 360px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: anywhere;
            font-size: .78rem;
            line-height: 1.55;
            padding: .85rem 1rem;
            padding-right: 5.5rem;
            border-radius: .5rem;
            border: 1px solid var(--phoenix-border-color, #dee2e6);
            background: var(--phoenix-body-highlight-bg, #f5f7fa);
            color: var(--phoenix-body-color, #212529);
        }
        .sgt-codigo .sgt-copiar {
            position: absolute;
            top: .5rem;
            right: .75rem;
        }
    </style>
    <script>
        document.addEventListener('click', function (e) {
            var boton = e.target.closest('.sgt-copiar');
            if (!boton) return;
            var texto = boton.closest('.sgt-codigo').querySelector('pre').innerText;
            navigator.clipboard.writeText(texto).then(function () {
                var original = boton.innerHTML;
                boton.innerHTML = '<span class="fa-solid fa-check me-1"></span>Copiado';
                setTimeout(function () { boton.innerHTML = original; }, 1500);
            });
        });
    </script>
@endonce
