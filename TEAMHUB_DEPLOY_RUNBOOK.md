# TeamHUB Deploy Runbook

## Goal

This runbook covers the current TeamHUB application state after the local-first MVP build and Phase 7 polish pass. Use it to prepare Phase 8 deployment and pilot rollout work.

## Required environment variables

Minimum application settings:

- `APP_NAME=TeamHUB`
- `APP_ENV`
- `APP_KEY`
- `APP_URL`
- `APP_LOCALE`
- `FILESYSTEM_DISK`
- `SESSION_DRIVER`
- `CACHE_STORE`
- `QUEUE_CONNECTION`
- `MAIL_MAILER`

Database:

- `DB_CONNECTION`
- `DB_DATABASE`
- `DB_HOST` / `DB_PORT` / `DB_USERNAME` / `DB_PASSWORD` when not using SQLite

Optional but commonly needed for production:

- mail transport credentials
- queue backend credentials
- object storage credentials if moving off local disk

## Local-first defaults

The repo is currently optimized for development without external infrastructure:

- SQLite database file at `database/database.sqlite`
- `FILESYSTEM_DISK=local`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `MAIL_MAILER=log`

This is suitable for local validation, demos, and rapid iteration, but not for a real pilot environment.

## Recommended production expectations

Before pilot rollout, plan to replace the local-first defaults with:

- managed database
- persistent queue backend
- real mail provider
- persistent file/object storage for deliverables
- HTTPS and correct `APP_URL`
- process management for Laravel + Vite-built assets

## Deploy checklist

1. Install PHP, Composer, Node.js, and the required extensions on the target environment.
2. Configure `.env` for the target environment.
3. Run `composer install --no-dev` and `npm ci`.
4. Build frontend assets with `npm run build`.
5. Run database migrations.
6. Ensure the storage link exists if public file access is required.
7. Verify queue and mail configuration.
8. Run `composer ci:check` before the final cutover when feasible.

## Post-deploy smoke checks

- Can a user sign in and switch language?
- Does persisted light/dark appearance survive navigation and refresh?
- Can a manager open a workspace and a project?
- Can a manager create and assign a task?
- Can an assignee submit a deliverable?
- Can a reviewer approve or request changes?
- Are notifications generated for the core task lifecycle?
- Does the assistant list tasks and complete a confirmed write flow?

## Operational notes

- Deliverable uploads depend on the configured filesystem disk.
- Review/assignment notifications depend on your mail and queue setup.
- The app still uses existing `Club` and `Committee` models internally, even though the UI presents them as workspaces and projects.

## Phase 8 handoff

Phase 8 should focus on:

- deployment architecture and environment hardening
- pilot user onboarding
- monitoring, logging, and backup decisions
- production queue/mail/file storage readiness
- final go-live checklist and rollback plan
