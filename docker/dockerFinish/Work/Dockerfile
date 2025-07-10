# Базовый образ - Ubuntu 22.04
FROM ghcr.io/andrey18051/taxi_laravel_8.83.29_work:base

ENV DEBIAN_FRONTEND=noninteractive

# Устанавливаем необходимые пакеты
RUN apt-get update && apt-get install -y \
    lsof \
    net-tools \
    inotify-tools \
    nmap \
    sysvinit-utils \
    sudo \
    systemd \
    && apt-get clean


# Создаём необходимые директории
RUN mkdir -p /var/log/nginx /run/nginx /etc/ssl/certs/nginx /var/log/supervisor

# Копируем конфигурационные файлы
COPY docker/nginx_work.conf /etc/nginx/nginx.conf
COPY docker/certs/nginx/m-easy-order-taxi-site.crt /etc/ssl/certs/nginx/m-easy-order-taxi-site.crt
COPY docker/certs/nginx/m-easy-order-taxi-site.key /etc/ssl/certs/nginx/m-easy-order-taxi-site.key
COPY docker/certs/nginx/test-taxi.kyiv.ua.crt /etc/ssl/certs/nginx/test-taxi.kyiv.ua.crt
COPY docker/certs/nginx/test-taxi.kyiv.ua.key /etc/ssl/certs/nginx/test-taxi.kyiv.ua.key
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Устанавливаем права и владельцев для необходимых файлов и директорий
RUN chmod -R 777 /etc/ssl/certs/nginx && \
    chown -R www-data:www-data /var/log/nginx /run/nginx && \
    chmod -R 777 /usr/share/nginx/html/taxi/storage && \
    chmod -R 777 /usr/share/nginx/html/taxi/bootstrap/cache && \
    chown -R www-data:www-data /usr/share/nginx/html/taxi

# Открываем нужные порты
EXPOSE 7443

# Копируем файлы проекта
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


COPY ./.editorconfig /usr/share/nginx/html/taxi/
COPY ../../../app/env/work/.env /usr/share/nginx/html/taxi/
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

# Копируем конфигурации и службы
RUN cp /usr/share/nginx/html/taxi/docker/supervisord_work.conf /etc/supervisor/supervisord.conf && \
    cp /usr/share/nginx/html/taxi/docker/nginx_work.conf /etc/nginx/nginx.conf && \
    cp -r /usr/share/nginx/html/taxi/docker/certs/nginx /etc/ssl/certs/nginx && \
#    cp /usr/share/nginx/html/taxi/docker/laravel-worker.service /etc/systemd/system/laravel-worker.service && \
    cp /usr/share/nginx/html/taxi/docker/watch_log.service /etc/systemd/system/watch_log.service && \
    cp /usr/share/nginx/html/taxi/docker/watch_log.sh /usr/share/nginx/html/laravel_logs/watch_log.sh

# Устанавливаем зависимости Laravel
#RUN cd /usr/share/nginx/html/taxi/ && composer clear-cache && \
#    rm -rf vendor composer.lock && \
#    composer install --no-dev --optimize-autoloader && \
#    composer require predis/predis

# Создаём директорию для логов и устанавливаем права
RUN mkdir -p /usr/share/nginx/html/laravel_logs && chmod 777 /usr/share/nginx/html/laravel_logs

# Настраиваем Cron
RUN crontab -u root -l > /tmp/cronfile 2>/dev/null || true && \
    echo "*/15 * * * * cd /usr/share/nginx/html/taxi && /opt/bitnami/php/bin/php artisan daily-task:run >> /var/log/cron_tasks.log 2>&1" >> /tmp/cronfile && \
    echo "0 21 * * * cd /usr/share/nginx/html/taxi && /opt/bitnami/php/bin/php artisan driver-balance-report-task:run >> /var/log/cron_tasks.log 2>&1" >> /tmp/cronfile && \
    echo "0 21 * * * cd /usr/share/nginx/html/taxi && /opt/bitnami/php/bin/php artisan logs:send >> /var/log/cron_tasks.log 2>&1" >> /tmp/cronfile && \
    echo "0 20 * * * cd /usr/share/nginx/html/taxi && /opt/bitnami/php/bin/php artisan clean-task:run >> /var/log/cron_tasks.log 2>&1" >> /tmp/cronfile && \
    crontab -u root /tmp/cronfile && \
    rm /tmp/cronfile

# Устанавливаем права доступа ко всем файлам проекта
RUN chmod -R 777 /usr/share/nginx/html/taxi/


# Запускаем supervisord, который управляет всеми процессами (nginx, php-fpm, cron)
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
