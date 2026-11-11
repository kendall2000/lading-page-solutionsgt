<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $formato === 'ticket' ? 'Ticket' : 'Factura' }} {{ $doc->numero_interno }} — {{ $sitio->nombre_comercial ?? 'Solutions GT' }}</title>
    <style>
        :root {
            --brand: #2563eb;
            --brand-dark: #1d4ed8;
            --brand-accent: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-page: #f8fafc;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background: var(--bg-page);
            padding-bottom: 40px;
        }

        .toolbar {
            position: sticky;
            top: 0;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            z-index: 1000;
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
        }

        .toolbar-btns {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-primary { background: var(--brand); color: #ffffff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-outline { background: #ffffff; color: var(--text-main); border-color: var(--border-color); }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-active { background: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }

        @media print {
            body { background: #ffffff; padding: 0; }
            .toolbar { display: none !important; }
            .ticket, .factura {
                box-shadow: none !important;
                margin: 0 auto !important;
                border-radius: 0 !important;
                padding: 0 !important;
            }
        }

        /* ── FORMATO A4 ── */
        .factura {
            background: #ffffff;
            width: 820px;
            margin: 30px auto;
            padding: 44px 50px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            font-size: 13px;
            line-height: 1.5;
            position: relative;
        }

        .factura .f-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .factura .f-logo-area { max-width: 55%; }
        .factura .f-logo-area img {
            max-height: 60px;
            max-width: 240px;
            object-fit: contain;
            margin-bottom: 8px;
        }
        .factura .f-company-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--brand-accent);
            text-transform: uppercase;
        }
        .factura .f-company-info {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 4px;
            line-height: 1.45;
        }

        .factura .f-doc-info { text-align: right; max-width: 40%; }
        .factura .f-doc-type {
            font-size: 24px;
            font-weight: 900;
            color: var(--brand);
            text-transform: uppercase;
        }
        .factura .f-doc-number {
            font-size: 15px;
            font-weight: 700;
            color: var(--brand-accent);
            margin-top: 4px;
        }
        .factura .f-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            background: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            border-radius: 4px;
            border: 1px solid #bbf7d0;
        }

        .factura .f-bar {
            height: 3px;
            background: linear-gradient(90deg, var(--brand) 0%, #60a5fa 100%);
            border-radius: 2px;
            margin-bottom: 24px;
        }

        .factura .f-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .factura .f-card {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 18px;
        }
        .factura .f-card-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .factura .f-card-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--brand-accent);
            margin-bottom: 3px;
        }
        .factura .f-card-detail {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .factura table.f-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .factura table.f-table thead th {
            background: var(--brand-accent);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 10px 14px;
            text-align: left;
        }
        .factura table.f-table thead th.text-right,
        .factura table.f-table tbody td.text-right {
            text-align: right;
        }
        .factura table.f-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color);
            font-size: 12.5px;
            vertical-align: top;
        }

        .factura .f-summary-area {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 24px;
        }
        .factura .f-payment-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 12px;
        }
        .factura .f-totals-box { width: 320px; }
        .factura .f-total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: var(--text-muted);
        }
        .factura .f-total-row.grand-total {
            border-top: 2px solid var(--brand-accent);
            margin-top: 8px;
            padding-top: 10px;
            font-size: 18px;
            font-weight: 900;
            color: var(--brand-accent);
        }

        .factura .f-qr-card {
            border: 1px dashed #cbd5e1;
            background: #ffffff;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .factura .f-qr-card img { width: 84px; height: 84px; }
        .factura .f-qr-text { font-size: 11px; color: var(--text-muted); line-height: 1.45; }

        .factura .f-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 16px;
        }

        /* ── FORMATO TICKET ── */
        .ticket {
            background: #ffffff;
            width: 310px;
            margin: 30px auto;
            padding: 20px 16px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            font-size: 11px;
            line-height: 1.4;
            color: #111827;
        }
        .ticket .t-center { text-align: center; }
        .ticket .t-logo img { max-height: 48px; max-width: 170px; margin-bottom: 6px; }
        .ticket .t-title { font-size: 14px; font-weight: 800; text-transform: uppercase; }
        .ticket .t-muted { font-size: 10px; color: #6b7280; }
        .ticket .t-divider { border: none; border-top: 1px dashed #9ca3af; margin: 10px 0; }
        .ticket .t-number { font-size: 14px; font-weight: 800; color: var(--brand); margin: 4px 0; }
        .ticket .t-items-table { width: 100%; border-collapse: collapse; font-size: 10.5px; margin: 8px 0; }
        .ticket .t-items-table th { border-bottom: 1px solid #d1d5db; padding: 4px 0; font-weight: 700; text-align: left; }
        .ticket .t-items-table td { padding: 5px 0; vertical-align: top; }
        .ticket .t-items-table .t-num { text-align: right; white-space: nowrap; }
        .ticket .t-totales { width: 100%; margin: 6px 0; font-size: 11px; }
        .ticket .t-totales td { padding: 2px 0; }
        .ticket .t-gran-total { font-size: 14px; font-weight: 900; border-top: 1px solid #111; padding-top: 4px !important; }
        .ticket .t-qr { margin: 12px auto 6px; text-align: center; }
        .ticket .t-qr img { width: 100px; height: 100px; }
    </style>
</head>
<body>

    <div class="toolbar">
        <div class="toolbar-info">
            <span>{{ $formato === 'ticket' ? 'Ticket de Pago (80mm)' : 'Factura Formal (A4)' }}</span>
            <span style="color:#94a3b8;">·</span>
            <span>No: {{ $doc->numero_interno }}</span>
        </div>
        <div class="toolbar-btns">
            <a href="?formato=factura" class="btn {{ $formato === 'factura' ? 'btn-active' : 'btn-outline' }}">📄 Vista A4</a>
            <a href="?formato=ticket" class="btn {{ $formato === 'ticket' ? 'btn-active' : 'btn-outline' }}">🧾 Vista Ticket</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir</button>
            <a href="{{ route('admin.facturas.documento.descargar-pdf', $doc) }}?formato={{ $formato }}" class="btn btn-outline">⬇️ Descargar PDF</a>
        </div>
    </div>

    @php
        $simbolo = $doc->moneda === 'GTQ' ? 'Q' : '$';
        $fmt = fn($val) => $simbolo . number_format((float)$val, 2, '.', ',');
        $empresaNombre = $sitio->nombre_comercial ?? 'SOLUTIONS GT';
        $logo = $sitio->logo ? asset('storage/' . $sitio->logo) : null;
    @endphp

    {{-- ══════════════ FORMATO A4 ══════════════ --}}
    @if($formato === 'factura')
    <div class="factura">
        <div class="f-header">
            <div class="f-logo-area">
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $empresaNombre }}">
                @endif
                <div class="f-company-name">{{ $empresaNombre }}</div>
                <div class="f-company-info">
                    @if(!empty($sitio->slogan)) <div>{{ $sitio->slogan }}</div> @endif
                    @if(!empty($sitio->telefono)) <div>Tel: {{ $sitio->telefono }}</div> @endif
                    @if(!empty($sitio->correo)) <div>Email: {{ $sitio->correo }}</div> @endif
                    <div>Guatemala &middot; Software &amp; Servicios Informáticos</div>
                </div>
            </div>

            <div class="f-doc-info">
                <div class="f-doc-type">FACTURA</div>
                <div class="f-doc-number">No. {{ $doc->numero_interno }}</div>
                <div><span class="f-badge">✓ EMITIDA</span></div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 6px;">
                    Fecha: <strong>{{ $doc->fecha_emision ? $doc->fecha_emision->format('d/m/Y') : date('d/m/Y') }}</strong>
                </div>
            </div>
        </div>

        <div class="f-bar"></div>

        <div class="f-parties">
            <div class="f-card">
                <div class="f-card-label">Facturado a (Cliente)</div>
                <div class="f-card-name">{{ $doc->nombre_receptor }}</div>
                <div class="f-card-detail">
                    <div><strong>NIT / Tax ID:</strong> {{ $doc->nit_receptor ?: 'CF' }}</div>
                    @if($doc->correo_receptor)
                        <div><strong>Correo:</strong> {{ $doc->correo_receptor }}</div>
                    @endif
                    @if($doc->direccion_receptor)
                        <div><strong>Dirección:</strong> {{ $doc->direccion_receptor }}</div>
                    @endif
                </div>
            </div>

            <div class="f-card">
                <div class="f-card-label">Condiciones de Venta</div>
                <div class="f-card-detail">
                    <div><strong>Condición:</strong> {{ ucfirst($doc->condicion_pago) }}</div>
                    <div><strong>Método de Pago:</strong> {{ ucfirst($doc->metodo_pago) }}</div>
                    <div><strong>Moneda:</strong> {{ $doc->moneda }}</div>
                    <div><strong>Régimen:</strong> Pequeño Contribuyente</div>
                </div>
            </div>
        </div>

        <table class="f-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Tipo</th>
                    <th style="width: 50%;">Descripción del Bien o Servicio</th>
                    <th class="text-right" style="width: 12%;">Cant.</th>
                    <th class="text-right" style="width: 15%;">Precio Unit.</th>
                    <th class="text-right" style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($doc->items as $item)
                <tr>
                    <td>
                        <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700; padding:2px 6px; border-radius:4px; font-size:10px;">
                            {{ $item->tipo === 'B' ? 'BIEN' : 'SERVICIO' }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--brand-accent);">{!! nl2br(e($item->descripcion)) !!}</div>
                    </td>
                    <td class="text-right">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="text-right">{{ $fmt($item->precio_unitario) }}</td>
                    <td class="text-right" style="font-weight: 700;">{{ $fmt($item->subtotal) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #94a3b8;">Sin ítems registrados</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="f-summary-area">
            <div class="f-payment-box">
                <div class="f-card-label">Observaciones y Régimen Fiscal</div>
                <p style="color: var(--text-muted); font-size: 11px; line-height: 1.45;">
                    Régimen Pequeño Contribuyente. No genera derecho a crédito fiscal.
                </p>
                @if($doc->notas)
                    <div style="margin-top: 6px; font-weight: 600;">Notas: {{ $doc->notas }}</div>
                @endif
            </div>

            <div class="f-totals-box">
                <div class="f-total-row">
                    <span>Subtotal:</span>
                    <span>{{ $fmt($doc->subtotal) }}</span>
                </div>
                <div class="f-total-row">
                    <span>IVA / Impuestos:</span>
                    <span>{{ $fmt(0) }}</span>
                </div>
                <div class="f-total-row grand-total">
                    <span>TOTAL:</span>
                    <span>{{ $fmt($doc->total) }} {{ $doc->moneda }}</span>
                </div>
            </div>
        </div>

        <div class="f-qr-card">
            <div>
                <img src="{{ $qrUri }}" alt="QR Validación">
            </div>
            <div class="f-qr-text">
                <strong style="color: var(--brand-accent);">Comprobante Oficial Verificado</strong><br>
                Escanee este código QR para comprobar la autenticidad, registro y validez de esta factura emitida directamente en nuestros sistemas.
                <div style="font-family: monospace; font-size: 10px; margin-top: 4px; color: #94a3b8;">
                    HASH: {{ substr($doc->token_publico, 0, 16) }}...
                </div>
            </div>
        </div>

        <div class="f-footer">
            {{ $empresaNombre }} &middot; Gracias por su confianza.
        </div>
    </div>

    {{-- ══════════════ FORMATO TICKET ══════════════ --}}
    @else
    <div class="ticket">
        <div class="t-center t-logo">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $empresaNombre }}">
            @endif
        </div>
        <div class="t-center t-title">{{ $empresaNombre }}</div>
        @if(!empty($sitio->telefono)) <div class="t-center t-muted">Tel: {{ $sitio->telefono }}</div> @endif
        @if(!empty($sitio->correo)) <div class="t-center t-muted">{{ $sitio->correo }}</div> @endif

        <hr class="t-divider">

        <div class="t-center t-number">FACTURA {{ $doc->numero_interno }}</div>
        <div class="t-center t-muted">Fecha: {{ $doc->fecha_emision ? $doc->fecha_emision->format('d/m/Y') : date('d/m/Y') }}</div>
        <div class="t-center" style="font-size:10px; margin-top:2px;">
            Cliente: <strong>{{ $doc->nombre_receptor }}</strong>
        </div>
        <div class="t-center t-muted">NIT/ID: {{ $doc->nit_receptor ?: 'CF' }}</div>

        <hr class="t-divider">

        <table class="t-items-table">
            <thead>
                <tr>
                    <th>Desc. [B/S]</th>
                    <th class="t-num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doc->items as $item)
                <tr>
                    <td>
                        <strong>[{{ $item->tipo }}]</strong> {{ $item->descripcion }}<br>
                        <span style="color:#6b7280;">{{ number_format($item->cantidad, 2) }} x {{ $fmt($item->precio_unitario) }}</span>
                    </td>
                    <td class="t-num">{{ $fmt($item->subtotal) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="t-divider">

        <table class="t-totales">
            <tr>
                <td>Subtotal</td>
                <td style="text-align:right;">{{ $fmt($doc->subtotal) }}</td>
            </tr>
            <tr class="t-gran-total">
                <td>TOTAL ({{ $doc->moneda }})</td>
                <td style="text-align:right;">{{ $fmt($doc->total) }}</td>
            </tr>
        </table>

        <hr class="t-divider">

        <div style="font-size:10px; margin-bottom:4px;">
            Condición: {{ ucfirst($doc->condicion_pago) }}<br>
            Método: {{ ucfirst($doc->metodo_pago) }}
        </div>

        <div class="t-qr">
            <img src="{{ $qrUri }}" alt="QR Validación">
            <div style="font-size:9px; color:#6b7280; margin-top:2px;">Escanear para verificar comprobante</div>
        </div>

        <div class="t-center t-muted" style="margin-top:8px;">
            Pequeño Contribuyente &middot; ¡Gracias!
        </div>
    </div>
    @endif

</body>
</html>
