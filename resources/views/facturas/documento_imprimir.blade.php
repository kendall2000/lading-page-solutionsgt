{{-- Vista en pantalla / impresión del navegador. El documento es la misma plantilla que el PDF. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $formato === 'ticket' ? 'Ticket' : $doc->nombreTipo() }} {{ $doc->numero_interno }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; background: #f3f4f6; font-family: "Helvetica Neue", Arial, sans-serif; }
        .barra { position: sticky; top: 0; z-index: 10; background: #ffffff; border-bottom: 1px solid #e5e7eb;
            padding: 10px 20px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
        .barra .info { font-size: 14px; font-weight: 600; color: #1f2937; }
        .barra .acciones { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
            border: 1px solid #d1d5db; background: #ffffff; color: #1f2937; }
        .btn:hover { background: #f9fafb; }
        .btn.activo { background: #eef2ff; border-color: #c7d2fe; color: #3730a3; }
        .btn.primario { background: #2563eb; border-color: #2563eb; color: #ffffff; }
        .hoja { background: #ffffff; margin: 24px auto; box-shadow: 0 4px 24px rgba(0, 0, 0, .08); border-radius: 8px; overflow: hidden; }
        .hoja.a4 { width: 816px; max-width: calc(100% - 24px); }
        .hoja.ticket { width: 302px; padding: 14px 12px; }
        @media print {
            body { background: #ffffff; }
            .barra { display: none; }
            .hoja { margin: 0 auto; box-shadow: none; border-radius: 0; }
            .hoja.a4 { width: 100%; max-width: none; }
            .hoja.ticket { width: 100%; max-width: 302px; padding: 0; }
            @page { margin: {{ $formato === 'ticket' ? '4mm' : '0' }}; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <div class="info">{{ $doc->nombreTipo() }} {{ $doc->numero_interno }} · {{ $formato === 'ticket' ? 'Ticket 80 mm' : 'Carta / A4' }}</div>
        <div class="acciones">
            <a href="?formato=factura" class="btn {{ $formato === 'factura' ? 'activo' : '' }}">Vista A4</a>
            <a href="?formato=ticket" class="btn {{ $formato === 'ticket' ? 'activo' : '' }}">Vista ticket</a>
            <a href="{{ route('admin.facturas.documento.descargar-pdf', $doc) }}?formato={{ $formato }}" class="btn">Descargar PDF</a>
            <button type="button" onclick="window.print()" class="btn primario">Imprimir</button>
        </div>
    </div>

    <div class="hoja {{ $formato === 'ticket' ? 'ticket' : 'a4' }}">
        @include('facturas.partes.documento', ['modoPdf' => false])
    </div>
</body>
</html>
