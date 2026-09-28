# OpenClaw UI Developer Agent

Coding agent for **`ui/`** (React, Vite, Tailwind).

## Setup

```bash
cd openclaw-ui-developer-agent
cp .env.example .env
```

Fill secrets in `.env` (not committed), including `PAPERCLIP_API_KEY` only when needed at runtime.

## Ports (isolated from other OpenClaw agents)

- Gateway + Control UI: **18819**
- Host Chrome Dev CDP (attach mode): **18830**

## Host Chrome Dev (optional)

```bash
/Applications/Google\ Chrome\ Dev.app/Contents/MacOS/Google\ Chrome\ Dev --remote-debugging-port=18830 --user-data-dir="$HOME/openclaw-ui-developer-agent"
```

## Docker Compose

From repo root:

```bash
docker compose up -d --build openclaw-ui-developer-agent
```

The service bind-mounts `./ui` to `/home/node/.openclaw/workspace/ui` so edits apply to your repo.
