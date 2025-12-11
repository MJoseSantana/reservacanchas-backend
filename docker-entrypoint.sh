#!/bin/bash
set -e

echo "==> Starting entrypoint script..."

# Crear archivo .env con variables de Railway
echo "==> Creating .env file..."
cat > /var/www/html/.env << EOF
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-https://web-production-117f.up.railway.app}"

DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE}"
DB_USERNAME="${DB_USERNAME}"
DB_PASSWORD="${DB_PASSWORD}"

CACHE_STORE="${CACHE_STORE:-file}"
SESSION_DRIVER="${SESSION_DRIVER:-file}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

LOG_CHANNEL="${LOG_CHANNEL:-stack}"
LOG_LEVEL="${LOG_LEVEL:-error}"
EOF

echo "==> .env created successfully"
cat /var/www/html/.env

# Usar puerto de Railway
export PORT=${PORT:-8080}
echo "==> Using PORT: $PORT"

# Actualizar configuración de Nginx
if [ -f /etc/nginx/sites-available/default ]; then
    sed -i "s/listen [0-9]*;/listen $PORT;/" /etc/nginx/sites-available/default
    echo "==> Nginx configured for port $PORT"
fi

# Limpiar cachés de Laravel
echo "==> Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Ejecutar migraciones
echo "==> Running migrations..."
php artisan migrate --force || echo "Warning: Migrations failed or already up to date"

# Iniciar Nginx en background
echo "==> Starting Nginx..."
nginx -g 'daemon off;' &

# Iniciar PHP-FPM en foreground
echo "==> Starting PHP-FPM..."
exec php-fpm
