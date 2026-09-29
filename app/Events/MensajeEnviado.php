<?php

namespace App\Events;

use App\Models\MensajeChat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Mensaje nuevo del chat (el «MessageSent» del pedido). Se transmite al instante
 * (ShouldBroadcastNow, variante de ShouldBroadcast sin cola: no hace falta un
 * worker) por el canal privado de la conversación y, si lo escribió el visitante,
 * también por el canal del panel para avisar a quien esté conectado.
 */
class MensajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MensajeChat $mensaje) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $canales = [new PrivateChannel('chat.conversacion.'.$this->mensaje->conversacion_id)];
        if ($this->mensaje->autor === 'visitante') {
            $canales[] = new PrivateChannel('chat.panel');
        }

        return $canales;
    }

    public function broadcastAs(): string
    {
        return 'mensaje.enviado';
    }

    public function broadcastWith(): array
    {
        $conversacion = $this->mensaje->conversacion;

        return [
            'mensaje' => $this->mensaje->paraCliente(),
            'conversacion' => [
                'id' => $conversacion->id,
                'nombre' => $conversacion->nombre,
                'no_leidos_admin' => $conversacion->no_leidos_admin,
                'estado' => $conversacion->estado,
            ],
        ];
    }
}
