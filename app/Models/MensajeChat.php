<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajeChat extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'mensajes_chat';

    protected $fillable = ['conversacion_id', 'autor', 'user_id', 'cuerpo'];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Datos que viajan al navegador (API y evento de tiempo real). */
    public function paraCliente(): array
    {
        return [
            'id' => $this->id,
            'conversacion_id' => $this->conversacion_id,
            'autor' => $this->autor,
            'nombre' => $this->autor === 'admin' ? ($this->usuario?->name ? strtok($this->usuario->name, ' ') : 'Soporte') : null,
            'cuerpo' => $this->cuerpo,
            'hora' => $this->created_at?->timezone(config('app.timezone'))->format('H:i'),
            'fecha' => $this->created_at?->toIso8601String(),
        ];
    }
}
