<?php

namespace App\Models;

use App\Support\Bloques;
use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Bloque de una página (portada, texto, tarjetas, tecnologías…). Ver App\Support\Bloques. */
class Seccion extends Model
{
    protected $table = 'secciones';

    protected $fillable = [
        'pagina_id', 'tipo', 'etiqueta', 'titulo', 'contenido', 'imagen', 'imagen_oscura',
        'boton_texto', 'boton_enlace', 'boton2_texto', 'boton2_enlace', 'fondo', 'opciones', 'visible', 'orden',
    ];

    protected function casts(): array
    {
        return ['opciones' => 'array', 'visible' => 'boolean', 'orden' => 'integer'];
    }

    public function pagina(): BelongsTo
    {
        return $this->belongsTo(Pagina::class);
    }

    public function elementos(): HasMany
    {
        return $this->hasMany(Elemento::class)->orderBy('orden')->orderBy('id');
    }

    public function opcion(string $clave, mixed $defecto = null): mixed
    {
        return $this->opciones[$clave] ?? $defecto;
    }

    public function url(string $campo = 'imagen'): ?string
    {
        return Imagenes::url($this->{$campo});
    }

    /** Nombre del tipo de bloque para el panel. */
    public function nombreTipo(): string
    {
        return Bloques::TIPOS[$this->tipo]['nombre'] ?? $this->tipo;
    }

    /** Contenido en Markdown convertido a HTML seguro (sin HTML escrito a mano). */
    public function contenidoHtml(): HtmlString
    {
        return self::markdown($this->contenido);
    }

    public static function markdown(?string $texto): HtmlString
    {
        return new HtmlString(Str::markdown((string) $texto, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }
}
