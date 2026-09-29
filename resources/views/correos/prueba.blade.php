{{-- Cuerpo del correo de prueba del servidor. --}}
<div style="text-align:center;">
    <div style="font-size:44px;">✅</div>
    <h2 style="margin:12px 0;">¡Funciona!</h2>
    <p>Si recibes este correo, el servidor de correo de <strong>{{ \App\Support\Sitio::nombre() }}</strong> está bien configurado.</p>
    <p style="color:#8a94ad;font-size:13px;">Enviado el {{ now()->format('d/m/Y H:i') }}</p>
</div>
