#!/bin/bash
set -e

# Список контейнеров Kafka
KAFKA_CONTAINERS=("zookeeper" "kafka" "kafka-rest-proxy")

echo "=== Проверка состояния контейнеров Kafka ==="
for c in "${KAFKA_CONTAINERS[@]}"; do
    if docker ps -q -f name=^/${c}$ >/dev/null; then
        STATUS=$(docker inspect -f '{{.State.Status}}' $c)
        echo "$c: $STATUS"
    else
        echo "$c: НЕ ЗАПУЩЕН"
    fi
done

echo
echo "=== Проверка доступности Kafka (создание и чтение тестового топика) ==="
TEST_TOPIC="check-topic-$(date +%s)"
docker exec -i kafka kafka-topics --bootstrap-server localhost:9092 --create --topic $TEST_TOPIC --partitions 1 --replication-factor 1 >/dev/null 2>&1
docker exec -i kafka kafka-topics --bootstrap-server localhost:9092 --list | grep $TEST_TOPIC >/dev/null
if [ $? -eq 0 ]; then
    echo "Kafka: тестовый топик $TEST_TOPIC успешно создан и читается ✅"
else
    echo "Kafka: ошибка при создании или чтении топика ❌"
fi

echo
echo "=== Проверка Kafka REST Proxy ==="
REST_TOPICS=$(curl -s http://localhost:8082/topics)
if [[ $REST_TOPICS == *$TEST_TOPIC* ]]; then
    echo "REST Proxy: топик $TEST_TOPIC доступен через REST ✅"
else
    echo "REST Proxy: ошибка доступа к топику через REST ❌"
fi

echo
echo "=== Состояние завершено ==="
