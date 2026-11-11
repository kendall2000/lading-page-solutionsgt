<?php

namespace App\Services;

use App\Models\ConfiguracionLicencias;
use App\Models\Contrato;
use App\Models\Instalacion;
use App\Models\PagoLicencia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Licencias de las instalaciones de Nexus ERP en renta: pagos a mano, prórrogas, modos, el token de cada
 * instalación, la respuesta firmada que consulta Nexus y el aviso «consulta ya» que se le manda.
 * Protocolo completo en nexus-erp: docs/instalacion-clientes/licencia.md.
 */
class Licencias
{
    public function __construct(private Correos $correos) {}

    // ── Token de la instalación ─────────────────────────────────────────────

    /** Token nuevo (el anterior deja de servir). Devuelve el token en claro: se muestra una sola vez. */
    public function nuevoToken(Instalacion $i): string
    {
        $token = Str::random(48);
        $i->forceFill(['token_hash' => hash('sha256', $token)])->saveQuietly();

        return $token;
    }

    public function buscarPorToken(string $token): ?Instalacion
    {
        return $token === '' ? null : Instalacion::query()->where('token_hash', hash('sha256', $token))->first();
    }

    // ── Consulta de Nexus ───────────────────────────────────────────────────

    /** Anota lo que informó la instalación (monitoreo) y le arma la respuesta firmada. */
    public function responder(Instalacion $i, array $informe, ?string $ip): array
    {
        $num = fn ($v) => is_numeric($v) && $v >= 0 ? (int) min($v, 4294967295) : null;
        $etapa = (string) ($informe['etapa'] ?? '');
        $i->forceFill([
            'ultima_consulta_en' => now(),
            'ip_consulta' => $ip,
            'version' => isset($informe['version']) ? mb_substr((string) $informe['version'], 0, 40) : null,
            'usuarios_activos' => $num($informe['usuarios_activos'] ?? null),
            'empresas_activas' => $num($informe['empresas_activas'] ?? null),
            'etapa_reportada' => array_key_exists($etapa, Instalacion::ETAPAS) ? $etapa : null,
            'sin_comunicacion_avisada_en' => null,
        ])->saveQuietly();

        return $this->firmada($i);
    }

