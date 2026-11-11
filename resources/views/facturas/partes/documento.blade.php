{{--
    Factura / nota en formato A4 o ticket, con el estilo de los documentos de sistema-inventario.
    La usan la vista en pantalla (documento_imprimir) y el PDF (documento_pdf), por eso todo va con
    tablas y colores fijos: DomPDF no entiende flexbox, grid, variables CSS, gap ni degradados.

    Recibe: $doc, $formato (factura|ticket), $qrUri, $logoUri, $sitio, $cfgDte, $modoPdf (bool).
--}}
@php
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($sitio->color_primario ?? '')) ? $sitio->color_primario : '#2563eb';
    $simbolo = $doc->moneda === 'GTQ' ? 'Q' : '$';
    $fmt = fn ($n) => $simbolo . number_format((float) $n, 2, '.', ',');
    $fecha = fn ($f) => $f ? $f->copy()->setTimezone('America/Guatemala')->format('d/m/Y H:i') : '—';

    $empresa = $cfgDte->nombre_comercial ?: ($sitio->nombre ?? 'Solutions GT');
    $razonSocial = $cfgDte->nombre_emisor && strcasecmp($cfgDte->nombre_emisor, $empresa) !== 0 ? $cfgDte->nombre_emisor : null;
    $nitEmisor = $cfgDte->nit_emisor && $cfgDte->nit_emisor !== 'CF' ? $cfgDte->nit_emisor : null;
    $direccion = trim(implode(', ', array_filter([$cfgDte->direccion, $cfgDte->municipio, $cfgDte->departamento])));

    $afiliacion = strtoupper((string) ($cfgDte->afiliacion_iva ?: 'PEQ'));
    $regimen = match ($afiliacion) { 'GEN' => 'Régimen General (IVA 12%)', 'EXE' => 'Exento de IVA', default => 'Pequeño Contribuyente' };
    $leyenda = match ($afiliacion) {
        'GEN' => 'Sujeto a pagos trimestrales ISR. Precios con IVA incluido.',
        'EXE' => 'Exento de IVA.',
        default => 'Pequeño Contribuyente. No genera derecho a crédito fiscal.',
    };
    $iva = ($afiliacion === 'GEN' && $doc->tipo !== 'NABN') ? round((float) $doc->total - round((float) $doc->total / 1.12, 2), 2) : 0.0;

    $tieneSat = (bool) $doc->uuid_sat;
    $titulo = $doc->nombreTipo() . ($tieneSat ? ' electrónica' : '');
    $metodos = ['transferencia' => 'Transferencia', 'efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'paypal' => 'PayPal', 'cheque' => 'Cheque'];
    $metodo = $metodos[$doc->metodo_pago] ?? ucfirst((string) $doc->metodo_pago);
@endphp

<style>
    .dc, .dc * { font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif; }
    .dc { color: #1f2937; font-size: 12px; line-height: 1.45; }
    .dc table { border-collapse: collapse; }
    .dc .mono { font-family: "DejaVu Sans Mono", monospace; }
    .dc .muted { color: #6b7280; }
    .dc .num { text-align: right; }

    /* ── A4 / carta ── */
    .dc .page { padding: 36px 44px 90px; }
    .dc .header { width: 100%; border-bottom: 3px solid {{ $color }}; }
    .dc .header td { vertical-align: middle; padding-bottom: 14px; }
    .dc .header h1 { margin: 0; font-size: 20px; color: {{ $color }}; }
    .dc .header p { margin: 2px 0 0; font-size: 10.5px; color: #6b7280; }
    .dc .header .logo { text-align: right; width: 160px; }
    .dc .header .logo img { max-height: 52px; max-width: 160px; }

    .dc .title { text-align: center; margin: 22px 0 4px; }
    .dc .title h2 { margin: 0; font-size: 21px; letter-spacing: 1px; text-transform: uppercase; color: #111827; }
    .dc .title .sub { color: #6b7280; font-size: 12px; margin-top: 4px; font-weight: bold; }
    .dc .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 8px; }
    .dc .badge-anulada { background: #fee2e2; color: #b91c1c; }
    .dc .badge-pendiente { background: #fef3c7; color: #92400e; }

    .dc .meta { width: 100%; margin-top: 20px; }
    .dc .meta td { width: 50%; padding: 10px 12px; border: 1px solid #e5e7eb; vertical-align: top; font-size: 11.5px; }
    .dc .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px; font-weight: bold; }
    .dc .meta strong.nombre { font-size: 13px; color: #111827; }

    .dc table.items { width: 100%; margin-top: 22px; font-size: 11px; }
    .dc table.items th { background: {{ $color }}; color: #ffffff; padding: 8px; text-align: left; font-size: 9.5px; text-transform: uppercase; }
    .dc table.items th.num { text-align: right; }
    .dc table.items td { padding: 7px 8px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
    .dc table.items tr.par td { background: #f9fafb; }
    .dc .tag { font-size: 8px; font-weight: bold; color: #6b7280; }

    .dc .totales { width: 100%; margin-top: 14px; }
    .dc .totales td { vertical-align: top; }
    .dc .totales table { width: 100%; font-size: 12px; }
    .dc .totales table td { padding: 5px 8px; }
    .dc .totales .lbl { color: #6b7280; text-align: right; }
    .dc .totales .val { text-align: right; font-weight: bold; }
    .dc .totales .grand td { border-top: 2px solid {{ $color }}; font-size: 15px; color: {{ $color }}; padding-top: 8px; }

    .dc .section { margin-top: 20px; }
    .dc .section h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $color }}; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin: 0 0 8px; }
    .dc .section p { margin: 0; font-size: 11px; line-height: 1.6; color: #374151; }

    .dc .dte { width: 100%; margin-top: 18px; border: 1px solid #bbf7d0; background: #f0fdf4; }
    .dc .dte.anulado { border-color: #fecaca; background: #fef2f2; }
    .dc .dte.interno { border-color: #e5e7eb; background: #f9fafb; }
    .dc .dte td { padding: 10px 12px; vertical-align: middle; font-size: 10.5px; line-height: 1.6; }
    .dc .dte .uuid { font-family: "DejaVu Sans Mono", monospace; font-size: 10px; color: #166534; }
    .dc .dte .qr { width: 112px; text-align: right; }
    .dc .dte .qr img { width: 100px; height: 100px; }
    .dc .dte .ver { font-size: 7.5px; color: #6b7280; }

    .dc .footer { border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 8.5px; color: #9ca3af; width: 100%; }
    .dc .footer td { padding: 0; }
    .dc .footer.fijo { position: fixed; bottom: 26px; left: 44px; width: 728px; }
    .dc .footer.normal { margin: 0 44px 30px; width: calc(100% - 88px); }

    /* ── Ticket 80 mm ── */
    .dc .ticket { font-size: 9.5px; color: #111827; }
    .dc .ticket .c { text-align: center; }
    .dc .ticket .logo img { max-height: 48px; max-width: 170px; margin-bottom: 4px; }
    .dc .ticket .empresa { font-weight: bold; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    .dc .ticket .sub { font-size: 8.5px; color: #555555; }
    .dc .ticket .div { border-top: 1px dashed #aaaaaa; margin: 7px 0; height: 0; }
    .dc .ticket .numero { font-size: 12.5px; font-weight: bold; color: {{ $color }}; letter-spacing: 0.5px; }
    .dc .ticket table { width: 100%; }
    .dc .ticket .titems th { font-size: 7.5px; text-transform: uppercase; color: #777777; border-bottom: 1px solid #dddddd; padding: 3px 1px; text-align: left; }
    .dc .ticket .titems th.num { text-align: right; }
    .dc .ticket .titems td { padding: 3px 1px; vertical-align: top; font-size: 8.5px; }
    .dc .ticket .tot td { padding: 2px 1px; font-size: 9px; }
    .dc .ticket .tot .lbl { color: #555555; }
    .dc .ticket .tot .val { text-align: right; font-weight: bold; }
    .dc .ticket .gran td { font-size: 13px; font-weight: bold; color: {{ $color }}; }
    .dc .ticket .fel { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 5px 7px; font-size: 8px; margin-top: 4px; }
    .dc .ticket .fel.anulado { background: #fef2f2; border-color: #fecaca; }
    .dc .ticket .fel .t { font-weight: bold; color: #166534; }
    .dc .ticket .fel.anulado .t { color: #b91c1c; }
    .dc .ticket .fel .uuid { font-family: "DejaVu Sans Mono", monospace; font-size: 7px; color: #166534; }
    .dc .ticket .qr img { width: 92px; height: 92px; }
    .dc .ticket .pie { text-align: center; font-size: 8px; color: #888888; margin-top: 8px; line-height: 1.4; }
</style>

<div class="dc">
@if ($formato !== 'ticket')
    {{-- ═══════════ A4 / CARTA ═══════════ --}}
    <div class="page">
        <table class="header">
            <tr>
                <td>
                    <h1>{{ $empresa }}</h1>
                    @if ($razonSocial || $nitEmisor)
                        <p>{{ $razonSocial }}@if ($razonSocial && $nitEmisor) &nbsp;·&nbsp; @endif @if ($nitEmisor) NIT: {{ $nitEmisor }} @endif</p>
                    @endif
                    @if ($direccion) <p>{{ $direccion }}</p> @endif
                    <p>
                        @if (! empty($sitio->telefono)) Tel: {{ $sitio->telefono }} @endif
                        @if (! empty($sitio->telefono) && ! empty($sitio->correo)) &nbsp;·&nbsp; @endif
                        @if (! empty($sitio->correo)) {{ $sitio->correo }} @endif
                    </p>
                </td>
                @if ($logoUri)
                    <td class="logo"><img src="{{ $logoUri }}" alt="logo"></td>
                @endif
            </tr>
        </table>

        <div class="title">
            <h2>{{ $titulo }}</h2>
            <div class="sub">
                @if ($tieneSat)
                    Serie {{ $doc->serie_sat }} · No. {{ $doc->numero_sat }}
                @else
                    {{ $doc->numero_interno }}
                @endif
            </div>
            @if ($doc->estaAnulado())
                <span class="badge badge-anulada">Anulada ante la SAT</span>
            @elseif (! $tieneSat)
                <span class="badge badge-pendiente">Sin certificar SAT</span>
            @endif
        </div>

        <table class="meta">
            <tr>
                <td>
                    <div class="label">Cliente</div>
                    <strong class="nombre">{{ $doc->nombre_receptor }}</strong><br>
                    NIT: {{ $doc->nit_receptor ?: 'CF' }}<br>
                    @if ($doc->correo_receptor) {{ $doc->correo_receptor }}<br> @endif
                    @if ($doc->direccion_receptor) {{ $doc->direccion_receptor }} @endif
                </td>
                <td>
                    <div class="label">Detalles</div>
                    Documento: <strong>{{ $doc->numero_interno }}</strong><br>
                    Fecha: <strong>{{ $fecha($doc->fecha_emision) }}</strong><br>
                    Pago: {{ ucfirst((string) $doc->condicion_pago) }} · {{ $metodo }} · {{ $doc->moneda }}<br>
                    Régimen: {{ $regimen }}
                    @if ($doc->origen)
                        <br>Factura de origen: <strong>{{ $doc->origen->serie_sat }} {{ $doc->origen->numero_sat }}</strong>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 56%;">Descripción</th>
                    <th class="num" style="width: 12%;">Cant.</th>
                    <th class="num" style="width: 16%;">P. Unitario</th>
                    <th class="num" style="width: 16%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($doc->items as $item)
                    <tr class="{{ $loop->even ? 'par' : '' }}">
                        <td>{!! nl2br(e($item->descripcion)) !!} <span class="tag">· {{ $item->tipo === 'B' ? 'BIEN' : 'SERVICIO' }}</span></td>
                        <td class="num">{{ number_format((float) $item->cantidad, 2) }}</td>
                        <td class="num">{{ $fmt($item->precio_unitario) }}</td>
                        <td class="num">{{ $fmt($item->subtotal) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="text-align: center; padding: 14px;">Sin ítems registrados</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totales">
            <tr>
                <td style="width: 58%; padding-top: 6px;">
                    <div class="label">Forma de pago</div>
                    <div style="font-size: 11px;">{{ $metodo }} · {{ ucfirst((string) $doc->condicion_pago) }}</div>
                    <div class="label" style="margin-top: 10px;">Régimen fiscal</div>
                    <div style="font-size: 10.5px;" class="muted">{{ $leyenda }}</div>
                </td>
                <td style="width: 42%;">
                    <table>
                        <tr><td class="lbl">Subtotal</td><td class="val">{{ $fmt($doc->subtotal) }}</td></tr>
                        @if ($iva > 0)
                            <tr><td class="lbl">Base imponible</td><td class="val">{{ $fmt((float) $doc->total - $iva) }}</td></tr>
                            <tr><td class="lbl">IVA 12%</td><td class="val">{{ $fmt($iva) }}</td></tr>
                        @endif
                        <tr class="grand"><td class="lbl" style="color: {{ $color }}; font-weight: bold;">GRAN TOTAL</td><td class="val">{{ $fmt($doc->total) }} {{ $doc->moneda }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        @if ($tieneSat)
            <table class="dte {{ $doc->estaAnulado() ? 'anulado' : '' }}">
                <tr>
                    <td>
                        <strong>{{ $doc->estaAnulado() ? 'Documento Tributario Electrónico (FEL) ANULADO ante la SAT' : 'Documento Tributario Electrónico (FEL) certificado por la SAT' }}</strong><br>
                        Serie: {{ $doc->serie_sat }} · Número: {{ $doc->numero_sat }}<br>
                        Fecha de certificación: {{ $fecha($doc->fecha_certificacion ?? $doc->fecha_emision) }}<br>
                        Autorización:<br>
                        <span class="uuid">{{ $doc->uuid_sat }}</span><br>
                        Certificador: INFILE, S.A. · NIT 12521337
                        @if ($doc->estaAnulado() && $doc->fecha_anulacion)
                            <br>Anulado el {{ $fecha($doc->fecha_anulacion) }}
                        @endif
                        <br><span class="ver">Escanee el código para verificarlo en el portal de la SAT.</span>
                    </td>
                    @if ($qrUri)
                        <td class="qr"><img src="{{ $qrUri }}" alt="QR de verificación SAT"></td>
                    @endif
                </tr>
            </table>
        @else
            <table class="dte interno">
                <tr>
                    <td>
                        <strong>Comprobante interno</strong><br>
                        Este documento aún no ha sido certificado ante la SAT, por lo que no es una factura electrónica.<br>
                        <span class="ver">Escanee el código para verificar el comprobante · HASH {{ substr((string) $doc->token_publico, 0, 16) }}</span>
                    </td>
                    @if ($qrUri)
                        <td class="qr"><img src="{{ $qrUri }}" alt="QR de verificación"></td>
                    @endif
                </tr>
            </table>
        @endif

        @if ($doc->notas)
            <div class="section">
                <h3>{{ $doc->esNota() ? 'Motivo del ajuste' : 'Notas' }}</h3>
                <p>{{ $doc->notas }}</p>
            </div>
        @endif

        @if ($doc->estaAnulado() && $doc->motivo_anulacion)
            <div class="section">
                <h3>Motivo de la anulación</h3>
                <p>{{ $doc->motivo_anulacion }}</p>
            </div>
        @endif
    </div>

    <table class="footer {{ $modoPdf ? 'fijo' : 'normal' }}">
        <tr>
            <td>{{ $empresa }} — {{ $doc->nombreTipo() }} {{ $doc->numero_interno }}</td>
            <td class="num">Generado el {{ now()->setTimezone('America/Guatemala')->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
@else
    {{-- ═══════════ TICKET 80 mm ═══════════ --}}
    <div class="ticket">
        @if ($logoUri)
            <div class="c logo"><img src="{{ $logoUri }}" alt="logo"></div>
        @endif
        <div class="c empresa">{{ $empresa }}</div>
        @if ($razonSocial) <div class="c sub">{{ $razonSocial }}</div> @endif
        @if ($nitEmisor) <div class="c sub">NIT: {{ $nitEmisor }}</div> @endif
        @if (! empty($sitio->telefono)) <div class="c sub">Tel: {{ $sitio->telefono }}</div> @endif
        @if ($direccion) <div class="c sub">{{ $direccion }}</div> @endif

        <div class="div"></div>

        <div class="c" style="font-size: 8.5px; text-transform: uppercase; color: #555555;">{{ $titulo }}</div>
        <div class="c numero">{{ $tieneSat ? $doc->serie_sat . ' · ' . $doc->numero_sat : $doc->numero_interno }}</div>
        <div class="c sub">{{ $fecha($doc->fecha_emision) }} · {{ $doc->numero_interno }}</div>
        @if ($doc->estaAnulado()) <div class="c" style="font-weight: bold; color: #b91c1c;">*** ANULADA ***</div> @endif
        <div class="c sub" style="margin-top: 3px;">Cliente: <strong style="color: #111827;">{{ $doc->nombre_receptor }}</strong></div>
        <div class="c sub">NIT: {{ $doc->nit_receptor ?: 'CF' }}</div>
        @if ($doc->origen) <div class="c sub">Factura de origen: {{ $doc->origen->serie_sat }} {{ $doc->origen->numero_sat }}</div> @endif

        <div class="div"></div>

        <table class="titems">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="num">Cant.</th>
                    <th class="num">P.Unit</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($doc->items as $item)
                    <tr>
                        <td>{{ $item->descripcion }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $item->cantidad, 2, '.', ''), '0'), '.') }}</td>
                        <td class="num" style="white-space: nowrap;">{{ $fmt($item->precio_unitario) }}</td>
                        <td class="num" style="white-space: nowrap;">{{ $fmt($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="div"></div>

        <table class="tot">
            <tr><td class="lbl">Subtotal</td><td class="val">{{ $fmt($doc->subtotal) }}</td></tr>
            @if ($iva > 0)
                <tr><td class="lbl">IVA 12% incluido</td><td class="val">{{ $fmt($iva) }}</td></tr>
            @endif
            <tr class="gran"><td>TOTAL</td><td class="val">{{ $fmt($doc->total) }}</td></tr>
        </table>

        <div class="div"></div>

        <table class="tot">
            <tr><td class="lbl">Pago</td><td class="val">{{ $metodo }} · {{ ucfirst((string) $doc->condicion_pago) }}</td></tr>
        </table>

        @if ($tieneSat)
            <div class="fel {{ $doc->estaAnulado() ? 'anulado' : '' }}">
                <div class="t">{{ $doc->estaAnulado() ? '✕ FEL anulado ante la SAT' : '✓ ' . $doc->nombreTipo() . ' electrónica (FEL)' }}</div>
                <div>Serie: {{ $doc->serie_sat }} · No. {{ $doc->numero_sat }}</div>
                <div class="uuid">UUID: {{ $doc->uuid_sat }}</div>
                <div>Certificador: INFILE, S.A. · NIT 12521337</div>
            </div>
        @endif

        @if ($doc->notas)
            <div class="div"></div>
            <div class="sub">{{ $doc->esNota() ? 'Motivo' : 'Notas' }}: {{ $doc->notas }}</div>
        @endif

        @if ($qrUri)
            <div class="c qr" style="margin-top: 8px;"><img src="{{ $qrUri }}" alt="QR"></div>
            <div class="c sub">{{ $tieneSat ? 'Verifique este documento en la SAT' : 'Escanee para verificar el comprobante' }}</div>
        @endif

        <div class="pie">
            {{ $regimen }}<br>
            ¡Gracias por su compra!<br>
            {{ $empresa }}@if (! empty($sitio->correo))<br>{{ $sitio->correo }}@endif
        </div>
    </div>
@endif
</div>
