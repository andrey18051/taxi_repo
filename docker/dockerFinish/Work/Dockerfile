# Базовый образ - Ubuntu 22.04
FROM ghcr.io/andrey18051/taxi_laravel_8.83.29_work:base

ENV DEBIAN_FRONTEND=noninteractive
RUN install_packages nginx supervisor
RUN mkdir -p /var/log/nginx /run/nginx /etc/ssl/certs/nginx /var/log/supervisor

COPY docker/nginx_work.conf /etc/nginx/nginx.conf
COPY docker/certs/nginx/m-easy-order-taxi-site.crt /etc/ssl/certs/nginx/m-easy-order-taxi-site.crt
COPY docker/certs/nginx/m-easy-order-taxi-site.key /etc/ssl/certs/nginx/m-easy-order-taxi-site.key

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod -R 777 /etc/ssl/certs/nginx && \
    chown -R www-data:www-data /var/log/nginx /run/nginx && \
    chmod -R 777 /usr/share/nginx/html/taxi/storage && \
    chmod -R 777 /usr/share/nginx/html/taxi/bootstrap/cache && \
    chown -R www-data:www-data /usr/share/nginx/html/taxi

EXPOSE 7443
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]


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
COPY ./.editorconfig /usr/share/nginx/html/taxi/
COPY ./.env /usr/share/nginx/html/taxi/
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

#RUN  update-alternatives --set php /usr/bin/php7.3

# Копируем конфигурации и службы
RUN cp /usr/share/nginx/html/taxi/docker/supervisord.conf /etc/supervisor/supervisord.conf && \
    cp /usr/share/nginx/html/taxi/docker/nginx_work.conf /etc/nginx/nginx.conf && \
    cp -r /usr/share/nginx/html/taxi/docker/certs/nginx /etc/ssl/certs/nginx && \
    cp /usr/share/nginx/html/taxi/docker/laravel-worker.service /etc/systemd/system/laravel-worker.service && \
    cp /usr/share/nginx/html/taxi/docker/watch_log.service /etc/systemd/system/watch_log.service && \
    cp /usr/share/nginx/html/taxi/docker/watch_log.sh /usr/share/nginx/html/laravel_logs/watch_log.sh

RUN cd /usr/share/nginx/html/taxi/ && composer clear-cache
RUN cd /usr/share/nginx/html/taxi/ && rm -rf vendor composer.lock
RUN cd /usr/share/nginx/html/taxi/ && composer install --no-dev --optimize-autoloader
RUN mkdir -p /var/www/html/laravel_logs
RUN chmod 777 /var/www/html/laravel_logs

# Настраиваем Cron
RUN echo "*/15 * * * * cd /usr/share/nginx/html/taxi && php artisan daily-task:run" >> /etc/cron.d/laravel-cron && \
    echo "0 22 * * * cd /usr/share/nginx/html/taxi && php artisan driver-balance-report-task:run" >> /etc/cron.d/laravel-cron && \
    echo "0 22 * * * cd /usr/share/nginx/html/taxi && php artisan logs:send" >> /etc/cron.d/laravel-cron && \
    echo "0 21 * * * cd /usr/share/nginx/html/taxi && php artisan clean-task:run" >> /etc/cron.d/laravel-cron && \
    chmod 0644 /etc/cron.d/laravel-cron && \
    crontab /etc/cron.d/laravel-cron

# Устанавливаем права доступа ко всем файлам проекта
RUN chmod -R 777 /usr/share/nginx/html/taxi/

