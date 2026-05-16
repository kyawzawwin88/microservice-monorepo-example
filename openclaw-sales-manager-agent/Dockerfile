# OpenClaw Sales Manager Agent — Dockerfile
# Docs: https://docs.openclaw.ai/install/docker
#
# Runs the OpenClaw gateway with headless Chromium (Playwright) for E2E tests.
# Use `network_mode: host` (see docker-compose) so the browser can reach services
# on the host (e.g. UI on :3000, APIs on :800x). CDP :18800 is optional debugging.
#
# Paths match local dev: OPENCLAW_CONFIG_PATH points at the JSON config;
# OPENCLAW_STATE_DIR holds sessions, device pairing, logs (mount a volume here).

FROM node:24-bookworm

# Install system dependencies for headless Chromium (Playwright)
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    libnss3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libpango-1.0-0 \
    libcairo2 \
    libasound2 \
    libatspi2.0-0 \
    libwayland-client0 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxext6 \
    libxi6 \
    libxtst6 \
    libglib2.0-0 \
    libdbus-1-3 \
    fonts-liberation \
    fonts-noto-color-emoji \
    fonts-noto-cjk \
    curl \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN npm install -g openclaw@latest

ENV PLAYWRIGHT_BROWSERS_PATH=/home/node/.cache/ms-playwright
RUN npx playwright install chromium \
    && npx playwright install-deps chromium || true

WORKDIR /home/node

# OpenClaw: config file + mutable state (sessions, devices, logs)
ENV OPENCLAW_CONFIG_PATH=/home/node/.openclaw/openclaw.json
ENV OPENCLAW_STATE_DIR=/home/node/.openclaw/state
RUN mkdir -p /home/node/.openclaw/state \
             /home/node/.openclaw/state/agents/main/agent \
             /home/node/.openclaw/agents/sales-manager/sessions \
             /home/node/.openclaw/credentials

COPY config/openclaw.json /home/node/.openclaw/openclaw.json
COPY workspace/ /home/node/.openclaw/workspace/
COPY docker-entrypoint.sh /home/node/docker-entrypoint.sh

RUN chown -R node:node /home/node

USER node

ENV HOME=/home/node
ENV NODE_ENV=production

EXPOSE 18789 18800

# Gateway auth token: set OPENCLAW_GATEWAY_TOKEN at runtime (e.g. compose env_file: .env).
# OpenClaw resolves env before config; keep gateway.auth.token empty in the baked config.
CMD ["sh", "/home/node/docker-entrypoint.sh"]
