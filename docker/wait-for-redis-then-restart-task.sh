#!/bin/bash

echo "Ожидание Redis на redis_server:6379..."

# Ждем, пока Redis не станет доступен
until nc -z 127.0.0.1 6379; do
  echo "Redis не доступен, пробуем снова через 1 секунду..."
  sleep 1
done

echo "Redis готов. Запускаем команду restart-task:run"

/opt/bitnami/php/bin/php /usr/share/nginx/html/taxi/artisan restart-task:run
