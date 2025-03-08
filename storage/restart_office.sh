#!/bin/bash

# Получение текущего времени
current_time=$(date +"%Y-%m-%d %H:%M:%S")

# Проверка доступности сайта
if ! curl -s --head --request GET https://korzhov-office.kharkiv.ua/ | head -n 1 | grep -q "200"; then
    echo "$current_time - Сайт недоступен. Перезапускаем контейнер." >> /usr/share/nginx/html/laravel_logs/restart_office.log

    # Остановка контейнера
    docker stop office

    # Удаление контейнера
   # docker rm office

    # Удаление образа
   # docker rmi ghcr.io/andrey18051/office:1.0

    # Запуск нового контейнера
   # docker run --name office -d --network host ghcr.io/andrey18051/office:1.0
   docker start office
else
    echo "$current_time - Сайт доступен. Перезапуск не требуется." >> /usr/share/nginx/html/laravel_logs/restart_office.log
fi
