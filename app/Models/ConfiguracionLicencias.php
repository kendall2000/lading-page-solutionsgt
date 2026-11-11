<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;

/**
 * Licencias de Nexus (una fila, Panel → Licencias Nexus → Configuración): el par de claves Ed25519 con
 * que el panel firma cada respuesta (la privada cifrada con APP_KEY; se genera sola la primera vez) y los
 * valores por defecto de las instalaciones (contacto, enlace de pago y días de cada etapa).
 */
class ConfiguracionLicencias extends Model
{
    use RegistraCambios;

    protected $table = 'configuracion_licencias';

    protected $fillable = ['contacto', 'enlace_pago', 'dias_aviso', 'dias_gracia', 'dias_restringido'];

    protected $hidden = ['clave_privada'];

    protected function casts(): array
    {
        return ['clave_privada' => 'encrypted', 'dias_aviso' => 'integer', 'dias_gracia' => 'integer', 'dias_restringido' => 'integer'];
    }

    /** La fila de configuración, con su par de claves (una consulta por petición). */
    public static function actual(): self
    {
        return once(function () {
            $cfg = self::query()->firstOrCreate([], ['dias_aviso' => 7, 'dias_gracia' => 7, 'dias_restringido' => 8]);
            if (! $cfg->clave_privada || ! $cfg->clave_publica) {
                $par = sodium_crypto_sign_keypair();
                $cfg->forceFill([
                    'clave_privada' => base64_encode(sodium_crypto_sign_secretkey($par)),
                    'clave_publica' => base64_encode(sodium_crypto_sign_publickey($par)),
                ])->saveQuietly();
            }

            return $cfg;
        });
    }

    public function firmar(string $datos): string
    {
        return sodium_crypto_sign_detached($datos, base64_decode($this->clave_privada));
    }
}
