<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use App\Support\Imagenes;
use App\Support\Sitio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lo que se vende en el sitio (Panel → Ventas → Productos y precios): la suscripción
 * a un sistema, un servicio u otra cosa. Cada uno tiene sus precios por periodo (Precio).
 */
class Producto extends Model
{
    use RegistraCambios;

    protected $table = 'productos';

    /** tipo => [texto, ícono]. */
    public const TIPOS = [
        'sistema' => ['Software', 'fa-laptop-code'],
        'servicio' => ['Servicio', 'fa-screwdriver-wrench'],
        'otro' => ['Otro', 'fa-box'],
    ];

    protected $fillable = ['nombre', 'slug', 'tipo', 'sistema_id', 'descripcion', 'incluye', 'destacado', 'activo', 'orden'];

    protected function casts(): array
    {
        return ['destacado' => 'boolean', 'activo' => 'boolean', 'orden' => 'integer'];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function precios(): HasMany
    {
        return $this->hasMany(Precio::class)->orderByRaw(Precio::ordenSql());
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }

    /** Activos y con algún precio activo (lo que se muestra en el sitio), con esos precios. */
    public function scopeEnVenta(Builder $q): Builder
    {
        return $q->where('activo', true)
            ->whereHas('precios', fn ($p) => $p->where('activo', true))
            ->with(['precios' => fn ($p) => $p->where('activo', true), 'sistema'])
            ->orderBy('orden')->orderBy('nombre');
    }

    /** @return array{0:string,1:string} [texto, ícono] */
    public function tipoInfo(): array
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['otro'];
    }

    /** «De por vida» se habla con un asesor: WhatsApp con mensaje listo o, sin número, el formulario de contacto. */
    public function enlaceAsesor(): string
    {
        $texto = ConfiguracionPagos::actual()->texto_asesor ?: 'Hola, me interesa la licencia de por vida de {producto}.';

        return Sitio::config()->enlaceWhatsapp(str_replace('{producto}', $this->nombre, $texto)) ?? Sitio::enlaceContacto();
    }

    /** @return list<string> */
    public function listaIncluye(): array
    {
        return Imagenes::lineas($this->incluye);
    }
}
