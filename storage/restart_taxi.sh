#!/bin/bash

# Получение текущего времени
current_time=$(date +"%Y-%m-%d %H:%M:%S")
log_file="/usr/share/nginx/html/laravel_logs/restart_taxi.log"

# Список сайтов для проверки
sites=(
    "https://m.easy-order-taxi.site/"
    "https://test-taxi.kyiv.ua/"
)

# Флаг для перезапуска
restart_needed=false

# Проверка доступности сайтов
for site in "${sites[@]}"; do
    if ! curl -s --head --fail "$site" > /dev/null; then
        echo "$current_time - Сайт $site недоступен." >> "$log_file"
        restart_needed=true
    else
        echo "$current_time - Сайт $site доступен." >> "$log_file"
    fi
done

# Перезапуск контейнеров, если нужно
if [ "$restart_needed" = true ]; then
    echo "$current_time - Перезапускаем контейнеры." >> "$log_file"
    docker stop taxi_work
    docker start taxi_work

    docker stop taxi_test
    docker start taxi_test
else
    echo "$current_time - Перезапуск не требуется." >> "$log_file"
fi
