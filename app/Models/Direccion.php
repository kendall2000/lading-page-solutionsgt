<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    protected $table = 'direcciones';

    protected $fillable = ['nombre', 'direccion', 'ciudad', 'telefono', 'horario', 'latitud', 'longitud', 'principal', 'visible', 'orden'];

    protected function casts(): array
    {
        return ['principal' => 'boolean', 'visible' => 'boolean', 'orden' => 'integer', 'latitud' => 'float', 'longitud' => 'float'];
    }

    public function scopePublicas(Builder $q): Builder
    {
        return $q->where('visible', true)->orderByDesc('principal')->orderBy('orden')->orderBy('id');
    }

    private function consultaMapa(): string
    {
        return $this->latitud && $this->longitud
            ? $this->latitud.','.$this->longitud
            : trim($this->direccion.' '.$this->ciudad);
    }

    /** Mapa de Google embebido (sin llave de API): por coordenadas o por dirección. */
    public function mapaEmbebido(): string
    {
        return 'https://maps.google.com/maps?q='.rawurlencode($this->consultaMapa()).'&z=15&output=embed';
    }

    public function enlaceMapa(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->consultaMapa());
    }
}
