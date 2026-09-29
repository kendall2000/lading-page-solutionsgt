<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sistema extends Model
{
    protected $table = 'sistemas';

    /** modalidad => [texto, color del badge]. */
    public const MODALIDADES = [
        'gratis' => ['Gratis', 'success'],
        'premium' => ['Premium', 'warning'],
        'a_medida' => ['A la medida', 'info'],
    ];

    protected $fillable = [
        'nombre', 'slug', 'categoria_id', 'modalidad', 'precio', 'acepta_demo', 'acepta_prueba', 'dias_prueba', 'proximamente',
        'resumen', 'descripcion', 'icono', 'caracteristicas', 'tecnologias',
        'url_demo', 'imagen', 'imagen_oscura', 'destacado', 'visible', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'destacado' => 'boolean', 'visible' => 'boolean', 'orden' => 'integer', 'visitas' => 'integer',
            'acepta_demo' => 'boolean', 'acepta_prueba' => 'boolean', 'dias_prueba' => 'integer', 'proximamente' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaSistema::class, 'categoria_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(SistemaImagen::class)->orderBy('orden')->orderBy('id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function manuales(): HasMany
    {
        return $this->hasMany(Manual::class)->orderBy('orden')->orderBy('titulo');
    }

    public function scopePublicos(Builder $q): Builder
    {
        return $q->where('visible', true)->orderBy('orden')->orderBy('nombre');
    }

    public function url(string $campo = 'imagen'): ?string
    {
        return Imagenes::url($this->{$campo});
    }

    /** Se puede pedir demostración (no en los que están en desarrollo). */
    public function ofreceDemo(): bool
    {
        return $this->acepta_demo && ! $this->proximamente;
    }

    /** Se puede pedir una prueba con usuario y contraseña. */
    public function ofrecePrueba(): bool
    {
        return $this->acepta_prueba && ! $this->proximamente;
    }

    /** @return array{0:string,1:string} [texto, color] */
    public function modalidadInfo(): array
    {
        return self::MODALIDADES[$this->modalidad] ?? [ucfirst((string) $this->modalidad), 'secondary'];
    }

    /** @return list<string> */
    public function listaCaracteristicas(): array
    {
        return Imagenes::lineas($this->caracteristicas);
    }

    /** @return list<string> */
    public function listaTecnologias(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tecnologias))));
    }
}
