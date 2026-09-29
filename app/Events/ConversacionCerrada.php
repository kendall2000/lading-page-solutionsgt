<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** Soporte cerró la conversación: el widget del visitante muestra el aviso y las opciones. */
class ConversacionCerrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $conversacionId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversacion.'.$this->conversacionId)];
    }

    public function broadcastAs(): string
    {
        return 'conversacion.cerrada';
    }

    public function broadcastWith(): array
    {
        return ['conversacion_id' => $this->conversacionId];
    }
}
