<?php

namespace App\Providers;

use App\Models\ConfiguracionCorreo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Aplica el servidor de correo guardado en el panel (Correos). El SMTP vive en
 * la base, no en el .env: cambiarlo no requiere tocar archivos ni reconstruir.
 * Si está apagado, App\Services\Correos no envía y lo anota en la bitácora.
 */
class CorreoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $cfg = ConfiguracionCorreo::query()->first();
        } catch (Throwable) {
            return;
        }
        if (! $cfg?->is_active || ! $cfg->host) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $cfg->host);
        Config::set('mail.mailers.smtp.port', $cfg->puerto ?: 587);
        Config::set('mail.mailers.smtp.username', $cfg->usuario);
        Config::set('mail.mailers.smtp.password', rescue(fn () => $cfg->clave, null, false));
        // 465 o «ssl» = SSL directo (smtps); lo demás, STARTTLS.
        Config::set('mail.mailers.smtp.scheme', ((int) $cfg->puerto === 465 || $cfg->cifrado === 'ssl') ? 'smtps' : 'smtp');
        if ($cfg->remitente_correo) {
            Config::set('mail.from.address', $cfg->remitente_correo);
            Config::set('mail.from.name', $cfg->remitente_nombre ?: config('app.name'));
        }
    }
}
