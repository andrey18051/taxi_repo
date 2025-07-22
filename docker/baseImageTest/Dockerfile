# Базовый образ
FROM bitnami/php-fpm:7.3-prod

# Установка переменной окружения для избежания интерактивных запросов
ENV DEBIAN_FRONTEND=noninteractive

RUN mkdir -p /run/php

# Update apt sources to use Debian archive and install packages
RUN echo "deb http://archive.debian.org/debian buster main" > /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security buster/updates main" >> /etc/apt/sources.list && \
    apt-get update --allow-unauthenticated && \
    apt-get install -y --allow-unauthenticated \
        autoconf \
        gcc \
        g++ \
        make \
        libtool \
        php-dev \
        zlib1g-dev \
        wget \
        nginx \
        supervisor \
        cron \
        inotify-tools && \
    apt-get clean


# Настраиваем PHP-FPM и Nginx на порт 9002 вместо 9000
RUN sed -i 's|listen = 9000|listen = 127.0.0.1:9002|'  /opt/bitnami/php/etc/php-fpm.d/www.conf && \
    echo "upstream php { server 127.0.0.1:9002; }" > /etc/nginx/conf.d/upstream.conf

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

# Установка Composer с проверкой
RUN wget -O composer-setup.php https://getcomposer.org/installer && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm composer-setup.php && \
    composer --version || { echo "Composer installation failed"; exit 1; }

# Увеличиваем лимит памяти для PHP
RUN echo "memory_limit = 1G" >> /opt/bitnami/php/etc/php.ini

# Создаем рабочую директорию для Laravel
WORKDIR /usr/share/nginx/html/taxi

# Устанавливаем Laravel v8.83.29 (последняя версия скелета для ветки 8.x)
RUN composer create-project --prefer-dist laravel/laravel . "8.6.12" --no-interaction || { echo "Ctaxioser create-project failed with details above"; exit 1; }

# Обновляем laravel/framework до версии 8.83.27
# RUN composer require laravel/framework:"8.83.27" --no-interaction --prefer-dist || { echo "Failed to update laravel/framework to 8.83.27"; exit 1; }

# Устанавливаем права доступа (без указания пользователя bitnami)
RUN chmod -R 755 /usr/share/nginx/html/taxi/storage && \
    chmod -R 755 /usr/share/nginx/html/taxi/bootstrap/cache

