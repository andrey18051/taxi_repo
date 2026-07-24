# Восстановление Kafka и taxi_ai (prod)

Сервер: `91.219.60.148` (SSH alias `taxi-prod`), хост `dedbybeR.netx.com.ua`.

Документ для отката, если контейнеры остановили ради экономии RAM (даунгрейд VPS / освобождение памяти).  
**Основной заказ/цена PAS через HTTP + Centrifugo без Kafka и taxi_ai работают.**

Ожидаемая экономия при остановке: ~3.2 GB RAM  
(`taxi_ai` ~1.6 GB + `kafka-fixed` ~1.0 GB + `kafka-rest` ~0.5 GB + `root-zookeeper-1` ~0.1 GB).

---

## 1. Безопасная остановка (перед отключением)

```bash
ssh taxi-prod

# Kafka-стек
docker stop kafka-rest kafka-fixed root-zookeeper-1

# AI
docker stop taxi_ai
```

Проверка памяти:

```bash
free -h
docker stats --no-stream
```

Контейнеры можно оставить остановленными (`docker stop`) — так проще вернуть (`docker start`).  
Удалять (`docker rm`) только если нужно освободить место под образы; команды восстановления ниже — полный recreate.

---

## 2. Восстановление Kafka (актуальные имена на prod)

Порядок обязателен: **Zookeeper → Kafka → REST Proxy**.

Источник истины по командам: `docker/kafka/helper.txt` в этом репозитории.  
На prod сейчас имена: `root-zookeeper-1`, `kafka-fixed`, `kafka-rest` (не `zookeeper` / `kafka` из старого `deploy_kafka.sh`).

### Если контейнеры только остановлены

```bash
docker start root-zookeeper-1
sleep 5
docker start kafka-fixed
sleep 10
docker start kafka-rest
```

### Полный recreate (как в helper.txt)

```bash
docker stop root-zookeeper-1 && docker rm root-zookeeper-1
docker run -d --restart unless-stopped --name root-zookeeper-1 --network host \
  -e ZOOKEEPER_CLIENT_PORT=2181 \
  -e ZOOKEEPER_TICK_TIME=2000 \
  confluentinc/cp-zookeeper:7.3.0

docker stop kafka-fixed && docker rm kafka-fixed
docker run -d --restart unless-stopped --name kafka-fixed --network host \
  -e KAFKA_BROKER_ID=1 \
  -e KAFKA_ZOOKEEPER_CONNECT=127.0.0.1:2181 \
  -e KAFKA_LISTENERS=PLAINTEXT://0.0.0.0:9092 \
  -e KAFKA_ADVERTISED_LISTENERS=PLAINTEXT://127.0.0.1:9092 \
  -e KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR=1 \
  confluentinc/cp-kafka:7.3.0

docker stop kafka-rest && docker rm kafka-rest
docker run -d --restart unless-stopped --name kafka-rest --network host \
  -e KAFKA_REST_HOST_NAME=localhost \
  -e KAFKA_REST_LISTENERS=http://0.0.0.0:8082 \
  -e KAFKA_REST_BOOTSTRAP_SERVERS=127.0.0.1:9092 \
  -e KAFKA_REST_CONSUMER_ENABLE=true \
  confluentinc/cp-kafka-rest:7.6.0
```

### Проверка Kafka

```bash
docker ps --filter name=zookeeper --filter name=kafka
curl -s http://127.0.0.1:8082/topics
# ожидаются топики: cost-topic, cost-topic-my-api (могут создаться при первой отправке)

# опционально consumer из Laravel
docker exec taxi_work php artisan kafka:consume --timeout=10
# или taxi_test — смотря где крутится consumer
```

Laravel ходит в REST Proxy: `KAFKA_REST_HOST` (по умолчанию `http://127.0.0.1:8082`) — см. `app/Services/KafkaService.php`.

Топики: `cost-topic`, `cost-topic-my-api`, `test-topic`.  
PAS шлёт дубль расчёта цены в `/kafka/sendCostMessageMyApi` (не основной путь UI).

---

## 3. Восстановление taxi_ai

Образ на prod (на момент записи): `ghcr.io/andrey18051/taxi_ai:1.0`  
Порт: **8001** (host publish). Laravel: `TaxiAiController` → `http://172.17.0.1:8001`.

### Если контейнер только остановлен

```bash
docker start taxi_ai
docker ps --filter name=taxi_ai
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8001/docs || \
  curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8001/
```

### Полный recreate

Скрипт в репо: `docker/taxi_ai/docker_full_ai/build_push_ai.sh` (pull + run).  
На сервере также может быть `/root/build_push_ai.sh`.

Минимальный запуск (как на prod — bridge + publish 8001):

```bash
docker stop taxi_ai 2>/dev/null; docker rm taxi_ai 2>/dev/null

docker pull ghcr.io/andrey18051/taxi_ai:1.0

docker run -d --name taxi_ai --restart unless-stopped \
  -p 8001:8001 \
  ghcr.io/andrey18051/taxi_ai:1.0
```

Если скрипт на сервере использует `--network host` — это тоже ок; главное, чтобы с хоста и из Docker bridge был доступен `:8001`.

Нужен логин в GHCR (`docker login ghcr.io -u andrey18051`), если образа нет локально.

### Проверка taxi_ai

```bash
docker logs --tail 50 taxi_ai
curl -s http://127.0.0.1:8001/docs | head
# из контейнера Laravel:
curl -s -o /dev/null -w "%{http_code}\n" http://172.17.0.1:8001/docs
```

---

## 4. Что не трогать при восстановлении

- `taxi_work`, `taxi_test`, `office`, redis, mantis, md-access — не связаны с этим откатом.
- Не запускать старый `/root/deploy_kafka.sh` с именами `zookeeper`/`kafka`/`kafka-rest-proxy`, если уже есть `kafka-fixed` / `root-zookeeper-1` — будут конфликты портов 2181/9092/8082.

---

## 5. Связанные файлы в репозитории

| Путь | Назначение |
|------|------------|
| `docker/kafka/helper.txt` | Команды recreate Kafka-стека (prod-имена) |
| `docker/kafka/deploy_kafka.sh` | Старый вариант имён контейнеров |
| `docker/kafka/check_kafka.sh` | Проверка топиков |
| `docker/taxi_ai/docker_full_ai/build_push_ai.sh` | Pull/run `taxi_ai` |
| `app/Services/KafkaService.php` | REST Proxy клиент |
| `app/Http/Controllers/KafkaController.php` | HTTP API `/kafka/*` |
| `app/Console/Commands/KafkaConsumeCommand.php` | `php artisan kafka:consume` |
| `app/Http/Controllers/TaxiAiController.php` | Клиент AI на `:8001` |

---

## 6. Чеклист после восстановления

1. `docker ps` — все три Kafka + `taxi_ai` в `Up`
2. `curl http://127.0.0.1:8082/topics` — ответ JSON
3. `curl` / health на `:8001`
4. `free -h` — убедиться, что RAM и swap в норме (на NVMe-3 с 6 GB оба стека снова могут не влезть)
