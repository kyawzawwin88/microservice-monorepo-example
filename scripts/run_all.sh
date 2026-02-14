#!/usr/bin/env bash
# Run all microservices via docker-compose
# Usage: bazelisk run //:run_all
set -euo pipefail

# Resolve workspace root — Bazel executes from the runfiles tree,
# so we navigate to BUILD_WORKSPACE_DIRECTORY which Bazel sets for `bazel run`.
cd "${BUILD_WORKSPACE_DIRECTORY:-.}"

echo "============================================"
echo " Microservice Monorepo — Starting ALL services"
echo "============================================"
echo ""
echo "Services:"
echo "  UI        → http://localhost:3000"
echo "  Sales     → http://localhost:8001  (Swagger: http://localhost:8001/docs/api)"
echo "  Invoice   → http://localhost:8002  (Swagger: http://localhost:8002/docs/api)"
echo "  Payment   → http://localhost:8003  (Swagger: http://localhost:8003/docs/api)"
echo "  Inventory → http://localhost:8004  (Swagger: http://localhost:8004/docs/api)"
echo ""

docker compose up --build "$@"
