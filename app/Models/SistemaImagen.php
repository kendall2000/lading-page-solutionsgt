<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SistemaImagen extends Model
{
    protected $table = 'sistema_imagenes';

    protected $fillable = ['sistema_id', 'ruta', 'titulo', 'orden'];

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function url(): ?string
    {
        return Imagenes::url($this->ruta);
    }
}
