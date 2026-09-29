<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sistema contratado por una cuenta o por toda una empresa (cliente). */
class Contrato extends Model
{
    use RegistraCambios;

    protected $table = 'contratos';

    protected $fillable = ['sistema_id', 'cuenta_id', 'cliente_id', 'url_acceso', 'plan', 'desde', 'hasta', 'notas'];

    protected function casts(): array
    {
        return ['desde' => 'date', 'hasta' => 'date'];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vigente(): bool
    {
        return $this->hasta === null || $this->hasta->endOfDay()->isFuture();
    }

    /** Días que le quedan (null = sin vencimiento). */
    public function diasRestantes(): ?int
    {
        return $this->hasta ? max(0, (int) now()->startOfDay()->diffInDays($this->hasta->copy()->startOfDay(), false)) : null;
    }
}
