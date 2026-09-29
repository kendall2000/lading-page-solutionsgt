#!/bin/sh
set -e

# ==========================================================================
# Entrypoint del contenedor solutionsgt (PHP 8.4 + Apache).
# Adaptado del restaurante. Se ejecuta CADA vez que arranca el contenedor:
#  1. Reconstruir la estructura de storage/ (volumen que puede venir vacío).
#  2. Asegurar APP_KEY (solo si falta).
#  3. Cachear config / rutas / vistas / eventos para producción.
#  4. (Opcional) correr migraciones si RUN_MIGRATIONS=true.
#  5. Ceder el control al CMD (apache2-foreground).
# Tareas programadas: contenedor aparte (solutionsgt-tareas, schedule:work), sin cron.
# ==========================================================================

cd /var/www/html

echo "[entrypoint] Preparando aplicación..."

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# SESSION_ENCRYPT=true y los 2 pasos cifran con APP_KEY: no cambiarla después.
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "[entrypoint] APP_KEY ausente — generando..."
    php artisan key:generate --force || true
fi

php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan event:cache || true

# Migraciones opcionales (default: NO, la BD está en un servidor compartido).
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "[entrypoint] RUN_MIGRATIONS=true — ejecutando migraciones..."
    php artisan migrate --force || true
fi

echo "[entrypoint] Listo. Arrancando: $*"
exec "$@"
