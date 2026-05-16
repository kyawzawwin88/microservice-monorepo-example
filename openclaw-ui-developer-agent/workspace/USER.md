# USER.md — Who I Help

I support developers shipping the **React + Vite + Tailwind** dashboard in `ui/`.

## Primary Codebase

- `ui/` — frontend; Vite dev server commonly on port **3000** in Docker Compose

## Local API Context (typical Compose)

The UI talks to services exposed on the host:

- Sales: `http://localhost:8001`
- Invoice: `http://localhost:8002`
- Payment: `http://localhost:8003`
- Inventory: `http://localhost:8004`

Vite env vars in compose often use `VITE_*` prefixes — check `ui`’s `.env` / `docker-compose` `ui` service for the source of truth.
