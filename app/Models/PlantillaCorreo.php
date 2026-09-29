<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;

/** Plantilla de correo con variables «{{ nombre }}» que se reemplazan al enviar. */
class PlantillaCorreo extends Model
{
    use RegistraCambios;

    protected $table = 'plantillas_correo';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'asunto', 'contenido', 'variables', 'is_active'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'del_sistema' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * Reemplaza «{{ variable }}». En el cuerpo los valores se escapan (salvo
     * enlaces y colores, que se validan antes); lo que no existe queda visible.
     *
     * @param  array<string, scalar|null>  $datos
     * @return array{asunto: string, html: string}
     */
    public function render(array $datos): array
    {
        $reemplazar = fn (string $texto, bool $html) => preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($datos, $html) {
            if (! array_key_exists($m[1], $datos)) {
                return $m[0];
            }

            return $html ? e((string) $datos[$m[1]]) : (string) $datos[$m[1]];
        }, $texto);

        return ['asunto' => $reemplazar($this->asunto, false), 'html' => $reemplazar($this->contenido, true)];
    }
}
