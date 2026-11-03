<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cotización {{ $cotizacion->numero }} · {{ $cotizacion->cliente_empresa }}</title>
<style>
  @page { size: Letter; margin: 18mm 16mm; }
  * { box-sizing: border-box; }
  body { font-family: "DejaVu Sans", "Segoe UI", Arial, Helvetica, sans-serif; color: #222; font-size: 10pt; line-height: 1.4; margin: 0; background: #fff; }
  h1 { font-size: 19pt; margin: 0; color: #1c3d5a; }
  h2 { font-size: 12.5pt; color: #1c3d5a; border-bottom: 2px solid #cbd5e1; padding-bottom: 4px; margin: 16px 0 8px; }
  h3 { font-size: 11pt; margin: 0 0 4px; }
  p { margin: 0 0 8px; }
  ul { margin: 4px 0 8px; padding-left: 18px; }
  li { margin-bottom: 3px; }
  table { width: 100%; border-collapse: collapse; margin: 6px 0 10px; }
  th, td { border: 1px solid #ddd; padding: 5px 8px; vertical-align: top; text-align: left; }
  th { background: #f1f5f9; color: #1c3d5a; }
  .head { width: 100%; border: none; margin-bottom: 12px; }
  .head td { border: none; padding: 0; }
  .muted { color: #64748b; }
  .right { text-align: right; }
  .center { text-align: center; }
  .price { font-size: 14pt; font-weight: bold; color: #1c3d5a; white-space: nowrap; }
  .yes { color: #15803d; font-weight: bold; }
  .no { color: #94a3b8; }
  .note { background: #f8fafc; border-left: 4px solid #3b82f6; padding: 8px 12px; margin: 10px 0; font-size: 9.5pt; }
  .sign { width: 100%; border: none; margin-top: 16px; page-break-inside: avoid; }
  .sign td { border: none; width: 50%; padding: 0 25px; text-align: center; }
  .line { border-top: 1px solid #334155; padding-top: 5px; margin-top: 40px; }
  .avoid { page-break-inside: avoid; }
  tr { page-break-inside: avoid; }
</style>
</head>
<body>

<table class="head">
  <tr>
    <td>
      <h1>{{ $cotizacion->titulo }}</h1>
      @if($cotizacion->subtitulo)
        <p class="muted" style="margin-top: 3px; font-size: 10.5pt;">{{ $cotizacion->subtitulo }}</p>
      @endif
    </td>
    <td class="right" style="white-space:nowrap;padding-left:16px;">
      <p>
        <strong>No.:</strong> {{ $cotizacion->numero }}<br>
        <strong>Fecha:</strong> {{ $cotizacion->fecha ? $cotizacion->fecha->translatedFormat('j \d\e F \d\e Y') : date('d/m/Y') }}<br>
        <strong>Vigencia:</strong> {{ $cotizacion->vigencia_dias }} días
      </p>
    </td>
  </tr>
</table>

<table>
  <tr>
    <th style="width:50%">Cliente</th>
    <th>Proveedor</th>
  </tr>
  <tr>
    <td>
      <strong>{{ $cotizacion->cliente_empresa }}</strong><br>
      @if($cotizacion->cliente_atencion)
        <span class="muted">Atención: {{ $cotizacion->cliente_atencion }}</span><br>
      @endif
      @if($cotizacion->cliente_correo)
        <span class="muted">{{ $cotizacion->cliente_correo }}</span>
      @endif
      @if($cotizacion->cliente_telefono)
        <span class="muted"> &middot; Tel: {{ $cotizacion->cliente_telefono }}</span>
      @endif
    </td>
    <td>
      <strong>{{ $cotizacion->proveedor_nombre }}</strong><br>
      <span class="muted">{{ $cotizacion->proveedor_cargo }}</span><br>
      @if($cotizacion->proveedor_correo)
        <span class="muted">{{ $cotizacion->proveedor_correo }}</span>
      @endif
      @if($cotizacion->proveedor_telefono)
        <span class="muted"> &middot; Tel: {{ $cotizacion->proveedor_telefono }}</span>
      @endif
    </td>
  </tr>
</table>

@if($cotizacion->objeto_descripcion || (!empty($cotizacion->objeto_puntos)))
  <h2>1. Objeto de la cotización</h2>
  @if($cotizacion->objeto_descripcion)
    <p>{{ $cotizacion->objeto_descripcion }}</p>
  @endif

  @if(!empty($cotizacion->objeto_puntos) && is_array($cotizacion->objeto_puntos))
    <ul>
      @foreach($cotizacion->objeto_puntos as $punto)
        <li>{{ $punto }}</li>
      @endforeach
    </ul>
  @endif

  @if($cotizacion->objeto_nota)
    <p class="muted" style="font-size: 9.5pt; margin-top: 6px;">{{ $cotizacion->objeto_nota }}</p>
  @endif
@endif

@if($cotizacion->tipo === 'planes' && !empty($cotizacion->planes['columnas']))
  <h2>2. Planes de servicio</h2>
  <table>
    <thead>
      <tr>
        <th style="width:34%">Servicio incluido</th>
        @foreach($cotizacion->planes['columnas'] as $col)
          <th class="center">
            {{ $col['nombre'] }}<br>
            <span class="price">
              @if($cotizacion->moneda === 'GTQ') Q @elseif($cotizacion->moneda === 'USD') $ @else {{ $cotizacion->moneda }} @endif{{ $col['precio'] }}
            </span><br>
            <span class="muted" style="font-size: 8.5pt;">{{ $col['frecuencia'] ?? '/ mes' }}</span>
          </th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @if(!empty($cotizacion->planes['filas']))
        @foreach($cotizacion->planes['filas'] as $fila)
          <tr>
            <td>{{ $fila['caracteristica'] }}</td>
            @foreach($fila['valores'] as $val)
              @php
                $esCheck = in_array(trim($val), ['✔', 'si', 'sí', 'SI', 'SÍ', '1', 'x', 'X']);
                $esNo = in_array(trim($val), ['—', '-', 'no', 'NO', '0']);
              @endphp
              <td class="center {{ $esCheck ? 'yes' : ($esNo ? 'no' : '') }}">{{ $val }}</td>
            @endforeach
          </tr>
        @endforeach
      @endif
    </tbody>
  </table>

  @if($cotizacion->planes_nota)
    <div class="note">
      {{ $cotizacion->planes_nota }}
    </div>
  @endif
@endif

@if($cotizacion->tipo === 'items' && $cotizacion->items->count() > 0)
  <h2>2. Detalle de servicios / productos</h2>
  <table>
    <thead>
      <tr>
        <th>Descripción</th>
        <th class="center" style="width:12%">Cant.</th>
        <th class="right" style="width:18%">Precio unitario</th>
        <th class="right" style="width:18%">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($cotizacion->items as $item)
        <tr>
          <td>
            <strong>{{ $item->descripcion }}</strong>
            @if($item->detalle)
              <div class="muted" style="font-size:9pt;">{{ $item->detalle }}</div>
            @endif
          </td>
          <td class="center">{{ number_format($item->cantidad, 2) }}</td>
          <td class="right">{{ $cotizacion->moneda === 'GTQ' ? 'Q' : '$' }} {{ number_format($item->precio_unitario, 2) }}</td>
          <td class="right">{{ $cotizacion->moneda === 'GTQ' ? 'Q' : '$' }} {{ number_format($item->subtotal, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3" class="right">Total:</th>
        <th class="right price" style="font-size:12pt;">
          {{ $cotizacion->moneda === 'GTQ' ? 'Q' : '$' }} {{ number_format($cotizacion->total ?? $cotizacion->items->sum('subtotal'), 2) }}
        </th>
      </tr>
    </tfoot>
  </table>
@endif

@if(!empty($cotizacion->mantenimiento_incluye) || !empty($cotizacion->desarrollo_nuevo_incluye))
  <div class="avoid">
    <h2>3. Qué es mantenimiento y qué es desarrollo nuevo</h2>
    <table>
      <tr>
        <th style="width:50%">Mantenimiento (incluido en el plan)</th>
        <th>Desarrollo nuevo (se cotiza aparte)</th>
      </tr>
      <tr>
        <td>
          @if(!empty($cotizacion->mantenimiento_incluye))
            <ul>
              @foreach($cotizacion->mantenimiento_incluye as $inc)
                <li>{{ $inc }}</li>
              @endforeach
            </ul>
          @endif
        </td>
        <td>
          @if(!empty($cotizacion->desarrollo_nuevo_incluye))
            <ul>
              @foreach($cotizacion->desarrollo_nuevo_incluye as $noInc)
                <li>{{ $noInc }}</li>
              @endforeach
            </ul>
          @endif
        </td>
      </tr>
    </table>
  </div>
@endif

@if($cotizacion->seccion_modulos_nuevos)
  <h2>4. Módulos nuevos y mejoras</h2>
  <p>{{ $cotizacion->seccion_modulos_nuevos }}</p>
@endif

@if(!empty($cotizacion->condiciones))
  <h2>5. Condiciones</h2>
  <ul>
    @foreach($cotizacion->condiciones as $condicion)
      <li>{{ $condicion }}</li>
    @endforeach
  </ul>
@endif

<div class="avoid">
  <h2>6. Aceptación</h2>
  @if(!empty($cotizacion->opciones_aceptacion))
    <p>
      <strong>Plan o servicio seleccionado:</strong> &nbsp;
      @foreach($cotizacion->opciones_aceptacion as $opcion)
        &nbsp;☐ {{ $opcion }} &nbsp;&nbsp;
      @endforeach
    </p>
  @endif
  <p>Fecha de inicio: ____________________</p>

  <table class="sign">
    <tr>
      <td>
        <div class="line">
          <strong>{{ $cotizacion->cliente_empresa }}</strong><br>
          <span class="muted">Nombre, firma y sello</span>
        </div>
      </td>
      <td>
        <div class="line">
          <strong>{{ $cotizacion->proveedor_nombre }}</strong><br>
          <span class="muted">Proveedor</span>
        </div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
