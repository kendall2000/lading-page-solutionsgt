<?php

namespace App\Support;

/**
 * Tipos de sección con los que se arman las páginas. Cada tipo dice qué campos
 * pide en el panel, qué opciones tiene y, si lleva elementos repetibles
 * (fotos, tarjetas, tecnologías…), qué datos pide de cada uno.
 * La vista pública de cada tipo está en resources/views/publico/bloques/{tipo}.blade.php.
 */
class Bloques
{
    public const TIPOS = [
        'portada' => [
            'nombre' => 'Portada', 'icono' => 'star',
            'descripcion' => 'Título grande con palabra resaltada, texto, dos botones e imagen al lado.',
            'campos' => ['etiqueta', 'titulo', 'contenido', 'imagen', 'imagen_oscura', 'boton', 'boton2'],
            'opciones' => ['resaltado'],
        ],
        'carrusel' => [
            'nombre' => 'Carrusel de fotos', 'icono' => 'image',
            'descripcion' => 'Fotos grandes que pasan solas, cada una con su título, texto y botón.',
            'campos' => [],
            'opciones' => ['altura'],
            'elementos' => ['nombre' => 'Fotos', 'uno' => 'foto', 'imagen_obligatoria' => true, 'campos' => [
                'imagen' => 'Foto (horizontal, 1920 px aprox.)', 'titulo' => 'Título', 'texto' => 'Texto', 'enlace_texto' => 'Texto del botón', 'enlace' => 'Enlace del botón',
            ]],
        ],
        'texto' => [
            'nombre' => 'Texto con imagen', 'icono' => 'align-left',
            'descripcion' => 'Un título y párrafos (quiénes somos, misión…) con una imagen opcional al lado.',
            'campos' => ['etiqueta', 'titulo', 'contenido', 'imagen', 'boton'],
            'opciones' => ['lado', 'alineacion'],
        ],
        'tarjetas' => [
            'nombre' => 'Tarjetas', 'icono' => 'grid',
            'descripcion' => 'Varias tarjetas con ícono o imagen, título y texto. Ideal para servicios, valores o beneficios.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => ['columnas'],
            'elementos' => ['nombre' => 'Tarjetas', 'uno' => 'tarjeta', 'campos' => [
                'icono' => 'Ícono', 'imagen' => 'Imagen (en lugar del ícono)', 'titulo' => 'Título', 'texto' => 'Texto', 'enlace_texto' => 'Texto del enlace', 'enlace' => 'Enlace',
            ]],
        ],
        'tecnologias' => [
            'nombre' => 'Tecnologías', 'icono' => 'cpu',
            'descripcion' => 'Lenguajes, frameworks, bases de datos y herramientas con las que trabajas, agrupados por categoría.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => [],
            'elementos' => ['nombre' => 'Tecnologías', 'uno' => 'tecnología', 'campos' => [
                'grupo' => 'Categoría (Lenguajes, Frameworks, Bases de datos…)', 'titulo' => 'Nombre', 'icono' => 'Ícono', 'imagen' => 'Logo (en lugar del ícono)', 'texto' => 'Descripción corta',
            ]],
        ],
        'galeria' => [
            'nombre' => 'Galería de fotos', 'icono' => 'camera',
            'descripcion' => 'Fotos en cuadrícula que se abren en grande; con filtros si les pones grupo.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => [],
            'elementos' => ['nombre' => 'Fotos', 'uno' => 'foto', 'imagen_obligatoria' => true, 'campos' => [
                'imagen' => 'Foto', 'titulo' => 'Descripción', 'grupo' => 'Grupo (para filtrar)',
            ]],
        ],
        'cifras' => [
            'nombre' => 'Cifras', 'icono' => 'trending-up',
            'descripcion' => 'Números animados: años de experiencia, clientes, proyectos…',
            'campos' => ['etiqueta', 'titulo'],
            'opciones' => [],
            'elementos' => ['nombre' => 'Cifras', 'uno' => 'cifra', 'campos' => [
                'valor' => 'Número', 'subtitulo' => 'Sufijo (+, %, K)', 'titulo' => 'Descripción',
            ]],
        ],
        'preguntas' => [
            'nombre' => 'Preguntas frecuentes', 'icono' => 'help-circle',
            'descripcion' => 'Preguntas que se abren para ver la respuesta.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => [],
            'elementos' => ['nombre' => 'Preguntas', 'uno' => 'pregunta', 'campos' => ['titulo' => 'Pregunta', 'texto' => 'Respuesta']],
        ],
        'sistemas' => [
            'nombre' => 'Catálogo de software', 'icono' => 'package',
            'descripcion' => 'Tus sistemas (de «Software»), con filtros por categoría, tipo y botones de demo o prueba.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => ['estilo', 'categoria_id', 'solo_destacados', 'filtros', 'limite'],
        ],
        'manuales' => [
            'nombre' => 'Manuales', 'icono' => 'book-open',
            'descripcion' => 'Lista de manuales (de «Manuales»), agrupados por sistema.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => ['sistema_id'],
        ],
        'planes' => [
            'nombre' => 'Planes y precios', 'icono' => 'tag',
            'descripcion' => 'Los planes de «Planes y precios» en tarjetas.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => [],
        ],
        'testimonios' => [
            'nombre' => 'Testimonios', 'icono' => 'message-circle',
            'descripcion' => 'Lo que dicen tus clientes (de «Clientes», los que tienen testimonio visible).',
            'campos' => ['etiqueta', 'titulo'],
            'opciones' => [],
        ],
        'clientes' => [
            'nombre' => 'Logos de clientes', 'icono' => 'briefcase',
            'descripcion' => 'Logos de las empresas que confían en ti (de «Clientes»).',
            'campos' => ['titulo'],
            'opciones' => [],
        ],
        'llamado' => [
            'nombre' => 'Llamado a la acción', 'icono' => 'zap',
            'descripcion' => 'Recuadro destacado para invitar a escribir, pedir una demo o cotizar.',
            'campos' => ['etiqueta', 'titulo', 'contenido', 'imagen', 'boton', 'boton2'],
            'opciones' => ['whatsapp'],
        ],
        'contacto' => [
            'nombre' => 'Contacto', 'icono' => 'mail',
            'descripcion' => 'Formulario de contacto, teléfonos, direcciones, redes y mapa.',
            'campos' => ['etiqueta', 'titulo', 'contenido'],
            'opciones' => ['mapa'],
        ],
    ];

    public const FONDOS = ['claro' => 'Claro', 'suave' => 'Color suave', 'oscuro' => 'Oscuro'];

    public static function tieneElementos(string $tipo): bool
    {
        return isset(self::TIPOS[$tipo]['elementos']);
    }

    public static function usa(string $tipo, string $campo): bool
    {
        return in_array($campo, self::TIPOS[$tipo]['campos'] ?? [], true);
    }

    /** Enlace válido: dirección completa, ruta del sitio, ancla, correo o teléfono. */
    public const REGLA_ENLACE = ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|#|mailto:|tel:)/i'];

    public const MENSAJE_ENLACE = 'El enlace debe empezar con https://, / (página del sitio), # (sección), mailto: o tel:.';
}
