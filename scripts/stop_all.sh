#!/usr/bin/env bash
# Stop all running microservices
# Usage: bazelisk run //:stop_all
set -euo pipefail

cd "${BUILD_WORKSPACE_DIRECTORY:-.}"

echo "Stopping all services..."
docker compose down "$@"
echo "All services stopped."
