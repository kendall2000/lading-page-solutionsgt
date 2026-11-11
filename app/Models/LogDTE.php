<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogDTE extends Model
{
    protected $table = 'logs_dte';

    public $timestamps = false;

    protected $fillable = [
        'documento_id',
        'accion',
        'url',
        'request_body',
        'response_body',
        'codigo_http',
        'exito',
        'error_mensaje',
        'duracion_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'exito' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoTributario::class, 'documento_id');
    }
}
