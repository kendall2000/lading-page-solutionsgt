<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaSistema extends Model
{
    protected $table = 'categorias_sistema';

    protected $fillable = ['nombre', 'slug', 'icono', 'orden'];

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    public function sistemas(): HasMany
    {
        return $this->hasMany(Sistema::class, 'categoria_id');
    }
}
