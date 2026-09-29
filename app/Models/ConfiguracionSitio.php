<?php

namespace App\Models;

use App\Models\Concerns\RegistraCambios;
use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;

/** Datos generales del sitio público: una sola fila (ver App\Support\Sitio). */
class ConfiguracionSitio extends Model
{
    use RegistraCambios;

    protected $table = 'configuracion_sitio';

    /** Imágenes configurables: campo => [título, ayuda]. */
    public const IMAGENES = [
        'logo' => ['Logo', 'Menú del sitio, panel y correos. PNG con fondo transparente, horizontal.'],
        'logo_oscuro' => ['Logo para modo oscuro', 'Versión clara del logo: pie de página y modo oscuro (opcional).'],
        'favicon' => ['Ícono de la pestaña', 'El iconito del navegador y del chat. Cuadrado, de 64 a 512 px.'],
        'fondo_login' => ['Fondo del inicio de sesión', 'Imagen de la mitad izquierda del acceso al panel. Mínimo 1200 px.'],
    ];

    protected $fillable = [
        'nombre', 'eslogan', 'telefono', 'whatsapp', 'correo', 'horario',
        'facebook', 'instagram', 'linkedin', 'tiktok', 'youtube', 'github',
        'color_primario', 'color_secundario', 'meta_descripcion', 'logo', 'logo_oscuro', 'favicon', 'fondo_login',
        'login_titulo', 'login_subtitulo', 'pie_texto',
        'chat_activo', 'chat_titulo', 'chat_bienvenida',
    ];

    protected function casts(): array
    {
        return ['chat_activo' => 'boolean'];
    }

    public function url(string $campo): ?string
    {
        return Imagenes::url($this->{$campo});
    }

    /** Enlace de WhatsApp con mensaje inicial (o null si no hay número). */
    public function enlaceWhatsapp(string $texto = ''): ?string
    {
        $numero = preg_replace('/\D/', '', (string) $this->whatsapp);
        if ($numero === '') {
            return null;
        }
        if (strlen($numero) === 8) {
            $numero = '502'.$numero; // Guatemala
        }

        return 'https://wa.me/'.$numero.($texto !== '' ? '?text='.rawurlencode($texto) : '');
    }

    /** @return array<string, array{0:string,1:string}> red => [url, ícono], solo las que tienen enlace. */
    public function redes(): array
    {
        $iconos = [
            'facebook' => 'fa-brands fa-facebook', 'instagram' => 'fa-brands fa-instagram', 'linkedin' => 'fa-brands fa-linkedin-in',
            'tiktok' => 'fa-brands fa-tiktok', 'youtube' => 'fa-brands fa-youtube', 'github' => 'fa-brands fa-github',
        ];

        return collect($iconos)->filter(fn ($i, $red) => filled($this->{$red}))
            ->map(fn ($icono, $red) => [$this->{$red}, $icono])->all();
    }
}
