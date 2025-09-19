#!/bin/bash
set -e

# Список контейнеров Kafka
KAFKA_CONTAINERS=("zookeeper" "kafka" "kafka-rest-proxy")

# Список образов
declare -A KAFKA_IMAGES
KAFKA_IMAGES[zookeeper]="confluentinc/cp-zookeeper:7.3.0"
KAFKA_IMAGES[kafka]="confluentinc/cp-kafka:7.3.0"
KAFKA_IMAGES[kafka-rest-proxy]="confluentinc/cp-kafka-rest:7.3.0"

echo "=== Проверка существующих контейнеров ==="
for c in "${KAFKA_CONTAINERS[@]}"; do
    if [ "$(docker ps -a -q -f name=^/${c}$)" ]; then
        echo "Останавливаем и удаляем контейнер $c..."
        docker stop "$c" >/dev/null 2>&1 || true
        docker rm "$c" >/dev/null 2>&1 || true
    else
        echo "Контейнер $c не существует, будет создан."
    fi
done

echo "=== Запуск Zookeeper ==="
docker run -d --name zookeeper --network host \
    -e ZOOKEEPER_CLIENT_PORT=2181 \
    -e ZOOKEEPER_TICK_TIME=2000 \
    --restart unless-stopped \
    "${KAFKA_IMAGES[zookeeper]}"
echo "Zookeeper запущен."

echo "=== Запуск Kafka ==="
docker run -d --name kafka --network host \
    -e KAFKA_BROKER_ID=1 \
    -e KAFKA_ZOOKEEPER_CONNECT=localhost:2181 \
    -e KAFKA_ADVERTISED_LISTENERS=PLAINTEXT://localhost:9092 \
    -e KAFKA_LISTENER_SECURITY_PROTOCOL_MAP=PLAINTEXT:PLAINTEXT \
    -e KAFKA_INTER_BROKER_LISTENER_NAME=PLAINTEXT \
    -e KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR=1 \
    --restart unless-stopped \
    "${KAFKA_IMAGES[kafka]}"
echo "Kafka запущена."

echo "=== Запуск Kafka REST Proxy ==="
docker run -d --name kafka-rest-proxy --network host \
    -e KAFKA_REST_HOST_NAME=localhost \
    -e KAFKA_REST_BOOTSTRAP_SERVERS=localhost:9092 \
    -e KAFKA_REST_LISTENERS=http://0.0.0.0:8082 \
    --restart unless-stopped \
    "${KAFKA_IMAGES[kafka-rest-proxy]}"
echo "Kafka REST Proxy запущен."

echo "=== Текущие контейнеры Kafka ==="
docker ps --filter "name=zookeeper" --filter "name=kafka" --filter "name=kafka-rest-proxy" \
    --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"
