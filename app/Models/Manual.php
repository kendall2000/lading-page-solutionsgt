<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

/** Manual de uso de un sistema: texto (Markdown), PDF descargable y/o video. */
class Manual extends Model
{
    use RegistraCambios;

    protected $table = 'manuales';

    protected $fillable = ['sistema_id', 'titulo', 'slug', 'resumen', 'contenido', 'archivo', 'video_url', 'visible', 'solo_clientes', 'orden'];

    protected function casts(): array
    {
        return ['visible' => 'boolean', 'solo_clientes' => 'boolean', 'orden' => 'integer', 'visitas' => 'integer'];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function scopePublicos(Builder $q): Builder
    {
        return $q->where('visible', true)->orderBy('orden')->orderBy('titulo');
    }

    public function urlArchivo(): ?string
    {
        return Imagenes::url($this->archivo);
    }

    public function contenidoHtml(): HtmlString
    {
        return Seccion::markdown($this->contenido);
    }

    /** Dirección para incrustar el video (YouTube o Vimeo); null si no se reconoce. */
    public function videoEmbebido(): ?string
    {
        $url = (string) $this->video_url;
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
