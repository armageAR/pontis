# Pontis Web Client

React 19 + TypeScript + Vite single-page app for Pontis. See the
[root README](../README.md) for the full project overview and API reference.

## Quick start

```bash
npm install
cp .env.example .env    # optional: only needed to point at a remote API
npm run dev
```

Without `VITE_API_URL`, requests go to the relative `/api` path, which the Vite dev server
proxies to the target configured in `vite.config.ts`.

## Commands

```bash
npm run dev       # dev server with HMR
npm run build     # tsc -b && vite build
npm run lint      # ESLint
npm run preview   # serve the production build
```

## Layout

- `src/api` — typed axios clients (auth, users, workshops, dashboard, sync)
- `src/components` — UI primitives, layouts and the protected-route guard
- `src/context/AuthContext.tsx` — session, current user and role helpers
- `src/pages` — one directory per route, each component with a sibling `.css`

Imports resolve through the `@/` alias for `src/`.

Licensed under the [MIT License](../LICENSE).
