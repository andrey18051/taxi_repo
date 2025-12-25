#!/bin/bash
set -e  # Останавливать скрипт при ошибке

mkdir -p /usr/share/nginx/html/taxi/storage/app/public/logs
mkdir -p /usr/share/nginx/html/taxi/storage/app/public/reports
mkdir -p /usr/share/nginx/html/taxi/storage/framework/cache
mkdir -p /usr/share/nginx/html/taxi/storage/framework/sessions
mkdir -p /usr/share/nginx/html/taxi/storage/framework/views
# Устанавливаем права
chmod -R 777 /usr/share/nginx/html/taxi/storage
chown -R www-data:www-data /usr/share/nginx/html/taxi/storage


cd /usr/share/nginx/html/taxi

echo "Проверка и создание storage link..."

# Если симлинк уже есть — ничего не делаем
if [ ! -L public/storage ]; then
    echo "Создаём symlink storage → public/storage"
    php artisan config:clear
    php artisan storage:link
else
    echo "Storage link уже существует"
fi

chmod 777 /usr/share/nginx/html/laravel_logs/laravel.log


echo "Запуск supervisord..."

# Запускаем основной процесс
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
