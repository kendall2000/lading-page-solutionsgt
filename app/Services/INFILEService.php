<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use App\Models\LogDTE;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class INFILEService
{
    /**
     * Paso 1: Firma electrónica del XML mediante WebService de INFILE.
     */
    public function firmar(string $xml, ConfiguracionDTE $cfg, ?DocumentoTributario $dte = null, bool $esAnulacion = false): string
    {
        $url = $cfg->url_firma . '?alias=' . urlencode($cfg->alias_ws);

        $payload = [
            'alias'   => $cfg->alias_ws,
            'llave'   => $cfg->token_signer,
            'archivo' => base64_encode($xml),
            'codigo'  => (string) ($dte?->id ?? uniqid('dte-')),
            'es_anulacion' => $esAnulacion ? 'S' : 'N',
        ];

        $ini = microtime(true);
        $response = Http::timeout(30)->asJson()->post($url, $payload);
        $ms = (int) ((microtime(true) - $ini) * 1000);

        $this->guardarLog($esAnulacion ? 'firmar_anulacion' : 'firmar', $url, json_encode($payload, JSON_UNESCAPED_UNICODE), $response, $ms, $dte?->id);

        if (!$response->successful()) {
            throw new RuntimeException('Error HTTP ' . $response->status() . ' al firmar: ' . $response->body());
        }

        $json = $response->json();
        if (!is_array($json) || !($json['resultado'] ?? false)) {
            $desc = $json['descripcion'] ?? $response->body();
            throw new RuntimeException('INFILE rechazó la firma: ' . $desc);
        }

        $archivoB64 = $json['archivo'] ?? null;
        if (empty($archivoB64)) {
            throw new RuntimeException('Firma exitosa pero no devolvió el XML firmado.');
        }

        $xmlFirmado = base64_decode($archivoB64, strict: false);
        if ($xmlFirmado === false || strlen($xmlFirmado) < 100) {
            throw new RuntimeException('El XML firmado devuelto es inválido.');
        }

        return $xmlFirmado;
    }

    /**
     * Paso 2: Certificación del XML firmado ante la SAT.
     */
    public function certificar(string $xmlFirmado, ConfiguracionDTE $cfg, ?DocumentoTributario $dte = null): array
    {
        $codigo = (string) ($dte?->id ?? uniqid('dte-'));

        $payload = [
            'nit_emisor'   => $cfg->nit_emisor,
            'correo_copia' => $cfg->correo_emisor ?? '',
            'xml_dte'      => base64_encode($xmlFirmado),
        ];

        $ini = microtime(true);
        $response = Http::timeout(30)
            ->withHeaders([
                'usuario'       => $cfg->alias_ws,
                'llave'         => $cfg->llave_ws,
                'identificador' => $codigo,
                'Content-Type'  => 'application/json',
            ])
            ->asJson()
            ->post($cfg->url_certificacion, $payload);

        $ms = (int) ((microtime(true) - $ini) * 1000);
        $this->guardarLog('certificar', $cfg->url_certificacion, json_encode($payload, JSON_UNESCAPED_UNICODE), $response, $ms, $dte?->id);

        if (!$response->successful()) {
            throw new RuntimeException('Error HTTP ' . $response->status() . ' al certificar: ' . $response->body());
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Respuesta de certificación no es válida: ' . substr($response->body(), 0, 500));
        }

        if (!($json['resultado'] ?? false)) {
            $desc = $json['descripcion'] ?? '';
            $errores = $json['descripcion_errores'] ?? [];
            $detalle = !empty($errores) ? implode(' | ', array_column($errores, 'mensaje_error')) : '';
            throw new RuntimeException('SAT / INFILE rechazó la certificación: ' . $desc . ($detalle ? ' — ' . $detalle : ''));
        }

        return [
            'uuid'            => $json['uuid'] ?? null,
            'serie'           => $json['serie'] ?? null,
            'numero'          => (string) ($json['numero'] ?? ''),
            'fecha'           => $json['fecha'] ?? null,
            'xml_certificado' => $json['xml_certificado'] ?? null,
        ];
    }

    /**
     * Paso 2 de la anulación: envía el XML de anulación firmado.
     * Mismo formato que la certificación (headers usuario/llave/identificador).
     */
    public function anular(string $xmlFirmado, ConfiguracionDTE $cfg, ?DocumentoTributario $dte = null): void
    {
        $codigo = 'anul-'.($dte?->id ?? uniqid());

        $payload = [
            'nit_emisor'   => $cfg->nit_emisor,
            'correo_copia' => $cfg->correo_emisor ?? '',
            'xml_dte'      => base64_encode($xmlFirmado),
        ];

        $ini = microtime(true);
        $response = Http::timeout(30)
            ->withHeaders([
                'usuario'       => $cfg->alias_ws,
                'llave'         => $cfg->llave_ws,
                'identificador' => $codigo,
                'Content-Type'  => 'application/json',
            ])
            ->asJson()
            ->post($cfg->url_anulacion, $payload);

        $ms = (int) ((microtime(true) - $ini) * 1000);
        $this->guardarLog('anular', $cfg->url_anulacion, json_encode($payload, JSON_UNESCAPED_UNICODE), $response, $ms, $dte?->id);

        if (!$response->successful()) {
            throw new RuntimeException('Error HTTP ' . $response->status() . ' al anular: ' . $response->body());
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Respuesta de anulación no es válida: ' . substr($response->body(), 0, 500));
        }

        if (!($json['resultado'] ?? false)) {
            $desc = $json['descripcion'] ?? '';
            $errores = $json['descripcion_errores'] ?? [];
            $detalle = !empty($errores) ? implode(' | ', array_column($errores, 'mensaje_error')) : '';
            throw new RuntimeException('SAT / INFILE rechazó la anulación: ' . $desc . ($detalle ? ' — ' . $detalle : ''));
        }
    }

    /**
     * Registra auditoría completa de peticiones HTTP, respuestas JSON, duración y errores en logs_dte.
     */
    private function guardarLog(
        string $accion,
        string $url,
        string $requestBody,
        Response $response,
        int $duracionMs,
        ?int $documentoId
    ): void {
        try {
            $json = $response->json();
            $exito = $response->successful() && is_array($json) && ($json['resultado'] ?? false);
            $errorMensaje = null;

            if (!$exito) {
                if (is_array($json)) {
                    $desc = $json['descripcion'] ?? '';
                    $errores = $json['descripcion_errores'] ?? [];
                    $detalle = !empty($errores) ? implode(' | ', array_column($errores, 'mensaje_error')) : '';
                    $errorMensaje = $desc . ($detalle ? ' — ' . $detalle : '');
                } else {
                    $errorMensaje = 'HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 400);
                }
            }

            LogDTE::create([
                'documento_id'  => $documentoId,
                'accion'        => $accion,
                'url'           => $url,
                'request_body'  => $requestBody,
                'response_body' => $response->body(),
                'codigo_http'   => $response->status(),
                'exito'         => $exito,
                'error_mensaje' => $errorMensaje,
                'duracion_ms'   => $duracionMs,
                'created_at'    => now(),
            ]);
        } catch (Throwable) {
            // No interrumpir el flujo principal si el logging falla
        }
    }
}
