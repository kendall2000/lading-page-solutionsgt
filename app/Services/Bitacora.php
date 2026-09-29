<?php

namespace App\Services;

use App\Models\BitacoraCambio;
use App\Models\CategoriaSistema;
use App\Models\Cliente;
use App\Models\ConfiguracionCorreo;
use App\Models\ConfiguracionSitio;
use App\Models\Conversacion;
use App\Models\Direccion;
use App\Models\Elemento;
use App\Models\Manual;
use App\Models\MensajeContacto;
use App\Models\Pagina;
use App\Models\PlantillaCorreo;
use App\Models\Seccion;
use App\Models\Servicio;
use App\Models\Sistema;
use App\Models\SistemaImagen;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Bitácora de cambios del panel: quién creó, editó o borró qué, y entradas al panel.
 * Solo anota lo que hace un usuario con sesión (lo que hacen visitantes, tareas y seeders no).
 * Nunca guarda contraseñas ni claves: los campos secretos quedan como «(cambió)».
 */
class Bitacora
{
    /** Modelo => [módulo del panel, nombre del registro]. */
    public const MODELOS = [
        Pagina::class => ['Páginas', 'Página'], Seccion::class => ['Páginas', 'Sección'], Elemento::class => ['Páginas', 'Elemento'],
        Sistema::class => ['Software', 'Sistema'], SistemaImagen::class => ['Software', 'Imagen de sistema'], CategoriaSistema::class => ['Software', 'Categoría'],
        Manual::class => ['Manuales', 'Manual'], Servicio::class => ['Planes', 'Plan'], Cliente::class => ['Clientes', 'Cliente'],
        Direccion::class => ['Direcciones', 'Dirección'], MensajeContacto::class => ['Solicitudes', 'Solicitud'], Conversacion::class => ['Chat', 'Conversación'],
        User::class => ['Usuarios', 'Usuario'], ConfiguracionSitio::class => ['Configuración', 'Configuración del sistema'],
        ConfiguracionCorreo::class => ['Correos', 'Servidor de correo'], PlantillaCorreo::class => ['Correos', 'Plantilla'],
    ];

    /** Campos que cambian solos (contadores, marcas de tiempo): no son un cambio de nadie. */
    private const IGNORAR = [
        'created_at', 'updated_at', 'visitas', 'remember_token', 'ultimo_acceso', 'no_leidos_admin', 'no_leidos_visitante',
        'ultimo_mensaje_en', 'probado_en', 'leido_en',
    ];

    /** Secretos aunque el modelo no los oculte. */
    private const SECRETOS = ['password', 'clave', 'clave_prueba', 'token_hash', 'two_factor_secret', 'two_factor_recovery_codes'];

    public function modelo(string $accion, Model $modelo): void
    {
        $usuario = auth()->user();
        if (! $usuario instanceof User) {
            return;
        }
        $cambios = null;
        if ($accion === 'editar' && ! $cambios = $this->diferencias($modelo)) {
            return;
        }

        [$modulo] = self::MODELOS[$modelo::class] ?? [class_basename($modelo)];
        $this->anotar($accion, $modulo, $this->describir($modelo), $usuario, $cambios, $modelo);
    }

    /** $nombre: quién, cuando no hay usuario (p. ej. el correo de un acceso fallido). */
    public function anotar(string $accion, string $modulo, string $descripcion, ?User $usuario = null, ?array $cambios = null, ?Model $modelo = null, ?string $nombre = null): void
    {
        // La bitácora nunca debe impedir guardar lo que se estaba guardando.
        rescue(fn () => BitacoraCambio::query()->create([
            'user_id' => $usuario?->id,
            'usuario' => Str::limit($nombre ?? $usuario?->name ?? '—', 145, ''),
            'accion' => $accion,
            'modulo' => $modulo,
            'modelo_tipo' => $modelo ? $modelo::class : null,
            'modelo_id' => $modelo?->getKey(),
            'descripcion' => Str::limit($descripcion, 250),
            'cambios' => $cambios,
            'ip' => request()?->ip(),
        ]), null, false);
    }

    /** @return array<string, array{0: mixed, 1: mixed}>|null campo => [antes, después] */
    private function diferencias(Model $modelo): ?array
    {
        $secretos = array_merge(self::SECRETOS, $modelo->getHidden());
        $cambios = [];
        foreach (array_keys($modelo->getChanges()) as $campo) {
            if (in_array($campo, self::IGNORAR, true)) {
                continue;
            }
            $cambios[$campo] = in_array($campo, $secretos, true)
                ? ['•••', '(cambió)']
                : [$this->valor($modelo->getOriginal($campo)), $this->valor($modelo->getAttribute($campo))];
        }

        return $cambios ?: null;
    }

    private function valor(mixed $valor): mixed
    {
        return match (true) {
            $valor instanceof \DateTimeInterface => $valor->format('Y-m-d H:i'),
            is_array($valor), is_object($valor) => Str::limit((string) json_encode($valor, JSON_UNESCAPED_UNICODE), 300),
            is_string($valor) => Str::limit($valor, 300),
            default => $valor,
        };
    }

    /** Nombre legible: Página «Nosotros», Usuario «Ana López». */
    private function describir(Model $modelo): string
    {
        $tipo = self::MODELOS[$modelo::class][1] ?? class_basename($modelo);
        $nombre = collect(['titulo', 'nombre', 'empresa', 'name', 'email', 'tipo', 'ruta'])
            ->map(fn ($c) => $modelo->getAttribute($c))->first(fn ($v) => is_string($v) && $v !== '');

        return $nombre ? $tipo.' «'.Str::limit($nombre, 120).'»' : "{$tipo} #{$modelo->getKey()}";
    }
}
