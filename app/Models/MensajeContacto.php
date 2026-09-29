<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lo que llega por los formularios del sitio: contacto, solicitud de demostración o de prueba. */
class MensajeContacto extends Model
{
    protected $table = 'mensajes_contacto';

    /** tipo => [texto, color del badge, título para el aviso]. */
    public const TIPOS = [
        'contacto' => ['Mensaje', 'secondary', 'Nuevo mensaje'],
        'demo' => ['Demostración', 'info', 'Solicitud de demostración'],
        'prueba' => ['Prueba', 'warning', 'Solicitud de prueba'],
    ];

    /** estado => [texto, color del badge]. */
    public const ESTADOS = [
        'nuevo' => ['Nuevo', 'primary'],
        'atendido' => ['Atendido', 'info'],
        'cliente' => ['Se volvió cliente', 'success'],
        'descartado' => ['Descartado', 'secondary'],
    ];

    protected $fillable = [
        'tipo', 'nombre', 'empresa', 'correo', 'telefono', 'sistema_id', 'mensaje', 'estado', 'notas', 'ip',
        'url_acceso', 'usuario_prueba', 'clave_prueba', 'vence_el', 'credenciales_enviadas_en',
    ];

    protected $hidden = ['clave_prueba'];

    protected function casts(): array
    {
        return ['clave_prueba' => 'encrypted', 'vence_el' => 'date', 'credenciales_enviadas_en' => 'datetime'];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    /** @return array{0:string,1:string,2:string} */
    public function tipoInfo(): array
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['contacto'];
    }
}
