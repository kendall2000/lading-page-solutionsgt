<?php

namespace App\Models\Concerns;

use App\Services\Bitacora;

/** Anota en la bitácora cuando alguien del panel crea, edita o borra este registro. */
trait RegistraCambios
{
    public static function bootRegistraCambios(): void
    {
        static::created(fn ($modelo) => app(Bitacora::class)->modelo('crear', $modelo));
        static::updated(fn ($modelo) => app(Bitacora::class)->modelo('editar', $modelo));
        static::deleted(fn ($modelo) => app(Bitacora::class)->modelo('borrar', $modelo));
    }
}
