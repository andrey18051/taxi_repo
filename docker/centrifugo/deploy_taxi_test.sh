#!/bin/bash
# Deploy taxi_test with Centrifugo env (always).
# Usage: IMAGE_TAG=4a4867d ./deploy_taxi_test.sh
#    or: ./deploy_taxi_test.sh 4a4867d
set -euo pipefail

IMAGE_TAG="${1:-${IMAGE_TAG:-latest}}"
IMAGE="ghcr.io/andrey18051/taxi_test:${IMAGE_TAG}"
ENV_FILE="/opt/centrifugo/laravel.env"

test -f "$ENV_FILE" || { echo "FATAL: missing $ENV_FILE"; exit 1; }

docker pull "$IMAGE"
docker stop taxi_test || true
docker rm taxi_test || true

mkdir -p /opt/laravel_logs/taxi_test
chmod 777 /opt/laravel_logs/taxi_test
touch /opt/laravel_logs/taxi_test/laravel.log
chmod 666 /opt/laravel_logs/taxi_test/laravel.log

docker run --name taxi_test -d \
  --network host \
  --restart unless-stopped \
  --user 0:0 \
  --env-file "$ENV_FILE" \
  -v /opt/secrets/firebase/test:/run/secrets/firebase:ro \
  -v /opt/laravel_logs/taxi_test:/usr/share/nginx/html/laravel_logs \
  "$IMAGE"

echo "taxi_test deployed: $IMAGE (with $ENV_FILE)"
