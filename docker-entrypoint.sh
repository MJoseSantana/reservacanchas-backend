#!/bin/bash

# Descubrir paquetes de Laravel (necesario porque se instaló con --no-scripts)
echo "Descubriendo paquetes de Laravel..."
php artisan package:discover --ansi || true

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ]; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force
fi

# Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Limpiar y cachear configuración
echo "Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar Nginx y PHP-FPM
echo "Iniciando servicios..."
service nginx start
php-fpm
