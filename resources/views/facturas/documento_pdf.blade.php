{{-- PDF (DomPDF): solo envuelve la plantilla compartida facturas/partes/documento. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $doc->nombreTipo() }} {{ $doc->numero_interno }}</title>
    <style>
        @page { margin: {{ $formato === 'ticket' ? '12px 10px' : '0' }}; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('facturas.partes.documento', ['modoPdf' => true])
</body>
</html>
