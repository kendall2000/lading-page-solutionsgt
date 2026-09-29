<?php

use App\Models\Conversacion;
use Illuminate\Support\Facades\Broadcast;

/*
 * Canales privados del chat. Aquí se autorizan los usuarios del PANEL (sesión de Laravel).
 * Los visitantes no tienen usuario: su canal se autoriza en ChatController::autorizar
 * comprobando el token de su cookie contra la conversación.
 */

// Cualquier usuario activo del panel atiende el chat (no hay roles).
Broadcast::channel('chat.panel', fn ($user) => (bool) $user?->is_active);

Broadcast::channel('chat.conversacion.{conversacion}', function ($user, Conversacion $conversacion) {
    return (bool) $user?->is_active;
});
