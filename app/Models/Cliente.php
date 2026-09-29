<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use RegistraCambios;

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

    /** Cuentas de clientes (personas) de esta empresa. */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class)->orderBy('nombre');
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
