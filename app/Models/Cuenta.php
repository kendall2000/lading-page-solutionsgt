<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

/**
 * Cuenta de cliente del sitio (portal «Mi cuenta», guard «cliente»). Independiente de los usuarios del panel.
 * Ve sus conversaciones, solicitudes y pruebas, sus sistemas contratados y los manuales privados de esos sistemas.
 */
class Cuenta extends Authenticatable
{
    use RegistraCambios;

    protected $table = 'cuentas';

    protected $fillable = ['nombre', 'correo', 'telefono', 'empresa', 'cliente_id', 'activa'];

    protected $hidden = ['password', 'remember_token', 'token_hash'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', 'activa' => 'boolean', 'correo_verificado_en' => 'datetime', 'token_vence' => 'datetime',
            'invitada_en' => 'datetime', 'ultimo_acceso' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conversaciones(): HasMany
    {
        return $this->hasMany(Conversacion::class)->orderByDesc('ultimo_mensaje_en')->orderByDesc('id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(MensajeContacto::class)->latest();
    }

    public function verificada(): bool
    {
        return $this->correo_verificado_en !== null;
    }

    /** Sistemas contratados por la cuenta o por su empresa. */
    public function contratos(): Builder
    {
        return Contrato::query()->with('sistema')
            ->where(fn ($q) => $q->where('cuenta_id', $this->id)
                ->when($this->cliente_id, fn ($q) => $q->orWhere('cliente_id', $this->cliente_id)))
            ->orderByDesc('id');
    }

    /** @return Collection<int, Contrato> */
    public function contratosVigentes(): Collection
    {
        return $this->contratos()->get()->filter->vigente()->values();
    }

    /** Un manual «solo clientes» lo ve quien tiene vigente el sistema del manual (o cualquier sistema, si es general). */
    public function puedeVerManual(Manual $manual): bool
    {
        if (! $manual->solo_clientes) {
            return true;
        }
        if (! $this->verificada() || ! $this->activa) {
            return false;
        }
        $vigentes = $this->contratosVigentes();

        return $manual->sistema_id ? $vigentes->contains('sistema_id', $manual->sistema_id) : $vigentes->isNotEmpty();
    }

    public function iniciales(): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', trim($this->nombre)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }

    /** Estado para el panel: [texto, color]. */
    public function estadoInfo(): array
    {
        return match (true) {
            ! $this->activa => ['Desactivada', 'secondary'],
            ! $this->verificada() && $this->invitada_en => ['Invitada', 'info'],
            ! $this->verificada() => ['Sin confirmar', 'warning'],
            default => ['Activa', 'success'],
        };
    }
}
