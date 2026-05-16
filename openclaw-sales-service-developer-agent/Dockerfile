# OpenClaw Sales Service Developer Agent — Dockerfile
# Dedicated coding agent for the sales-service codebase.

FROM node:24-bookworm

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

ENV OPENCLAW_CONFIG_PATH=/home/node/.openclaw/openclaw.json
ENV OPENCLAW_STATE_DIR=/home/node/.openclaw/state

RUN mkdir -p /home/node/.openclaw/state \
             /home/node/.openclaw/state/agents/main/agent \
             /home/node/.openclaw/agents/sales-service-developer/sessions \
             /home/node/.openclaw/credentials \
             /home/node/.openclaw/workspace \
             /home/node/.openclaw/workspace/sales-service/.cursor

COPY openclaw-sales-service-developer-agent/config/openclaw.json /home/node/.openclaw/openclaw.json
COPY openclaw-sales-service-developer-agent/workspace/ /home/node/.openclaw/workspace/
COPY openclaw-sales-service-developer-agent/paperclip-claimed-api-key.json /home/node/.openclaw/workspace/paperclip-claimed-api-key.json
COPY .cursor/mcp.json /home/node/.openclaw/workspace/sales-service/.cursor/mcp.json
COPY .cursor/rules/ /home/node/.openclaw/workspace/sales-service/.cursor/rules/
COPY openclaw-sales-service-developer-agent/docker-entrypoint.sh /home/node/docker-entrypoint.sh

RUN chown -R node:node /home/node

USER node

ENV HOME=/home/node
ENV NODE_ENV=production

EXPOSE 18799 18810

CMD ["sh", "/home/node/docker-entrypoint.sh"]
