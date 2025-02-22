FROM webdevops/php-nginx:latest
RUN  apt-get update -y && apt-get install -y supervisor
RUN  apt install php-json -y
RUN  apt install php-calendar -y
#устанавливаем cron
RUN  apt-get install -y cron

RUN sed -i 's/9000/9001/g' /opt/docker/etc/php/fpm/pool.d/application.conf
RUN sed -i 's/9000/9001/g' /opt/docker/etc/nginx/conf.d/10-php.conf

RUN  rm -f /opt/docker/etc/nginx/vhost.conf
RUN mkdir -p /usr/share/nginx/html/taxi
RUN mkdir -p /usr/share/nginx/html/sessions
RUN mkdir -p /usr/share/nginx/html/laravel_logs
RUN mkdir -p /usr/share/nginx/html/cache

#создаем папку с сертификатами
RUN mkdir -p /etc/ssl/certs/nginx

RUN chmod -R 777 /usr/share/nginx/html/sessions
RUN chmod -R 777 /usr/share/nginx/html/laravel_logs
RUN chmod -R 777 /usr/share/nginx/html/cache

#присваем полный доступ всех папке с сертификатами
RUN chmod -R 777 /etc/ssl/certs/nginx

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
COPY ./.styleci.yml /usr/share/nginx/html/taxi/
COPY ./php /usr/share/nginx/html/taxi/
COPY ./phpunit.xml /usr/share/nginx/html/taxi/
COPY ./questionnaire.docx /usr/share/nginx/html/taxi/
COPY ./README.md /usr/share/nginx/html/taxi/
COPY ./server.php /usr/share/nginx/html/taxi/
COPY ./webpack.mix.js /usr/share/nginx/html/taxi/
#RUN mkdir -p /etc/ssl/certs/nginx/
#RUN cp /usr/share/nginx/html/office/docker/korzhov-office-kharkiv-ua.key /etc/ssl/certs/nginx/korzhov-office-kharkiv-ua.key
#RUN cp /usr/share/nginx/html/office/docker/korzhov-office-kharkiv-ua.crt /etc/ssl/certs/nginx/korzhov-office-kharkiv-ua.crt
RUN cp /usr/share/nginx/html/taxi/docker/supervisord.conf /etc/supervisord.conf
RUN cp /usr/share/nginx/html/taxi/docker/taxi.conf /opt/docker/etc/nginx/vhost.conf

#копируем  SSL сертификаты  в папку с сертификатами
RUN cp -r /usr/share/nginx/html/taxi/docker/certs/nginx /etc/ssl/certs/nginx
#копируем  службу контроля очередей Laravel  в папку со службами
RUN cp /usr/share/nginx/html/taxi/docker/laravel-worker.service /etc/systemd/system/laravel-worker.service
#копируем  службу контроля 777 для фалйа логов  в папку со службами
RUN cp /usr/share/nginx/html/taxi/docker/watch_log.service /etc/systemd/system/watch_log.service
#копируем  задачу  777 для файла логов  в папку со службами

RUN cp /usr/share/nginx/html/taxi/docker/watch_log.sh /usr/share/nginx/html/laravel_logs/watch_log.sh

#RUN cp /usr/share/nginx/html/office/docker/main-local.php /usr/share/nginx/html/office/common/config/main-local.php
#RUN cp /usr/share/nginx/html/office/docker/index_f.php /usr/share/nginx/html/office/frontend/views/site/index.php
#RUN cp /usr/share/nginx/html/office/docker/index_b.php /usr/share/nginx/html/office/backend/views/site/index.php
#RUN cp /usr/share/nginx/html/office/docker/main.php  /usr/share/nginx/html/office/backend/config/main.php
RUN chmod -R 777 /usr/share/nginx/html/taxi/

RUN echo "*/15 * * * * cd /usr/share/nginx/html/taxi && php artisan daily-task:run" >> /etc/cron.d/laravel-cron && \
    echo "0 22 * * * cd /usr/share/nginx/html/taxi && php artisan driver-balance-report-task:run" >> /etc/cron.d/laravel-cron && \
    echo "0 22 * * * cd /usr/share/nginx/html/taxi && php artisan logs:send" >> /etc/cron.d/laravel-cron && \
    echo "0 21 * * * cd /usr/share/nginx/html/taxi && php artisan clean-task:run" >> /etc/cron.d/laravel-cron && \
    chmod 0644 /etc/cron.d/laravel-cron && \
    crontab /etc/cron.d/laravel-cron


# Запускаем cron и службы вручную
RUN service cron start && service laravel-worker start && service watch_log start

RUN service nginx restart
EXPOSE 7443

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
