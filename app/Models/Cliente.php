<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'empresa', 'contacto', 'cargo', 'telefono', 'correo', 'sitio_web', 'logo', 'foto', 'sistema_id',
        'testimonio', 'calificacion', 'mostrar_logo', 'mostrar_testimonio', 'notas', 'orden',
    ];

    protected function casts(): array
    {
        return ['mostrar_logo' => 'boolean', 'mostrar_testimonio' => 'boolean', 'calificacion' => 'integer', 'orden' => 'integer'];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function url(string $campo = 'logo'): ?string
    {
        return Imagenes::url($this->{$campo});
    }

    public function iniciales(): string
    {
        $base = $this->contacto ?: $this->empresa;

        return mb_strtoupper(collect(preg_split('/\s+/', trim($base)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }
}
