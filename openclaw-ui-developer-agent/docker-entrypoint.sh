#!/bin/sh
set -eu

AUTH_FILE="${OPENCLAW_STATE_DIR}/agents/main/agent/auth-profiles.json"
AUTH_DIR="$(dirname "${AUTH_FILE}")"
PAPERCLIP_FILE="/home/node/.openclaw/workspace/paperclip-claimed-api-key.json"
PAPERCLIP_NAME="openclaw-ui-developer-agent"

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
  export PAPERCLIP_FILE PAPERCLIP_NAME PAPERCLIP_API_KEY
  (umask 077 && node -e 'const fs = require("fs"); fs.writeFileSync(process.env.PAPERCLIP_FILE, JSON.stringify({ PAPERCLIP_API_KEY: process.env.PAPERCLIP_API_KEY, name: process.env.PAPERCLIP_NAME }, null, 2) + "\n");')
else
  rm -f "${PAPERCLIP_FILE}"
fi

exec openclaw gateway run --bind lan --port 18819 --allow-unconfigured
