# TeamHUB Deploy Runbook

## Goal

Deploy TeamHUB on a **Linux VPS** using **Laravel Octane + RoadRunner**, **Redis**, and a managed database (**PostgreSQL 15+** preferred). For a step-by-step cutover list, see [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md). For post-deploy QA, see [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md).

## Required environment variables

Minimum application settings:

- `APP_NAME=TeamHUB`
- `APP_ENV` — use `production` in live environments
- `APP_KEY`
- `APP_DEBUG` — **`false` in production**
- `APP_URL` — HTTPS URL in production
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

Production security flags:

- `DEMO_QUICK_LOGIN=false`
- `DEMO_HOURLY_RESET=false` (staging only if demo reset is required)

Optional but commonly needed:

- mail transport credentials
- Redis credentials (`REDIS_*`)
- object storage credentials when moving off local disk
- `GEMINI_API_KEY` / `DEEPSEEK_API_KEY` for the AI assistant

## Local-first defaults

The repository defaults optimize for development without external infrastructure:

- SQLite database file at `database/database.sqlite`
- `FILESYSTEM_DISK=local`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `MAIL_MAILER=log`

This is suitable for local validation and demos, not production.

## Recommended production profile

| Component | Recommendation |
| --------- | -------------- |
| Runtime | Laravel Octane + RoadRunner |
| Database | PostgreSQL 15+ (preferred) or MySQL 8+ |
| Cache / sessions / queues | Redis |
| Mail | SMTP or transactional API |
| Files | Local disk or S3-compatible storage for deliverables |
| TLS | Reverse proxy (nginx default; Caddy acceptable) in front of Octane |

## Deploy checklist

1. Install PHP 8.4, Composer, Node.js 22, and required PHP extensions.
2. Configure `.env` for the target environment (see [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md)).
3. Run `composer install --no-dev` and `npm ci && npm run build` (or use [deploy/deploy.sh](./deploy/deploy.sh)).
4. Run `php artisan migrate --force`.
5. Run `php artisan storage:link` if public file access is required.
6. Cache config, routes, views, and Filament components (see release checklist).
7. Configure and start Octane, queue workers, and scheduler using [deploy/examples/](./deploy/examples/).
8. Run `composer ci:check` on a build/staging host before final cutover when feasible.

## Post-deploy smoke checks

- `GET /up` returns 200
- Can a user sign in and switch language?
- Can a manager open a workspace and a project?
- Can a manager create and assign a task?
- Can an assignee submit a deliverable?
- Can a reviewer approve or request changes?
- Are notifications generated for the core task lifecycle?
- Does the assistant list tasks and complete a confirmed write flow?

## Operational notes

- Deliverable uploads depend on the configured filesystem disk.
- Review/assignment notifications depend on mail and queue configuration.
- The domain model uses **Workspace → Project → Task** everywhere in runtime code; historical migration filenames may reference legacy table names.

## Security headers

Configure at the reverse proxy — see [SECURITY.md](./SECURITY.md).

## Related documentation

- [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md) — production cutover and rollback
- [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md) — post-deploy QA
- [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md) — day-2 operations
- [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md) — backup and restore
- [deploy/examples/](./deploy/examples/) — nginx, Supervisor, systemd examples
- [TEAMHUB_USER_GUIDE.md](./TEAMHUB_USER_GUIDE.md) — end-user documentation
