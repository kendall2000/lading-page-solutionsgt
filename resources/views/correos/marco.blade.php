{{-- Marco de todos los correos: logo, nombre y color del sistema; el cuerpo viene de la plantilla. --}}
@php $cfg = \App\Support\Sitio::config(); $color = $cfg->color_primario ?: '#3874ff'; @endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#31374a;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e3e6ed;">
        <div style="background:{{ $color }};padding:18px 28px;color:#ffffff;">
            @if ($cfg->logo)
                <img src="{{ $cfg->url('logo') }}" alt="{{ \App\Support\Sitio::nombre() }}" style="max-height:40px;max-width:200px;" />
            @else
                <strong style="font-size:18px;">{{ \App\Support\Sitio::nombre() }}</strong>
            @endif
        </div>
        <div style="padding:28px;line-height:1.6;font-size:15px;">
            {!! $cuerpo !!}
        </div>
        <div style="padding:16px 28px;background:#f9fafb;color:#8a94ad;font-size:12px;text-align:center;">
            {{ \App\Support\Sitio::nombre() }}@if ($cfg->telefono) · {{ $cfg->telefono }}@endif @if ($cfg->correo) · {{ $cfg->correo }}@endif<br><a href="{{ url("/") }}" style="color:#8a94ad;">{{ preg_replace("#^https?://#", "", url("/")) }}</a>
        </div>
    </div>
</body>
</html>
