#!/bin/sh
set -eu

AUTH_FILE="${OPENCLAW_STATE_DIR}/agents/main/agent/auth-profiles.json"
AUTH_DIR="$(dirname "${AUTH_FILE}")"
PAPERCLIP_FILE="/home/node/.openclaw/workspace/paperclip-claimed-api-key.json"

mkdir -p "${AUTH_DIR}"

if [ -n "${OPENCLAW_AUTH_PROFILE_ACCESS:-}" ] && [ -n "${OPENCLAW_AUTH_PROFILE_REFRESH:-}" ] && [ -n "${OPENCLAW_AUTH_PROFILE_ACCOUNT_ID:-}" ]; then
  cat > "${AUTH_FILE}" <<EOF
{
  "version": 1,
  "profiles": {
    "openai-codex:default": {
      "type": "oauth",
      "provider": "openai-codex",
      "access": "${OPENCLAW_AUTH_PROFILE_ACCESS}",
      "refresh": "${OPENCLAW_AUTH_PROFILE_REFRESH}",
      "expires": ${OPENCLAW_AUTH_PROFILE_EXPIRES:-0},
      "accountId": "${OPENCLAW_AUTH_PROFILE_ACCOUNT_ID}"
    }
  }
}
EOF
fi

if [ -n "${PAPERCLIP_API_KEY:-}" ]; then
  mkdir -p "$(dirname "${PAPERCLIP_FILE}")"
  cat > "${PAPERCLIP_FILE}" <<EOF
{
  "PAPERCLIP_API_KEY": "${PAPERCLIP_API_KEY}",
  "name": "openclaw-ui-developer-agent"
}
EOF
fi

exec openclaw gateway run --bind lan --port 18819 --allow-unconfigured
