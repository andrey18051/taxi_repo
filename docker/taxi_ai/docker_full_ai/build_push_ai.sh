#!/bin/bash

IMAGE_NAME="ghcr.io/andrey18051/taxi_ai:1.0"
CONTAINER_NAME="taxi_ai"

echo "=== Starting deployment of $CONTAINER_NAME ==="

# 1. Проверка доступности Docker
if ! command -v docker &> /dev/null; then
    echo "Error: Docker is not installed or not available."
    exit 1
fi

# 2. Остановка и удаление старого контейнера
echo "Stopping and removing existing container $CONTAINER_NAME..."
docker stop $CONTAINER_NAME > /dev/null 2>&1 || echo "No container to stop."
docker rm $CONTAINER_NAME > /dev/null 2>&1 || echo "No container to remove."

# 3. Удаление старых образов для экономии места
echo "Cleaning up old images for $IMAGE_NAME..."
if [ -n "$(docker images -q $IMAGE_NAME)" ]; then
    docker rmi $(docker images -q $IMAGE_NAME | sort -u) --force > /dev/null 2>&1 || echo "Warning: Failed to remove some old images."
else
    echo "No old images found for $IMAGE_NAME."
fi
docker image prune -f > /dev/null 2>&1
echo "Old images cleaned up."

# 4. Проверка места на диске
echo "Disk space before pulling image:"
df -h

# 5. Скачивание нового образа
echo "Pulling image $IMAGE_NAME from GitHub Container Registry..."
docker pull $IMAGE_NAME || { echo "Error pulling image."; exit 1; }
echo "Image successfully pulled."

# 6. Запуск нового контейнера
echo "Starting new container $CONTAINER_NAME..."
docker run --name $CONTAINER_NAME -d --network host --restart unless-stopped $IMAGE_NAME || { echo "Error starting container."; exit 1; }
echo "Container successfully started."

# 7. Проверка статуса контейнера
echo "Checking container status..."
if docker ps | grep -q $CONTAINER_NAME; then
    echo "Container $CONTAINER_NAME is running."
else
    echo "Container $CONTAINER_NAME is not running."
    docker logs $CONTAINER_NAME
    exit 1
fi

# 8. Проверка места на диске после запуска
echo "Disk space after deployment:"
df -h

echo "=== Deployment finished ==="
