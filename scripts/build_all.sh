#!/usr/bin/env bash
# Build all microservice Docker images without starting them
# Usage: bazelisk run //:build_all
set -euo pipefail

cd "${BUILD_WORKSPACE_DIRECTORY:-.}"

echo "============================================"
echo " Microservice Monorepo — Building ALL images"
echo "============================================"

docker compose build "$@"

echo ""
echo "All images built successfully."
