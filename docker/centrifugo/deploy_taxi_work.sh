#!/bin/bash
# Deploy taxi_work with Centrifugo env (always).
# Usage: IMAGE_TAG=4a4867d ./deploy_taxi_work.sh
#    or: ./deploy_taxi_work.sh 4a4867d
set -euo pipefail

IMAGE_TAG="${1:-${IMAGE_TAG:-latest}}"
IMAGE="ghcr.io/andrey18051/taxi_work:${IMAGE_TAG}"
ENV_FILE="/opt/centrifugo/laravel.env"

test -f "$ENV_FILE" || { echo "FATAL: missing $ENV_FILE"; exit 1; }

docker pull "$IMAGE"
docker stop taxi_work || true
docker rm taxi_work || true

mkdir -p /opt/laravel_logs/taxi_work
chmod 777 /opt/laravel_logs/taxi_work
touch /opt/laravel_logs/taxi_work/laravel.log
chmod 666 /opt/laravel_logs/taxi_work/laravel.log

docker run --name taxi_work -d \
  --network host \
  --restart unless-stopped \
  --user 0:0 \
  --env-file "$ENV_FILE" \
  -v /opt/secrets/firebase/prod:/run/secrets/firebase:ro \
  -v /opt/laravel_logs/taxi_work:/usr/share/nginx/html/laravel_logs \
  "$IMAGE"

echo "taxi_work deployed: $IMAGE (with $ENV_FILE)"
