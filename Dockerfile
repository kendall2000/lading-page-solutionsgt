FROM php:8.4-apache

# Imagen del sitio Solutions GT (adaptada de la del restaurante). No lleva Node:
# la plantilla Phoenix ya viene compilada en public/assets y public/vendors.

# Dependencias del sistema + librerías para extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        ca-certificates \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        gd \
        intl \
        bcmath \
        zip \
        exif \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: DocumentRoot apunta a /public y habilita mod_rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!\${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers \
    # No anunciar versión de Apache/SO en encabezados ni páginas de error.
    && { echo 'ServerTokens Prod'; echo 'ServerSignature Off'; } > /etc/apache2/conf-available/zz-seguridad.conf \
    && a2enconf zz-seguridad

# Timezone del sistema operativo del container = Guatemala
RUN ln -snf /usr/share/zoneinfo/America/Guatemala /etc/localtime \
    && echo 'America/Guatemala' > /etc/timezone

# Configuración de PHP recomendada para producción
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'memory_limit=512M'; \
        echo 'upload_max_filesize=10M'; \
        echo 'post_max_size=12M'; \
        echo 'max_execution_time=120'; \
        echo 'date.timezone=America/Guatemala'; \
        echo 'expose_php=Off'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/html

# 1) Instalar dependencias PHP (capa cacheable mientras no cambien)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# 2) Copiamos el resto del código (ver .dockerignore: sin .env y sin la plantilla)
COPY . .

# 3) Autoload final optimizado
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Permisos para Laravel (las imágenes van a Contabo, no al disco)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint: prepara storage, cachea config/rutas/views y arranca Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD curl -fsS http://localhost/up || exit 1
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
