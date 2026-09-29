<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un registro de la bitácora de cambios del panel (ver App\Services\Bitacora). */
class BitacoraCambio extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bitacora_cambios';

    protected $guarded = ['id'];

    /** accion => [texto, color, ícono feather]. */
    public const ACCIONES = [
        'crear' => ['Creó', 'success', 'plus-circle'],
        'editar' => ['Editó', 'info', 'edit-3'],
        'borrar' => ['Borró', 'danger', 'trash-2'],
        'entrar' => ['Entró al panel', 'primary', 'log-in'],
        'salir' => ['Salió del panel', 'secondary', 'log-out'],
        'fallido' => ['Acceso fallido', 'warning', 'alert-triangle'],
    ];

    protected function casts(): array
    {
        return ['cambios' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0:string,1:string,2:string} */
    public function accionInfo(): array
    {
        return self::ACCIONES[$this->accion] ?? [ucfirst($this->accion), 'secondary', 'circle'];
    }
}
