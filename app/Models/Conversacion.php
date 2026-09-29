<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Conversación del chat en vivo con un visitante (identificado por el hash del token de su cookie). */
class Conversacion extends Model
{
    protected $table = 'conversaciones';

    protected $fillable = [
        'token_hash', 'nombre', 'correo', 'telefono', 'pagina', 'ip', 'user_agent', 'estado', 'cerrada_en', 'atendida_por',
        'no_leidos_admin', 'no_leidos_visitante', 'ultimo_mensaje_en',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['ultimo_mensaje_en' => 'datetime', 'cerrada_en' => 'datetime', 'no_leidos_admin' => 'integer', 'no_leidos_visitante' => 'integer'];
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(MensajeChat::class)->orderBy('id');
    }

    public function ultimoMensaje(): HasOne
    {
        return $this->hasOne(MensajeChat::class)->latestOfMany();
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Canal privado de esta conversación (visitante y panel). */
    public function canal(): string
    {
        return 'chat.conversacion.'.$this->id;
    }

    public function iniciales(): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', trim($this->nombre)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }
}
