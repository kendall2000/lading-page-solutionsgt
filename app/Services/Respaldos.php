<?php

namespace App\Services;

use App\Support\Imagenes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Respaldos de la base en Contabo (carpeta «respaldos/» del bucket).
 * El archivo va comprimido y cifrado con APP_KEY y con un nombre imposible de adivinar:
 * aunque el bucket se comparta para las imágenes, nadie puede leerlo sin la llave.
 */
class Respaldos
{
    public const CARPETA = 'respaldos';

    /** Cuántos se conservan (uno diario = 30 días; elegido por el usuario el 2026-09-29). */
    public const CONSERVAR = 30;

    /** Crea el respaldo, lo sube y borra los que sobran. Devuelve la ruta en Contabo. */
    public function crear(string $conexion): string
    {
        $sql = $this->volcar($conexion);
        $ruta = self::CARPETA.'/'.config("database.connections.{$conexion}.database").'-'.now()->format('Y-m-d-His')
            .'-'.Str::lower(Str::random(24)).'.sql.gz.enc';

        Storage::disk(Imagenes::DISCO)->put($ruta, Crypt::encryptString(gzencode($sql, 9)), 'private');
        $this->podar();

        return $ruta;
    }

    /** @return Collection<int, string> rutas, del más nuevo al más viejo (el nombre lleva la fecha). */
    public function lista(): Collection
    {
        return collect(Storage::disk(Imagenes::DISCO)->files(self::CARPETA))
            ->filter(fn ($f) => str_ends_with($f, '.sql.gz.enc'))->sortDesc()->values();
    }

    /** Contenido SQL (sin comprimir) de un respaldo. */
    public function leer(string $ruta): string
    {
        $sql = gzdecode(Crypt::decryptString(Storage::disk(Imagenes::DISCO)->get($ruta)));
        throw_if($sql === false, RuntimeException::class, 'El respaldo está dañado.');

        return $sql;
    }

    /** Deja solo los CONSERVAR más nuevos (si un día falla, no se borra ninguno de más). */
    private function podar(): void
    {
        $sobran = $this->lista()->slice(self::CONSERVAR)->all();
        if ($sobran) {
            Storage::disk(Imagenes::DISCO)->delete($sobran);
        }
    }

    private function volcar(string $conexion): string
    {
        $bd = config("database.connections.{$conexion}");
        throw_unless(in_array($bd['driver'] ?? null, ['mysql', 'mariadb'], true), RuntimeException::class,
            "La conexión «{$conexion}» no es MySQL: no se puede respaldar con mysqldump.");

        // Cliente de MariaDB (paquete mariadb-client de la imagen). La contraseña va por variable de
        // entorno, no en la línea de comandos (se vería con «ps»). El servidor usa certificado propio:
        // se cifra la conexión pero sin validar el certificado.
        $proceso = Process::timeout(600)->env(['MYSQL_PWD' => (string) $bd['password']])->run([
            'mariadb-dump', '--single-transaction', '--quick', '--no-tablespaces', '--skip-lock-tables',
            '--skip-ssl-verify-server-cert', '--default-character-set=utf8mb4',
            '--host='.$bd['host'], '--port='.$bd['port'], '--user='.$bd['username'], $bd['database'],
        ]);
        throw_unless($proceso->successful() && str_contains($proceso->output(), 'CREATE TABLE'), RuntimeException::class,
            'mariadb-dump falló: '.Str::limit(trim($proceso->errorOutput()) ?: 'no devolvió datos.', 500));

        // Las versiones nuevas empiezan con una línea «sandbox» que el cliente de MySQL no entiende al restaurar.
        return preg_replace('~\A/\*M!999999\\\\- enable the sandbox mode \*/\R~', '', $proceso->output());
    }
}
