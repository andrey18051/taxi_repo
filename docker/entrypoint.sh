#!/bin/bash

set -e  # Останавливать скрипт при ошибке

cd /usr/share/nginx/html/taxi

echo "Проверка и создание storage link..."

# Если симлинк уже есть — ничего не делаем
if [ ! -L public/storage ]; then
    echo "Создаём symlink storage → public/storage"
    php artisan storage:link
else
    echo "Storage link уже существует"
fi

# Опционально: другие команды при старте
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache
# php artisan migrate --force

echo "Запуск supervisord..."

# Запускаем основной процесс
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
