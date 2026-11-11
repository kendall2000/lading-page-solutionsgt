<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de Comprobante — {{ $sitio->nombre_comercial ?? 'Solutions GT' }}</title>
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
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            max-width: 540px;
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
        .cert-label {
            color: #64748b;
        }
        .cert-value {
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="cert-card">
    <div class="cert-header">
        <h4 style="color:#ffffff; margin:0 0 6px 0; font-weight:800;">{{ $sitio->nombre_comercial ?? 'SOLUTIONS GT' }}</h4>
        <div style="font-size:13px; color:#94a3b8;">Verificación Oficial de Pago y Comprobante</div>
    </div>

    <div class="cert-body text-center">
        <div class="badge-verified">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            DOCUMENTO AUTÉNTICO Y VÁLIDO
        </div>

        <div class="text-start mt-2">
            <div class="cert-row">
                <span class="cert-label">No. Comprobante:</span>
                <span class="cert-value text-primary font-monospace">{{ $pago->numero_comprobante }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Cliente / Receptor:</span>
                <span class="cert-value">{{ $pago->receptor_nombre ?: ($pago->cuenta?->nombre ?: ($pago->correo ?: 'Cliente Registrado')) }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Documento / ID:</span>
                <span class="cert-value">{{ $pago->receptor_documento ?: 'CF' }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Concepto:</span>
                <span class="cert-value">{{ $pago->descripcion }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Fecha de Pago:</span>
                <span class="cert-value">{{ $pago->pagado_en ? $pago->pagado_en->format('d/m/Y H:i') : date('d/m/Y') }}</span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Monto Pagado:</span>
                <span class="cert-value" style="font-size:16px; color:#15803d;">
                    {{ $pago->moneda === 'GTQ' ? 'Q' : '$' }}{{ number_format((float)$pago->monto, 2) }} {{ $pago->moneda }}
                </span>
            </div>
            <div class="cert-row">
                <span class="cert-label">Estado:</span>
                <span class="cert-value"><span class="badge bg-success text-uppercase">{{ $pago->estado }}</span></span>
            </div>
        </div>

        <div style="font-size:11px; color:#94a3b8; margin-top:24px;">
            Este registro ha sido verificado electrónicamente en la plataforma oficial de Solutions GT.
        </div>
    </div>
</div>

</body>
</html>
