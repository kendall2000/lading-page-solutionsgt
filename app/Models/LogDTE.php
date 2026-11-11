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

    /** Campos de INFILE que llevan un XML en base64 (ilegibles en pantalla). */
    private const CAMPOS_XML = ['archivo', 'xml_dte', 'xml_certificado'];

    /** Nombre corto de la acción para mostrar. */
    public function nombreAccion(): string
    {
        return match ($this->accion) {
            'firmar' => 'Firma XML',
            'firmar_anulacion' => 'Firma anulación',
            'certificar' => 'Certificar SAT',
            'anular' => 'Anular SAT',
            default => ucfirst((string) $this->accion),
        };
    }

    /**
     * Errores de validación de la SAT/INFILE como lista
     * [{fuente, categoria, numeral, validacion, mensaje}].
     */
    public function erroresSat(): array
    {
        $json = $this->decodificar($this->response_body);
        $errores = is_array($json['descripcion_errores'] ?? null) ? $json['descripcion_errores'] : [];

        return array_values(array_filter(array_map(fn ($e) => is_array($e) ? [
            'fuente' => $e['fuente'] ?? null,
            'categoria' => $e['categoria'] ?? null,
            'numeral' => $e['numeral'] ?? null,
            'validacion' => $e['validacion'] ?? null,
            'mensaje' => trim((string) ($e['mensaje_error'] ?? '')),
        ] : null, $errores), fn ($e) => $e && $e['mensaje'] !== ''));
    }

    /** Descripción general devuelta por INFILE («Existen errores en la validación…»). */
    public function descripcionRespuesta(): ?string
    {
        $json = $this->decodificar($this->response_body);

        return is_array($json) && ! empty($json['descripcion']) ? (string) $json['descripcion'] : null;
    }

    /** El error más útil para una sola línea: el primer error SAT o el mensaje guardado. */
    public function resumenError(): ?string
    {
        if ($this->exito) {
            return null;
        }

        return $this->erroresSat()[0]['mensaje'] ?? $this->error_mensaje ?? $this->descripcionRespuesta();
    }

    /** JSON de la respuesta con sangría y sin los XML en base64. */
    public function respuestaLegible(): string
    {
        return $this->legible($this->response_body);
    }

    /** JSON de la petición con sangría y sin los XML en base64. */
    public function peticionLegible(): string
    {
        return $this->legible($this->request_body);
    }

    /**
     * XML que viajó en la petición o volvió en la respuesta, decodificado.
     *
     * @return array<string, string> etiqueta => XML
     */
    public function xmls(): array
    {
        $salida = [];
        foreach (['Enviado' => $this->request_body, 'Recibido' => $this->response_body] as $lado => $cuerpo) {
            $json = $this->decodificar($cuerpo);
            if (! is_array($json)) {
                continue;
            }
            foreach (self::CAMPOS_XML as $campo) {
                if (! empty($json[$campo]) && is_string($json[$campo])) {
                    $xml = $this->xmlDesde($json[$campo]);
                    if ($xml !== null) {
                        $salida["{$lado} ({$campo})"] = $xml;
                    }
                }
            }
        }

        return $salida;
    }

    private function legible(mixed $cuerpo): string
    {
        if ($cuerpo === null || $cuerpo === '') {
            return '(vacío)';
        }

        $json = $this->decodificar($cuerpo);
        if (! is_array($json)) {
            return (string) $cuerpo;
        }

        foreach (self::CAMPOS_XML as $campo) {
            if (! empty($json[$campo]) && is_string($json[$campo])) {
                $kb = number_format(strlen($json[$campo]) / 1024, 1);
                $json[$campo] = "[XML en base64 · {$kb} KB — véalo en la pestaña «XML»]";
            }
        }

        return (string) json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decodificar(mixed $cuerpo): mixed
    {
        if (is_array($cuerpo)) {
            return $cuerpo;
        }

        return is_string($cuerpo) && $cuerpo !== '' ? json_decode($cuerpo, true) : null;
    }

    /** Devuelve el XML con sangría, venga en base64 o en texto. */
    private function xmlDesde(string $valor): ?string
    {
        $xml = str_starts_with(ltrim($valor), '<') ? $valor : base64_decode($valor, true);
        if (! is_string($xml) || ! str_starts_with(ltrim($xml), '<')) {
            return null;
        }

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        return @$dom->loadXML($xml) ? (string) $dom->saveXML() : $xml;
    }
}
