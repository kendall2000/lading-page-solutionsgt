<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = ['nombre', 'precio', 'periodo', 'descripcion', 'incluye', 'destacado', 'visible', 'orden'];

    protected function casts(): array
    {
        return ['destacado' => 'boolean', 'visible' => 'boolean', 'orden' => 'integer'];
    }

    public function scopePublicos(Builder $q): Builder
    {
        return $q->where('visible', true)->orderBy('orden')->orderBy('id');
    }

    /** @return list<string> */
    public function listaIncluye(): array
    {
        return Imagenes::lineas($this->incluye);
    }
}
