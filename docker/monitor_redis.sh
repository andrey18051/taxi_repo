#!/bin/bash

# Массив с именами контейнеров, в которых хранятся логи приложения
APP_CONTAINERS=("taxi_work" "taxi_test")

# Путь к логам внутри контейнера
LOG_FILE="/usr/share/nginx/html/laravel_logs/laravel.log"

# Сообщение об ошибке, которую нужно отслеживать
ERROR_MESSAGE="READONLY You can't write against a read only replica"

# Имя контейнера Redis
REDIS_CONTAINER="redis_server"

# Путь к директории с приложением для выполнения команды artisan
APP_DIR="/usr/share/nginx/html/taxi"

# Путь к PHP CLI
PHP_CLI="/opt/bitnami/php/bin/php"

# Бесконечный цикл для мониторинга
while true; do
  for APP_CONTAINER in "${APP_CONTAINERS[@]}"; do
    # Проверка на наличие ошибки в логах приложения (читаем логи из контейнера с приложением)
    if docker exec "$APP_CONTAINER" grep -q "$ERROR_MESSAGE" "$LOG_FILE"; then
      echo "$(date): Ошибка 'READONLY' обнаружена в логах Redis в контейнере $APP_CONTAINER. Перезапуск контейнера Redis..."

      # Останавливаем контейнер Redis
      docker stop "$REDIS_CONTAINER"

      # Удаляем контейнер Redis
     docker rm "$REDIS_CONTAINER"

      # Перезапускаем контейнер Redis с нужными параметрами
#      mkdir /tmp/redis || true && echo "cluster-enabled no" > /tmp/redis/redis.conf && docker run -d --name redis_server -v /tmp/redis/redis.conf:/usr/local/etc/redis/redis.conf -p 6379:6379 redis:alpine redis-server /usr/local/etc/redis/redis.conf
      mkdir /tmp/redis || true && echo "cluster-enabled no" > /tmp/redis/redis.conf && docker run -d --name redis_server -v /tmp/redis/redis.conf:/usr/local/etc/redis/redis.conf -p 127.0.0.1:6379:6379 redis:alpine redis-server /usr/local/etc/redis/redis.conf
      # Очистка логов через команду artisan
      echo "$(date): Очистка логов приложения..."
      docker exec "$APP_CONTAINER" bash -c "cd $APP_DIR && $PHP_CLI artisan logs:send"

      # Прерываем цикл, так как перезапуск Redis уже произошел
      break
    fi
  done

  # Ожидание 10 секунд перед следующей проверкой
  sleep 10
done





