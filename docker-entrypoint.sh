#!/bin/bash
set -e

# Usar puerto de Railway
export PORT=${PORT:-8080}
sed -i "s/listen 8080;/listen $PORT;/" /etc/nginx/sites-available/default

echo "==> Ejecutando migraciones..."
php artisan migrate --force || echo "Warning: Migrations failed"

echo "==> Iniciando Nginx + PHP-FPM en puerto $PORT..."
service nginx start
exec php-fpm
