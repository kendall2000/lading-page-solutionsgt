<?php

namespace App\Services;

use App\Mail\CorreoPlantilla;
use App\Models\ConfiguracionCorreo;
use App\Models\PlantillaCorreo;
use App\Support\Sitio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envío de correos con plantilla (igual que en el restaurante). Nunca rompe la
 * operación: si no hay plantilla, el servidor está apagado o falla el envío,
 * lo anota en la bitácora y devuelve false.
 */
class Correos
{
    /** @param  array<string, scalar|null>  $datos */
    public function enviar(string $codigo, string $destinatario, array $datos = [], ?int $usuarioId = null, ?string $responderA = null): bool
    {
        $plantilla = PlantillaCorreo::query()->where('codigo', $codigo)->where('is_active', true)->first();
        if (! $plantilla) {
            $this->anotar($codigo, $destinatario, null, 'sin_plantilla', null, $usuarioId);

            return false;
        }
        if (! ConfiguracionCorreo::actual()->is_active) {
            $this->anotar($codigo, $destinatario, $plantilla->asunto, 'desactivado', null, $usuarioId);

            return false;
        }

        return $this->mandar($plantilla->render($this->base() + $datos), $destinatario, $codigo, $usuarioId, $responderA);
    }

    /**
     * Envía una plantilla a todos los correos de «Avisos a» (p. ej. mensaje nuevo, respondiendo al visitante).
     *
     * @param  array<string, scalar|null>  $datos
     */
    public function avisar(string $codigo, array $datos, ?string $responderA = null): void
    {
        foreach (ConfiguracionCorreo::actual()->correosAvisos() as $correo) {
            $this->enviar($codigo, $correo, $datos, null, $responderA);
        }
    }

    /**
     * Envía ya renderizado (pruebas de la pantalla de Correos).
     *
     * @param  array{asunto: string, html: string}  $render
     */
    public function mandar(array $render, string $destinatario, ?string $codigo = null, ?int $usuarioId = null, ?string $responderA = null): bool
    {
        try {
            Mail::to($destinatario)->send(new CorreoPlantilla($render['asunto'], $render['html'], $responderA));
            $this->anotar($codigo, $destinatario, $render['asunto'], 'enviado', null, $usuarioId);

            return true;
        } catch (Throwable $e) {
            report($e);
            $this->anotar($codigo, $destinatario, $render['asunto'], 'fallido', mb_substr($e->getMessage(), 0, 2000), $usuarioId);

            return false;
        }
    }

    /** Variables que tienen todas las plantillas. */
    public function base(): array
    {
        return ['sistema' => Sitio::nombre(), 'color' => Sitio::config()->color_primario ?: '#3874ff'];
    }

    private function anotar(?string $plantilla, string $destinatario, ?string $asunto, string $estado, ?string $error, ?int $usuarioId): void
    {
        rescue(fn () => DB::table('bitacora_correos')->insert([
            'plantilla' => $plantilla, 'destinatario' => mb_substr($destinatario, 0, 200), 'asunto' => $asunto ? mb_substr($asunto, 0, 250) : null,
            'estado' => $estado, 'error' => $error, 'usuario_id' => $usuarioId, 'created_at' => now(),
        ]), null, false);
    }
}
