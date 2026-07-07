# Production Verification Checklist

Use this checklist **after** completing [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md) on a staging or production host. It verifies that TeamHUB is operational outside the development machine.

For cutover steps and infrastructure setup, see [TEAMHUB_DEPLOY_RUNBOOK.md](./TEAMHUB_DEPLOY_RUNBOOK.md) and [deploy/examples/](./deploy/examples/).

---

## Profiles

| Profile | When to use | Key env flags |
| ------- | ----------- | ------------- |
| **Standard production** | Live pilot or production | `DEMO_QUICK_LOGIN=false`, `APP_DEBUG=false` |
| **Staging / demo** | Internal demos with seeded data | `DEMO_QUICK_LOGIN=true` optional; `DEMO_HOURLY_RESET` only if intentional |

---

## Automated pre-checks

Run on the deployment host (or CI build agent) before manual verification:

```bash
composer ci:check
composer analyse
php artisan migrate:status
npm run build   # if rebuilding assets on host
```

---

## Infrastructure

### Health endpoint

- [ ] `GET /up` returns HTTP 200 (Laravel health route)
- [ ] `GET /up` responds within acceptable latency (< 1s on warm Octane)

### Redis

- [ ] `redis-cli ping` returns `PONG` from the app host
- [ ] `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` in production `.env`
- [ ] Cache read/write works (e.g. config cached successfully)

### Queue workers

- [ ] Supervisor/systemd shows `teamhub-queue` (or equivalent) **RUNNING**
- [ ] Dispatch a notification-triggering action; job processes without staying in `failed_jobs`
- [ ] `storage/logs/laravel.log` shows queue activity (no persistent connection errors)

### Scheduler

- [ ] Cron entry exists: `* * * * * php artisan schedule:run`
- [ ] `php artisan schedule:list` shows scheduled commands
- [ ] If `DEMO_HOURLY_RESET=true` (staging only): `demo:reset` appears and runs only when not production

### Storage

- [ ] `php artisan storage:link` created `public/storage` → `storage/app/public`
- [ ] `storage/` and `bootstrap/cache/` are writable by the PHP/Octane user
- [ ] Deliverable upload writes to configured disk (`FILESYSTEM_DISK`)

### SSL / reverse proxy

- [ ] Site loads over HTTPS without certificate warnings
- [ ] `APP_URL` matches the public HTTPS URL
- [ ] HTTP redirects to HTTPS (nginx/Caddy config)
- [ ] Security headers present (see [SECURITY.md](./SECURITY.md))

### Mail

- [ ] Test mail sends (password reset or notification) using production `MAIL_*` settings
- [ ] Mail is not stuck in `log` driver unless intentionally configured

### Logs

- [ ] `storage/logs/laravel.log` is writable and receiving entries
- [ ] Log rotation configured (see [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md))

### Cache

- [ ] `php artisan config:cache` succeeds
- [ ] Application serves pages after config/route/view caches are built

---

## Security (production)

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` — trigger a 404; no stack trace exposed
- [ ] `DEMO_QUICK_LOGIN=false` (unless staging demo profile)
- [ ] `DEMO_HOURLY_RESET=false` (unless staging demo profile)
- [ ] AI keys set only in `.env`, not in git
- [ ] Filament `/admin` accessible only to admin users (`isAdmin()` / `canAccessPanel()`)
- [ ] Non-admin users receive 403 on `/admin`
- [ ] Trusted proxy: app behind reverse proxy generates correct HTTPS URLs (see [SECURITY.md](./SECURITY.md))

---

## Application — Standard production mode

`DEMO_QUICK_LOGIN=false`

### Auth

- [ ] `/login` loads and accepts valid credentials
- [ ] `/register` loads and creates a new account (Fortify registration enabled)
- [ ] Invalid login shows validation error without stack trace
- [ ] Logout works and protects authenticated routes

### Core navigation

- [ ] `/dashboard` loads for authenticated user
- [ ] `/student-dashboard` loads assignee summary
- [ ] `/my-tasks` lists assigned tasks
- [ ] `/notifications` loads and mark-read works
- [ ] Language switch (`POST /locale`) persists preference

### Workspaces & projects

- [ ] Workspace page loads (`/workspaces/{workspace}`)
- [ ] Project shell loads with task list
- [ ] Project settings accessible to authorized lead
- [ ] Project switcher appears for multi-project users

### Task lifecycle

- [ ] Create task in project
- [ ] Assign to project member
- [ ] Assignee opens task detail (`/tasks/{task}`)
- [ ] Move `todo` → `in_progress`
- [ ] Submit deliverable (file, link, or notes)
- [ ] Lead reviews; approve → `done` or request changes

### Collaboration

- [ ] Comments appear with author and timestamp
- [ ] Task activity log shows creation, assignment, deliverable, approval events
- [ ] Assignment notification created for assignee
- [ ] Deliverable submission notification for project lead

### AI assistant

- [ ] Assistant panel opens for authorized user
- [ ] List tasks query returns results
- [ ] Confirm-before-write flow completes for a safe mutation
- [ ] Rate limiting present (assistant throttled under load test — optional)

### Admin panel

- [ ] `/admin` loads for admin user
- [ ] Non-admin denied access

---

## Application — Demo / staging mode

`DEMO_QUICK_LOGIN=true` (optional staging only)

- [ ] `/` shows demo role picker instead of redirecting to login
- [ ] Demo login (`POST /demo-login`) enters app as selected role
- [ ] Seeded demo accounts work (`admin@teamhub.test`, workspace/project leads)
- [ ] **Do not enable on public production** without explicit approval

---

## Post-verification

- [ ] Record verification date, version/tag, and operator name
- [ ] Confirm backups configured per [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md)
- [ ] Hand off to [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md) for day-2 monitoring

## Related documents

| Document | Purpose |
| -------- | ------- |
| [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md) | Pre-deploy cutover |
| [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md) | Day-2 operations |
| [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md) | Backup and restore |
| [VALIDATION_READINESS_CHECKLIST.md](./VALIDATION_READINESS_CHECKLIST.md) | **Superseded** — historical reference only |
