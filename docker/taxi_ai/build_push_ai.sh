#!/bin/bash

IMAGE_NAME="ghcr.io/andrey18051/taxi_ai:1.0"
CONTAINER_NAME="taxi_ai"

echo "Скачивание образа $IMAGE_NAME с GitHub Container Registry..."
docker pull $IMAGE_NAME || { echo "Ошибка при загрузке образа."; exit 1; }
echo "Образ успешно загружен."

echo "Остановка существующего контейнера $CONTAINER_NAME..."
docker stop $CONTAINER_NAME > /dev/null 2>&1 || true
docker rm $CONTAINER_NAME > /dev/null 2>&1 || true

echo "Запуск нового контейнера $CONTAINER_NAME..."
docker run --name $CONTAINER_NAME -d --network host --restart unless-stopped $IMAGE_NAME || { echo "Ошибка при запуске контейнера."; exit 1; }
echo "Контейнер успешно запущен."

docker ps | grep $CONTAINER_NAME
