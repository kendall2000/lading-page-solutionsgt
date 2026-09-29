<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Subir / bajar un registro entre sus hermanos (columna «orden»). */
class Orden
{
    /** @param  Builder  $hermanos  consulta de los registros del mismo grupo (incluido el que se mueve) */
    public static function mover(Model $registro, string $direccion, Builder $hermanos): void
    {
        DB::transaction(function () use ($registro, $direccion, $hermanos) {
            $lista = $hermanos->orderBy('orden')->orderBy('id')->get()->values();
            $i = $lista->search(fn ($m) => $m->is($registro));
            $j = $direccion === 'arriba' ? $i - 1 : $i + 1;
            if ($i === false || ! isset($lista[$j])) {
                return;
            }
            [$lista[$i], $lista[$j]] = [$lista[$j], $lista[$i]];
            foreach ($lista as $pos => $m) {
                if ($m->orden !== $pos + 1) {
                    $m->forceFill(['orden' => $pos + 1])->saveQuietly();
                }
            }
        });
    }

    /** Siguiente número de orden para un registro nuevo del grupo. */
    public static function siguiente(Builder $hermanos): int
    {
        return (int) $hermanos->max('orden') + 1;
    }
}
