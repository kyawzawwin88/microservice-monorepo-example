# OpenClaw Sales Service Developer Agent

This agent is dedicated to writing and modifying code in `sales-service/`.

## 1) Prepare environment

```bash
cd openclaw-sales-service-developer-agent
cp .env.example .env
```

Fill in secrets in `.env` (do not commit it).

## 2) Start host Chrome Dev (optional, for browser tool attach mode)

```bash
/Applications/Google\ Chrome\ Dev.app/Contents/MacOS/Google\ Chrome\ Dev --remote-debugging-port=18810 --user-data-dir="$HOME/openclaw-sales-service-developer-agent"
```

## 3) Run with Docker Compose (example service snippet)

Use these key mounts so the agent can edit `sales-service`:

- `./sales-service:/home/node/.openclaw/workspace/sales-service`
- `./openclaw-sales-service-developer-agent/workspace:/home/node/.openclaw/workspace`

The gateway runs on `ws://127.0.0.1:18799`.


docker compose up -d --build openclaw-sales-service-developer-agent