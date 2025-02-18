#!/bin/bash

FILE="/var/www/html/laravel_logs/laravel.log"
LOG="/var/www/html/laravel_logs/watch_log_debug.log"

echo "Скрипт запущен $(date)" >> "$LOG"

inotifywait -m -e create --format '%f' "$(dirname "$FILE")" | while read FILENAME
do
    echo "Файл обнаружен: $FILENAME" >> "$LOG"
    if [[ "$FILENAME" == "$(basename "$FILE")" ]]; then
        echo "Файл $FILE создан. Меняем разрешения на 777." >> "$LOG"
        chmod 777 "$FILE"
    fi
done
