#!/bin/bash

# Usar puerto de Railway (por defecto 8080 si no existe)
export PORT=${PORT:-8080}

# Actualizar configuración de Nginx con el puerto correcto
sed -i "s/listen 8080;/listen $PORT;/" /etc/nginx/sites-available/default

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ]; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force
fi

# Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Optimizar aplicación (sin config:cache que puede causar problemas)
echo "Optimizando aplicación..."
php artisan route:cache
php artisan view:cache

# Iniciar Nginx y PHP-FPM
echo "Iniciando servicios en puerto $PORT..."
service nginx start
php-fpm
