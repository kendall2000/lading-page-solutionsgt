<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** Un lado leyó los mensajes del otro hasta cierto id (para mostrar ✓✓ Visto). */
class MensajesLeidos implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /** @param  string  $lector  «admin» o «visitante» (quien leyó) */
    public function __construct(public int $conversacionId, public string $lector, public int $hastaId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversacion.'.$this->conversacionId)];
    }

    public function broadcastAs(): string
    {
        return 'mensajes.leidos';
    }

    public function broadcastWith(): array
    {
        return ['conversacion_id' => $this->conversacionId, 'lector' => $this->lector, 'hasta_id' => $this->hastaId];
    }
}
