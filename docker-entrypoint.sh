#!/bin/bash
set -e

echo "==> Starting entrypoint script..."

# Crear archivo .env con variables de Railway
echo "==> Creating .env file..."

# Parsear DATABASE_URL si existe (Railway lo genera automáticamente)
if [ ! -z "$DATABASE_URL" ]; then
    echo "==> Using DATABASE_URL"
    # Extraer componentes de postgresql://user:pass@host:port/dbname
    DB_USER=$(echo $DATABASE_URL | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
    DB_PASS=$(echo $DATABASE_URL | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
    DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:]*\):.*/\1/p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's/.*:\([0-9]*\)\/.*/\1/p')
    DB_NAME=$(echo $DATABASE_URL | sed -n 's/.*\/\([^?]*\).*/\1/p')
else
    # Usar variables individuales
    DB_USER="${DB_USERNAME}"
    DB_PASS="${DB_PASSWORD}"
    DB_HOST="${DB_HOST}"
    DB_PORT="${DB_PORT:-5432}"
    DB_NAME="${DB_DATABASE}"
fi

cat > /var/www/html/.env << EOF
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY:-base64:H8UOjm/lnv4GBskOaO7fLK8mIqDkdBjUJDnnkwgxJ10=}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="https://web-production-117f.up.railway.app"

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

FILESYSTEM_DISK=public
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error
EOF

echo "==> .env created successfully"
echo "DB_HOST: ${DB_HOST}"
echo "DB_DATABASE: ${DB_NAME}"

# Usar puerto de Railway
export PORT=${PORT:-8080}
echo "==> Using PORT: $PORT"

# Actualizar configuración de Nginx
if [ -f /etc/nginx/sites-available/default ]; then
    sed -i "s/listen [0-9]*;/listen $PORT;/" /etc/nginx/sites-available/default
    echo "==> Nginx configured for port $PORT"
fi

# Crear enlace simbólico para storage (imágenes públicas)
echo "==> Creating storage link..."
php artisan storage:link || echo "Storage link already exists"

# Asegurar permisos correctos
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/public/storage

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
