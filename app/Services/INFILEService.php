<?php

namespace App\Services;

use App\Models\ConfiguracionDTE;
use App\Models\DocumentoTributario;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class INFILEService
{
    /**
     * Paso 1: Firma electrónica del XML mediante WebService de INFILE.
     */
    public function firmar(string $xml, ConfiguracionDTE $cfg, ?DocumentoTributario $dte = null): string
    {
        $url = $cfg->url_firma . '?alias=' . urlencode($cfg->alias_ws);

        $payload = [
            'alias'   => $cfg->alias_ws,
            'llave'   => $cfg->token_signer,
            'archivo' => base64_encode($xml),
            'codigo'  => (string) ($dte?->id ?? uniqid('dte-')),
            'es_anulacion' => 'N',
        ];

        $response = Http::timeout(30)->asJson()->post($url, $payload);

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

        $response = Http::timeout(30)
            ->withHeaders([
                'usuario'       => $cfg->alias_ws,
                'llave'         => $cfg->llave_ws,
                'identificador' => $codigo,
                'Content-Type'  => 'application/json',
            ])
            ->asJson()
            ->post($cfg->url_certificacion, $payload);

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
}
