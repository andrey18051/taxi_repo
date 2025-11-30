#!/bin/bash

# Правильные пути
LARAVEL_LOG_DIR="/usr/share/nginx/html/taxi/storage/logs"
LARAVEL_LOG_FILE="$LARAVEL_LOG_DIR/laravel.log"
DEBUG_LOG="/usr/share/nginx/html/laravel_logs/watch_log_debug.log"

# Создаем директории если не существуют
mkdir -p "$LARAVEL_LOG_DIR"
mkdir -p "$(dirname "$DEBUG_LOG")"

echo "=== Скрипт watch_log.sh запущен $(date) ===" >> "$DEBUG_LOG"
echo "Laravel log dir: $LARAVEL_LOG_DIR" >> "$DEBUG_LOG"
echo "Laravel log file: $LARAVEL_LOG_FILE" >> "$DEBUG_LOG"

# Проверяем существование inotifywait
if ! command -v inotifywait &> /dev/null; then
    echo "ERROR: inotifywait не найден!" >> "$DEBUG_LOG"
    exit 1
fi

echo "inotifywait найден, начинаем мониторинг..." >> "$DEBUG_LOG"

# Устанавливаем права на существующий файл (если есть)
if [ -f "$LARAVEL_LOG_FILE" ]; then
    echo "Файл $LARAVEL_LOG_FILE уже существует, устанавливаем права 777" >> "$DEBUG_LOG"
    chmod 777 "$LARAVEL_LOG_FILE"
    echo "Права установлены: $(ls -la "$LARAVEL_LOG_FILE")" >> "$DEBUG_LOG"
fi

# Мониторим события создания и изменения
inotifywait -m -e create -e modify -e attrib --format '%e %f' "$LARAVEL_LOG_DIR" | while read -r EVENT FILENAME; do
    echo "[$(date)] Событие: $EVENT, Файл: $FILENAME" >> "$DEBUG_LOG"

    if [[ "$FILENAME" == "laravel.log" ]]; then
        echo "Обнаружен laravel.log, устанавливаем права 777..." >> "$DEBUG_LOG"
        chmod 777 "$LARAVEL_LOG_FILE"
        echo "Права установлены: $(ls -la "$LARAVEL_LOG_FILE" 2>/dev/null || echo 'файл недоступен')" >> "$DEBUG_LOG"
    fi
done
