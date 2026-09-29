<?php

namespace App\Models;

use App\Support\Imagenes;
use Illuminate\Database\Eloquent\Model;

/** Datos generales del sitio público: una sola fila (ver App\Support\Sitio). */
class ConfiguracionSitio extends Model
{
    protected $table = 'configuracion_sitio';

    /** Imágenes configurables: campo => [título, ayuda]. */
    public const IMAGENES = [
        'logo' => ['Logo', 'Barra superior y pie de página. PNG con fondo transparente, horizontal.'],
        'logo_oscuro' => ['Logo para modo oscuro', 'Versión clara del logo (opcional).'],
        'favicon' => ['Ícono de la pestaña', 'Cuadrado, de 64 a 512 px.'],
        'foto' => ['Tu foto', 'Aparece en «Sobre mí». Cuadrada o vertical.'],
        'imagen_hero' => ['Imagen de portada', 'Captura de uno de tus sistemas, horizontal (1600 px aprox.).'],
        'imagen_hero_oscura' => ['Imagen de portada (modo oscuro)', 'La misma captura en modo oscuro (opcional).'],
    ];

    protected $fillable = [
        'nombre', 'eslogan', 'propietario', 'cargo', 'hero_titulo', 'hero_resaltado', 'hero_texto',
        'sobre_titulo', 'sobre_texto', 'telefono', 'whatsapp', 'correo', 'horario',
        'facebook', 'instagram', 'linkedin', 'tiktok', 'youtube', 'github',
        'anios_experiencia', 'proyectos_entregados', 'color_primario', 'meta_descripcion',
        'logo', 'logo_oscuro', 'favicon', 'foto', 'imagen_hero', 'imagen_hero_oscura',
    ];

    protected function casts(): array
    {
        return ['anios_experiencia' => 'integer', 'proyectos_entregados' => 'integer'];
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
