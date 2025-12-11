#!/bin/bash
set -e

echo "==> Starting entrypoint script..."

# Usar puerto de Railway
export PORT=${PORT:-8080}
echo "==> Using PORT: $PORT"

# Actualizar configuración de Nginx
if [ -f /etc/nginx/sites-available/default ]; then
    sed -i "s/listen [0-9]*;/listen $PORT;/" /etc/nginx/sites-available/default
    echo "==> Nginx configured for port $PORT"
fi

# Ejecutar migraciones
echo "==> Running migrations..."
php artisan migrate --force || echo "Warning: Migrations failed or already up to date"

# Iniciar Nginx en background
echo "==> Starting Nginx..."
nginx -g 'daemon off;' &

# Iniciar PHP-FPM en foreground
echo "==> Starting PHP-FPM..."
exec php-fpm
