<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de Factura — {{ $sitio->nombre_comercial ?? 'Solutions GT' }}</title>
    <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .cert-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            max-width: 560px;
            width: 100%;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .cert-header {
            background: #0f172a;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .cert-body {
            padding: 28px;
        }
        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #15803d;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .cert-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .cert-label { color: #64748b; }
        .cert-value { font-weight: 600; color: #1e293b; text-align: right; }
    </style>
</head>
<body>

<div class="cert-card">
    <div class="cert-header">
        <h4 style="color:#ffffff; margin:0 0 6px 0; font-weight:800;">{{ $sitio->nombre_comercial ?? 'SOLUTIONS GT' }}</h4>
        <div style="font-size:13px; color:#94a3b8;">Verificación Oficial de Factura / Documento</div>
    </div>

    <div class="cert-body text-center">
        <div class="badge-verified">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            DOCUMENTO OFICIAL VERIFICADO
        </div>

        <div class="text-start mt-2">
            <div class="cert-row">
                <span class="cert-label">No. Factura:</span>
                <span class="cert-value text-primary font-monospace">{{ $doc->numero_interno }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Cliente / Receptor:</span>
                <span class="cert-value">{{ $doc->nombre_receptor }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">NIT / Documento:</span>
                <span class="cert-value">{{ $doc->nit_receptor ?: 'CF' }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Fecha de Emisión:</span>
                <span class="cert-value">{{ $doc->fecha_emision ? $doc->fecha_emision->format('d/m/Y') : date('d/m/Y') }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Régimen:</span>
                <span class="cert-value">Pequeño Contribuyente</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Total Documento:</span>
                <span class="cert-value" style="font-size:16px; color:#15803d;">
                    {{ $doc->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format($doc->total, 2) }} {{ $doc->moneda }}
                </span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Líneas Facturadas:</span>
                <span class="cert-value">{{ $doc->items->count() }} concepto(s)</span>
            </div>
        </div>

        <div style="font-size:11px; color:#94a3b8; margin-top:24px;">
            Este documento ha sido emitido y autenticado válidamente en la plataforma autorizada de Solutions GT.
        </div>
    </div>
</div>

</body>
</html>
