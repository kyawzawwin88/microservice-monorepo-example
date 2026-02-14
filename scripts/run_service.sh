#!/usr/bin/env bash
# Run a single microservice via docker-compose
# Usage: bazelisk run //sales-service:run
set -euo pipefail

cd "${BUILD_WORKSPACE_DIRECTORY:-.}"

SERVICE_NAME="${1:?Service name required}"
WORKER_NAME="${2:-}"

echo "============================================"
echo " Starting: ${SERVICE_NAME}"
echo "============================================"

if [ -n "$WORKER_NAME" ]; then
    docker compose up --build "${SERVICE_NAME}" "${WORKER_NAME}"
else
    docker compose up --build "${SERVICE_NAME}"
fi
