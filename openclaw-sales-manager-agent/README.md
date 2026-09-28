export OPENCLAW_CONFIG_PATH=~/Desktop/working/github/microservice-monorepo-example/openclaw-sales-manager-agent/config/openclaw.json
export OPENCLAW_STATE_DIR="$HOME/.openclaw-sales-manager/state"
export OPENCLAW_PAIRED_DEVICES_FILE="$HOME/.openclaw/devices/paired.json"
# export OPENCLAW_GATEWAY_TOKEN=…  # optional local override; Docker uses openclaw-sales-manager-agent/.env
openclaw gateway start

docker compose up -d --build openclaw-sales-manager-agent

# Host Chrome Dev for OpenClaw browser attach mode (CDP on :18800)
/Applications/Google\ Chrome\ Dev.app/Contents/MacOS/Google\ Chrome\ Dev --remote-debugging-port=18800 --user-data-dir="$HOME/openclaw-sales-manager-agent" --profile-directory="openclaw-sales-manager-agent"