    /** {datos: base64(JSON), firma: base64(Ed25519 de esos bytes)}. */
    public function firmada(Instalacion $i): array
    {
        $crudo = json_encode($i->datosLicencia(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return ['datos' => base64_encode($crudo), 'firma' => base64_encode(ConfiguracionLicencias::actual()->firmar($crudo))];
    }

    // ── Aviso «consulta ya» ─────────────────────────────────────────────────

    /**
     * Le pide a la instalación que consulte su licencia ahora (no le manda datos: ella pregunta y
     * comprueba la firma). Devuelve true si la instalación confirmó que aplicó la respuesta.
     */
    public function avisar(Instalacion $i): bool
    {
        try {
            $r = Http::timeout(10)->acceptJson()->post($i->urlBase().'/licencia/sincronizar');
            $ok = $r->successful() && $r->json('ok') === true;
            $resultado = $ok ? 'Aplicada' : match (true) {
                $r->status() === 404 => 'La instalación no tiene configurado el panel (NEXUS_LICENCIA_*).',
                $r->successful() => 'La instalación no pudo consultar al panel o rechazó la respuesta (revisa su token y clave pública).',
                default => 'La instalación respondió '.$r->status().'.',
            };
        } catch (Throwable $e) {
            $ok = false;
            $resultado = 'No se pudo conectar con la instalación: '.Str::limit($e->getMessage(), 150);
        }
        $i->forceFill(['avisado_en' => now(), 'aviso_resultado' => Str::limit($resultado, 250, '')])->saveQuietly();

        return $ok;
    }

    // ── Pagos, prórrogas y modos ────────────────────────────────────────────

    /**
     * Registra un pago a mano: corre el vencimiento N meses desde el vencimiento anterior (quien paga tarde no gana
     * días; si no había ninguno, desde el día del pago), quita una suspensión manual y avisa a la instalación.
     */
    public function registrarPago(Instalacion $i, array $datos, ?int $usuarioId): PagoLicencia
    {
        $pago = DB::transaction(function () use ($i, $datos, $usuarioId) {
            $i = Instalacion::query()->lockForUpdate()->findOrFail($i->id);
            $antes = $i->vencePagado();
            $base = $antes ?? CarbonImmutable::parse($datos['pagado_el'])->subDay();
            $despues = $base->addMonthsNoOverflow((int) $datos['meses']);

            $pago = PagoLicencia::query()->create([
                'instalacion_id' => $i->id, 'pagado_el' => $datos['pagado_el'], 'monto' => $datos['monto'], 'moneda' => $datos['moneda'],
                'metodo' => $datos['metodo'], 'referencia' => $datos['referencia'] ?? null, 'meses' => (int) $datos['meses'],
                'vence_antes' => $i->vence_el, 'vence_despues' => $despues->format('Y-m-d'), 'notas' => $datos['notas'] ?? null, 'user_id' => $usuarioId,
            ]);
            $i->vence_el = $despues;
            if ($i->prorroga_hasta && CarbonImmutable::parse($i->prorroga_hasta)->lte($despues)) {
                $i->prorroga_hasta = null;
            }
            if ($i->modo === 'SUSPENDIDO') {
                $i->modo = 'NORMAL';
            }
            $i->save();

            return $pago;
        });
        $this->avisar($i->refresh());

        return $pago;
    }

    /** Anula el último pago vigente: el vencimiento vuelve al que tenía antes de ese pago. */
    public function anularPago(PagoLicencia $pago): void
    {
        DB::transaction(function () use ($pago) {
            $i = Instalacion::query()->lockForUpdate()->findOrFail($pago->instalacion_id);
            $ultimo = $i->pagos()->whereNull('anulado_en')->first();
            if (! $ultimo || $ultimo->id !== $pago->id) {
                throw new RuntimeException('Solo se puede anular el último pago registrado.');
            }
            $pago->update(['anulado_en' => now()]);
            $i->update(['vence_el' => $pago->vence_antes]);
        });
        $this->avisar($pago->instalacion->refresh());
    }

    /** Prórroga: N días más desde hoy o desde el vencimiento, lo que sea mayor (sin registrar un pago). */
    public function prorrogar(Instalacion $i, int $dias): void
    {
        $desde = collect([CarbonImmutable::today(), $i->venceEfectivo()])->filter()->max();
        $i->update(['prorroga_hasta' => $desde->addDays($dias)]);
        $this->avisar($i);
    }

    public function cambiarModo(Instalacion $i, string $modo): void
    {
        $i->update(['modo' => $modo]);
        $this->avisar($i);
    }

    /** PayPal extendió (o cambió) un contrato: avisa a las instalaciones vinculadas. */
    public function contratoCambio(Contrato $contrato): void
    {
        foreach (Instalacion::query()->where('contrato_id', $contrato->id)->get() as $i) {
            $this->avisar($i);
        }
    }

    // ── Revisión diaria (correos) ───────────────────────────────────────────

    /**
     * Avisa por correo al cliente cuando su instalación entra en una etapa (por vencer, gracia, restringido,
     * solo lectura) y a ti («Avisos a») cuando entra en mora o deja de comunicarse. Cada aviso una sola vez.
     *
     * @return array{clientes: int, avisos: int}
     */
    public function revisar(): array
    {
        $enviados = ['clientes' => 0, 'avisos' => 0];
        foreach (Instalacion::query()->with('contrato')->where('tipo', 'renta')->get() as $i) {
            $e = $i->estado();
            $vence = $e['vence_el']?->format('Y-m-d');
            $yaAvisada = $i->correo_etapa === $e['etapa'] && $i->correo_vence_el?->format('Y-m-d') === $vence;

            if ($e['etapa'] !== 'AL_DIA' && ! $yaAvisada) {
                [$titulo, $texto] = $this->textoEtapa($e, $i);
                if ($i->correo_cobro) {
                    $this->correos->enviar('licencia_aviso', $i->correo_cobro, [
                        'cliente' => $i->nombre, 'titulo' => $titulo, 'texto' => $texto,
                        'vence' => $e['vence_el']?->format('d/m/Y') ?? '—', 'siguiente' => $e['siguiente']?->format('d/m/Y') ?? '—',
                        'contacto' => $i->contactoEfectivo() ?? '—', 'enlace' => $i->enlacePagoEfectivo() ?? '',
                    ]);
                    $enviados['clientes']++;
                }
                if ($e['etapa'] !== 'POR_VENCER') {
                    $this->correos->avisar('licencia_estado', ['cliente' => $i->nombre, 'titulo' => $titulo, 'texto' => $texto, 'enlace' => route('admin.licencias.show', $i)]);
                    $enviados['avisos']++;
                }
            }
            if ($e['etapa'] === 'AL_DIA' || ! $yaAvisada) {
                $i->forceFill(['correo_etapa' => $e['etapa'], 'correo_vence_el' => $vence])->saveQuietly();
            }

            // Sin comunicación (solo las que ya se habían conectado alguna vez).
            if ($i->ultima_consulta_en && $i->sinComunicacion() && ! $i->sin_comunicacion_avisada_en) {
                $this->correos->avisar('licencia_estado', [
                    'cliente' => $i->nombre, 'titulo' => 'sin comunicación con el panel',
                    'texto' => 'La instalación no consulta su licencia desde el '.$i->ultima_consulta_en->format('d/m/Y H:i').'. Puede estar apagada, sin internet o con el panel mal configurado. Mientras tanto aplica lo último que supo.',
                    'enlace' => route('admin.licencias.show', $i),
                ]);
                $i->forceFill(['sin_comunicacion_avisada_en' => now()])->saveQuietly();
                $enviados['avisos']++;
            }
        }

        return $enviados;
    }

    /** @return array{0:string,1:string} */
    private function textoEtapa(array $e, Instalacion $i): array
    {
        $f = fn (?CarbonImmutable $d) => $d?->format('d/m/Y') ?? '—';
        $facturar = $i->bloquear_facturacion ? 'tampoco se puede facturar' : 'sí se puede facturar y registrar cobros';

        return match ($e['etapa']) {
            'POR_VENCER' => ['El servicio de Nexus vence pronto', 'El servicio vence el '.$f($e['vence_el']).'. Realiza el pago para evitar interrupciones.'],
            'GRACIA' => ['Pago pendiente del servicio de Nexus', 'El servicio venció el '.$f($e['vence_el']).'. Todo sigue funcionando, pero a partir del '.$f($e['siguiente']).' no se podrán crear registros nuevos.'],
            'RESTRINGIDO' => ['Servicio de Nexus restringido', 'Por el pago pendiente no se pueden crear registros nuevos ('.$facturar.'). A partir del '.$f($e['siguiente']).' el sistema quedará en solo lectura.'],
            default => ['Servicio de Nexus en solo lectura', 'Por el pago pendiente el sistema está en solo lectura. La información sigue intacta y se puede consultar y exportar. Al registrarse el pago todo vuelve a la normalidad de inmediato.'],
        };
    }
}
