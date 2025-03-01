# Базовый образ
FROM bitnami/php-fpm:7.3-prod

# Установка переменной окружения для избежания интерактивных запросов
ENV DEBIAN_FRONTEND=noninteractive

RUN mkdir -p /run/php

RUN install_packages autoconf gcc g++ make libtool php-dev zlib1g-dev wget nginx supervisor cron

# Настраиваем PHP-FPM и Nginx на порт 9001 вместо 9000
RUN sed -i 's|listen = 9000|listen = 127.0.0.1:9001|'  /opt/bitnami/php/etc/php-fpm.d/www.conf && \
    echo "upstream php { server 127.0.0.1:9001; }" > /etc/nginx/conf.d/upstream.conf

# Удаляем старые конфигурации Nginx и создаём директории
RUN rm -f /etc/nginx/conf.d/default.conf && \
    mkdir -p /usr/share/nginx/html/taxi \
    /usr/share/nginx/html/sessions \
    /usr/share/nginx/html/laravel_logs \
    /usr/share/nginx/html/cache \
    /etc/ssl/certs/nginx

# Устанавливаем права доступа
RUN chmod -R 755 /usr/share/nginx/html/sessions \
    /usr/share/nginx/html/cache \
    /etc/ssl/certs/nginx && \
    chmod -R 777 /usr/share/nginx/html/laravel_logs

RUN /opt/bitnami/php/sbin/php-fpm --daemonize


RUN mkdir -p /tmp/pear && chmod -R 777 /tmp/pear

RUN pecl install grpc-1.62.0
RUN echo "extension=grpc.so" > /opt/bitnami/php/etc/php.ini


