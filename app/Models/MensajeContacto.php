<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajeContacto extends Model
{
    protected $table = 'mensajes_contacto';

    /** estado => [texto, color del badge]. */
    public const ESTADOS = [
        'nuevo' => ['Nuevo', 'primary'],
        'atendido' => ['Atendido', 'info'],
        'cliente' => ['Se volvió cliente', 'success'],
        'descartado' => ['Descartado', 'secondary'],
    ];

    protected $fillable = ['nombre', 'empresa', 'correo', 'telefono', 'sistema_id', 'mensaje', 'estado', 'notas', 'ip'];

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }
}
