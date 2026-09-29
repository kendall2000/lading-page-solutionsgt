<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Página del sitio (Inicio, Nosotros, Servicios…): va en el menú y se arma con secciones. */
class Pagina extends Model
{
    protected $table = 'paginas';

    /** Direcciones que usa el sistema: una página no puede llamarse así. */
    public const RESERVADAS = [
        'admin', 'login', 'logout', 'sistemas', 'manuales', 'contacto', 'forgot-password', 'reset-password',
        'two-factor-challenge', 'user', 'up', 'storage', 'assets', 'vendors', 'email', 'password', 'chat', 'broadcasting',
    ];

    /** Dirección de la política de privacidad (enlazada en el pie y en los formularios). */
    public const PRIVACIDAD = 'privacidad';

    protected $fillable = [
        'padre_id', 'titulo', 'titulo_menu', 'slug', 'subtitulo', 'imagen_encabezado', 'meta_descripcion',
        'es_inicio', 'en_menu', 'visible', 'orden',
    ];

    protected function casts(): array
    {
        return ['es_inicio' => 'boolean', 'en_menu' => 'boolean', 'visible' => 'boolean', 'orden' => 'integer'];
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id')->orderBy('orden')->orderBy('id');
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class)->orderBy('orden')->orderBy('id');
    }

    public function scopePublicas(Builder $q): Builder
    {
        return $q->where('visible', true);
    }

    public function nombreMenu(): string
    {
        return $this->titulo_menu ?: $this->titulo;
    }

    public function enlace(): string
    {
        return $this->es_inicio ? route('inicio') : route('pagina', $this->slug);
    }

    public function urlEncabezado(): ?string
    {
        return Imagenes::url($this->imagen_encabezado);
    }
}
