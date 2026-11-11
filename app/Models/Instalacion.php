<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Instalación de Nexus ERP de un cliente y su licencia. El vencimiento que recibe Nexus es el mayor entre
 * lo pagado a mano (vence_el), el contrato vinculado (que PayPal extiende solo) y la prórroga. Las etapas
 * se calculan igual que en Nexus (App\Support\Licencia de nexus-erp): este panel solo las muestra; quien
 * las aplica es cada instalación.
 */
class Instalacion extends Model
{
    use RegistraCambios;

    protected $table = 'instalaciones';

    public const TIPOS = ['renta' => 'Renta', 'venta' => 'Venta (sin vencimiento)'];

    public const MODOS = [
        'NORMAL' => ['Normal (según el vencimiento)', 'secondary'],
        'FORZAR_ACTIVO' => ['Forzado activo', 'info'],
        'SUSPENDIDO' => ['Suspendido a mano', 'danger'],
    ];

    /** Etapa => [texto, color]. */
    public const ETAPAS = [
        'AL_DIA' => ['Al día', 'success'],
        'POR_VENCER' => ['Por vencer', 'warning'],
        'GRACIA' => ['En gracia', 'warning'],
        'RESTRINGIDO' => ['Restringido', 'danger'],
        'SOLO_LECTURA' => ['Solo lectura', 'danger'],
    ];

    /** Horas sin consultar al panel para considerarla «sin comunicación» (consulta cada 6 h). */
    public const HORAS_SIN_COMUNICACION = 24;

    protected $fillable = [
        'nombre', 'identificador', 'url', 'tipo', 'contrato_id', 'correo_cobro', 'telefono', 'monto', 'moneda', 'meses_periodo',
        'vence_el', 'prorroga_hasta', 'modo', 'dias_aviso', 'dias_gracia', 'dias_restringido', 'bloquear_facturacion',
        'contacto', 'enlace_pago', 'notas',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'vence_el' => 'date', 'prorroga_hasta' => 'date', 'correo_vence_el' => 'date', 'monto' => 'decimal:2',
            'bloquear_facturacion' => 'boolean', 'meses_periodo' => 'integer', 'ultima_consulta_en' => 'datetime',
            'avisado_en' => 'datetime', 'sin_comunicacion_avisada_en' => 'datetime',
            'dias_aviso' => 'integer', 'dias_gracia' => 'integer', 'dias_restringido' => 'integer',
        ];
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoLicencia::class)->orderByDesc('pagado_el')->orderByDesc('id');
    }

    /** Días de cada etapa: los de la instalación o, si no tiene, los de la configuración. */
    public function dias(string $etapa): int
    {
        return (int) ($this->{'dias_'.$etapa} ?? ConfiguracionLicencias::actual()->{'dias_'.$etapa});
    }

    public function contactoEfectivo(): ?string
    {
        return $this->contacto ?: ConfiguracionLicencias::actual()->contacto;
    }

    public function enlacePagoEfectivo(): ?string
    {
        return $this->enlace_pago ?: ConfiguracionLicencias::actual()->enlace_pago;
    }

    /** Vencimiento sin la prórroga: lo pagado a mano o el contrato (PayPal), el mayor. Null = sin vencimiento. */
    public function vencePagado(): ?CarbonImmutable
    {
        if ($this->tipo === 'venta' || ($this->contrato && $this->contrato->hasta === null)) {
            return null;
        }
        $fechas = collect([$this->vence_el, $this->contrato?->hasta])->filter()->map(fn ($d) => CarbonImmutable::parse($d)->startOfDay());

        return $fechas->isEmpty() ? null : $fechas->max();
    }

    /** Vencimiento que recibe Nexus (con la prórroga). Null = sin vencimiento. */
    public function venceEfectivo(): ?CarbonImmutable
    {
        $pagado = $this->vencePagado();
        if (! $pagado) {
            // Renta sin ningún pago todavía: sin vencimiento hasta registrar el primero.
            return null;
        }
        $prorroga = $this->prorroga_hasta ? CarbonImmutable::parse($this->prorroga_hasta)->startOfDay() : null;

        return $prorroga && $prorroga->gt($pagado) ? $prorroga : $pagado;
    }

    /**
     * Etapa de hoy (la misma cuenta que hace Nexus).
     *
     * @return array{etapa: string, dias: int|null, vence_el: CarbonImmutable|null, siguiente: CarbonImmutable|null}
     */
    public function estado(?CarbonImmutable $hoy = null): array
    {
        $vence = $this->venceEfectivo();
        if ($this->modo === 'FORZAR_ACTIVO') {
            return ['etapa' => 'AL_DIA', 'dias' => null, 'vence_el' => $vence, 'siguiente' => null];
        }
        if ($this->modo === 'SUSPENDIDO') {
            return ['etapa' => 'SOLO_LECTURA', 'dias' => null, 'vence_el' => $vence, 'siguiente' => null];
        }
        if (! $vence) {
            return ['etapa' => 'AL_DIA', 'dias' => null, 'vence_el' => null, 'siguiente' => null];
        }

        $hoy = ($hoy ?? CarbonImmutable::now())->startOfDay();
        $dias = (int) round($vence->diffInDays($hoy, false));
        $gracia = $this->dias('gracia');
        $restringido = $this->dias('restringido');

        [$etapa, $siguiente] = match (true) {
            $dias <= 0 && -$dias > $this->dias('aviso') => ['AL_DIA', null],
            $dias <= 0 => ['POR_VENCER', $vence->addDay()],
            $dias <= $gracia => ['GRACIA', $vence->addDays($gracia + 1)],
            $dias <= $gracia + $restringido => ['RESTRINGIDO', $vence->addDays($gracia + $restringido + 1)],
            default => ['SOLO_LECTURA', null],
        };

        return ['etapa' => $etapa, 'dias' => $dias, 'vence_el' => $vence, 'siguiente' => $siguiente];
    }

    /** @return array{0:string,1:string} */
    public function etapaInfo(?string $etapa = null): array
    {
        return self::ETAPAS[$etapa ?? $this->estado()['etapa']] ?? ['—', 'secondary'];
    }

    /** Pasó más de un día sin consultar al panel (o nunca lo ha hecho). */
    public function sinComunicacion(): bool
    {
        return ! $this->ultima_consulta_en || $this->ultima_consulta_en->lt(now()->subHours(self::HORAS_SIN_COMUNICACION));
    }

    /** Lo que se le firma y envía a Nexus (ver docs/instalacion-clientes/licencia.md en nexus-erp). */
    public function datosLicencia(): array
    {
        return [
            'instalacion' => $this->identificador,
            'emitido_en' => now()->toIso8601String(),
            'vence_el' => $this->venceEfectivo()?->format('Y-m-d'),
            'modo' => $this->modo,
            'dias_aviso' => $this->dias('aviso'),
            'dias_gracia' => $this->dias('gracia'),
            'dias_restringido' => $this->dias('restringido'),
            'bloquear_facturacion' => (bool) $this->bloquear_facturacion,
            'contacto' => $this->contactoEfectivo(),
            'enlace_pago' => $this->enlacePagoEfectivo(),
        ];
    }

    public function urlBase(): string
    {
        return rtrim((string) $this->url, '/');
    }
}
