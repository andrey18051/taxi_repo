# Используем базовый образ с PHP-FPM 7.3 (bitnami) или ваш базовый
FROM ghcr.io/andrey18051/taxi_laravel_8.83.29_test:base

ENV DEBIAN_FRONTEND=noninteractive

# Обновляем источники и устанавливаем необходимые пакеты
RUN sed -i 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list \
 && sed -i 's|http://security.debian.org|http://archive.debian.org/debian-security|g' /etc/apt/sources.list \
 && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until \
 && apt-get update \
 && apt-get install -y \
    lsof \
    net-tools \
    inotify-tools \
    nmap \
    sysvinit-utils \
    sudo \
    systemd \
    netcat-traditional \
    dos2unix \
    supervisor \
    cron \
    nginx \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Создаем необходимые директории
RUN mkdir -p /var/log/nginx /run/nginx /etc/ssl/certs/nginx /var/log/supervisor /usr/share/nginx/html/taxi/laravel_logs

# Копируем конфигурационные файлы nginx и supervisord (адаптированные под Fly)
COPY docker/nginx_fly.conf /etc/nginx/nginx.conf
COPY docker/supervisord_fly.conf /etc/supervisor/supervisord.conf

# Копируем сертификаты (при необходимости, если есть)
COPY docker/certs/nginx/m-easy-order-taxi-site.crt /etc/ssl/certs/nginx/
COPY docker/certs/nginx/m-easy-order-taxi-site.key /etc/ssl/certs/nginx/
COPY docker/certs/nginx/test-taxi.kyiv.ua.crt /etc/ssl/certs/nginx/
COPY docker/certs/nginx/test-taxi.kyiv.ua.key /etc/ssl/certs/nginx/

# Копируем проект в контейнер
COPY ./app /usr/share/nginx/html/taxi/app
COPY ./bootstrap /usr/share/nginx/html/taxi/bootstrap
COPY ./config /usr/share/nginx/html/taxi/config
COPY ./database /usr/share/nginx/html/taxi/database
COPY ./docker /usr/share/nginx/html/taxi/docker
COPY ./public /usr/share/nginx/html/taxi/public
COPY ./resources /usr/share/nginx/html/taxi/resources
COPY ./routes /usr/share/nginx/html/taxi/routes
COPY ./storage /usr/share/nginx/html/taxi/storage
COPY ./tests /usr/share/nginx/html/taxi/tests
COPY ./tmp /usr/share/nginx/html/taxi/tmp
COPY ./vendor /usr/share/nginx/html/taxi/vendor

# Заменяем WorkCommand.php на оригинальный из Laravel 8.83.29
COPY docker/patches/WorkCommand.php /usr/share/nginx/html/taxi/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php

# Копируем дополнительные файлы проекта
COPY ./.editorconfig /usr/share/nginx/html/taxi/
COPY ../../../app/env/test/.env /usr/share/nginx/html/taxi/
COPY ./.styleci.yml /usr/share/nginx/html/taxi/
COPY ./anceta.docx /usr/share/nginx/html/taxi/
COPY ./artisan /usr/share/nginx/html/taxi/
COPY ./composer.json /usr/share/nginx/html/taxi/
COPY ./composer.lock /usr/share/nginx/html/taxi/
COPY ./google48a0497e525302d5.html /usr/share/nginx/html/taxi/
COPY ./mariadb_repo_setup /usr/share/nginx/html/taxi/
COPY ./package.json /usr/share/nginx/html/taxi/
COPY ./package-lock.json /usr/share/nginx/html/taxi/
COPY ./php /usr/share/nginx/html/taxi/
COPY ./phpunit.xml /usr/share/nginx/html/taxi/
COPY ./questionnaire.docx /usr/share/nginx/html/taxi/
COPY ./README.md /usr/share/nginx/html/taxi/
COPY ./server.php /usr/share/nginx/html/taxi/
COPY ./webpack.mix.js /usr/share/nginx/html/taxi/

# Копируем системные службы и скрипты
RUN cp /usr/share/nginx/html/taxi/docker/watch_log.service /etc/systemd/system/watch_log.service && \
    cp /usr/share/nginx/html/taxi/docker/watch_log.sh /usr/share/nginx/html/taxi/laravel_logs/watch_log.sh

# Копируем и делаем исполняемыми необходимые скрипты
COPY docker/wait-for-redis-then-restart-task.sh /usr/share/nginx/html/taxi/wait-for-redis-then-restart-task.sh
RUN chmod +x /usr/share/nginx/html/taxi/wait-for-redis-then-restart-task.sh \
 && dos2unix /usr/share/nginx/html/taxi/laravel_logs/watch_log.sh

# Устанавливаем права и владельцев для каталогов и файлов проекта
RUN chmod -R 777 /etc/ssl/certs/nginx && \
    chown -R www-data:www-data /var/log/nginx /run/nginx && \
    chmod -R 777 /usr/share/nginx/html/taxi/storage && \
    chmod -R 777 /usr/share/nginx/html/taxi/bootstrap/cache && \
    chown -R www-data:www-data /usr/share/nginx/html/taxi && \
    chmod -R 777 /usr/share/nginx/html/taxi

# Открываем порт 8080 (Fly.io требует именно этот порт)
EXPOSE 8080

# Настраиваем Cron задания
RUN crontab -u root -l > /tmp/cronfile 2>/dev/null || true && \
    echo "* * * * * cd /usr/share/nginx/html/taxi && /opt/bitnami/php/bin/php artisan schedule:run >> /dev/null 2>&1" >> /tmp/cronfile && \
    echo "1 21 * * * cd /usr/share/nginx/html/taxi && /opt/bitnami/php/bin/php artisan logs:send >> /var/log/cron_tasks.log 2>&1" >> /tmp/cronfile && \
    crontab -u root /tmp/cronfile && rm /tmp/cronfile

# Запускаем supervisord для управления nginx, php-fpm, cron и другими службами
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
