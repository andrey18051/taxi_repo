# Используем официальный образ Redis
FROM redis:alpine

# Задаем рабочую директорию (необязательно)
WORKDIR /data

# Копируем ваш файл конфигурации Redis (если требуется)
# COPY redis.conf /usr/local/etc/redis/redis.conf

# Открываем порт для Redis
EXPOSE 6379

# Запускаем Redis сервер
# Если вы используете свой файл конфигурации, добавьте `--config /usr/local/etc/redis/redis.conf`
CMD ["redis-server"]
