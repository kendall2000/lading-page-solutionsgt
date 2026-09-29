<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sistema extends Model
{
    protected $table = 'sistemas';

    protected $fillable = [
        'nombre', 'slug', 'resumen', 'descripcion', 'icono', 'caracteristicas', 'tecnologias',
        'url_demo', 'imagen', 'imagen_oscura', 'destacado', 'visible', 'orden',
    ];

    protected function casts(): array
    {
        return ['destacado' => 'boolean', 'visible' => 'boolean', 'orden' => 'integer', 'visitas' => 'integer'];
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(SistemaImagen::class)->orderBy('orden')->orderBy('id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function scopePublicos(Builder $q): Builder
    {
        return $q->where('visible', true)->orderBy('orden')->orderBy('nombre');
    }

    public function url(string $campo = 'imagen'): ?string
    {
        return Imagenes::url($this->{$campo});
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
