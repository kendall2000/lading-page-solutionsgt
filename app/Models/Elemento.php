<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Elemento repetible de una sección: una foto del carrusel, una tarjeta, una tecnología, una cifra… */
class Elemento extends Model
{
    protected $table = 'elementos';

    protected $fillable = ['seccion_id', 'grupo', 'titulo', 'subtitulo', 'texto', 'icono', 'imagen', 'enlace', 'enlace_texto', 'valor', 'visible', 'orden'];

    protected function casts(): array
    {
        return ['visible' => 'boolean', 'orden' => 'integer'];
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function url(): ?string
    {
        return Imagenes::url($this->imagen);
    }
}
