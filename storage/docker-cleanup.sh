#!/usr/bin/env bash
set -euo pipefail

LOG="/var/log/docker-cleanup.log"
EMAIL="taxi.easy.ua.sup@gmail.com"
SUBJECT="Docker Cleanup Report $(date '+%Y-%m-%d %H:%M')"

# Temp HTML
TEMP_HTML="/tmp/docker-report-$(date +%s).html"

# === BEFORE ===
BEFORE=$(docker system df -v 2>/dev/null || echo "No data")

# === CLEANUP ===
docker system prune -a --volumes -f
if command -v docker buildx >/dev/null 2>&1; then
    docker builder prune -a -f
fi

# === AFTER ===
AFTER=$(docker system df -v 2>/dev/null || echo "No data")

# === GENERATE HTML ===
cat > "$TEMP_HTML" << EOF
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Cleanup Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f4; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; }
        .summary { text-align: center; font-weight: bold; padding: 10px; background: #e8f5e8; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; font-size: 0.8em; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Docker Cleanup Report</h1>
        <p class="summary">Сервер: $(hostname)<br>Время: $(date '+%Y-%m-%d %H:%M:%S')</p>

        <h2>До очистки</h2>
        <pre>$(echo "$BEFORE" | sed 's/&/\&amp;/g; s/</\&lt;/g; s/>/\&gt;/g')</pre>

        <h2>После очистки</h2>
        <pre>$(echo "$AFTER" | sed 's/&/\&amp;/g; s/</\&lt;/g; s/>/\&gt;/g')</pre>

        <div class="footer">
            <p>Автоматическая очистка | Отчёт создан: $(date '+%Y-%m-%d %H:%M:%S')</p>
        </div>
    </div>
</body>
</html>
EOF

# === ОТПРАВКА С MIME-TYPE text/html ===
sendmail "$EMAIL" << EOF
From: root@$(hostname)
To: $EMAIL
Subject: $SUBJECT
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8

$(cat "$TEMP_HTML")
EOF

# === ОЧИСТКА ===
rm -f "$TEMP_HTML"

echo "HTML-отчёт отправлен как веб-страница!" | tee -a "$LOG"
