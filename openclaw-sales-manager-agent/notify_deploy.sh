#!/usr/bin/env bash
# ============================================
# OpenClaw Sales Manager Agent — Deployment Notification Script
# ============================================
# Send a deployment notification to the OpenClaw agent via its hooks endpoint.
# This triggers the agent to run E2E tests on the deployed environment.
#
# Usage:
#   ./notify_deploy.sh local "Fixed order creation bug in sales service"
#   ./notify_deploy.sh production "Release v2.1.0 — new inventory management"
#
# The agent will:
# 1. Receive the webhook
# 2. Analyze the deployment message
# 3. Run relevant E2E browser tests
# 4. Report results to Discord

set -euo pipefail

ENVIRONMENT="${1:-local}"
MESSAGE="${2:-Manual deployment trigger}"
HOOKS_TOKEN="${OPENCLAW_HOOKS_TOKEN:-change-me-to-a-webhook-secret}"
GATEWAY_URL="${OPENCLAW_GATEWAY_URL:-http://localhost:18789}"

echo "🚀 Notifying OpenClaw Sales Manager Agent..."
echo "   Environment: ${ENVIRONMENT}"
echo "   Message: ${MESSAGE}"
echo ""

curl -s -X POST "${GATEWAY_URL}/hooks/deploy" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${HOOKS_TOKEN}" \
  -d "{
    \"environment\": \"${ENVIRONMENT}\",
    \"message\": \"${MESSAGE}\",
    \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\",
    \"source\": \"manual\"
  }" | python3 -m json.tool 2>/dev/null || echo "(response received)"

echo ""
echo "✅ Notification sent. The agent will run E2E tests and report to Discord."
