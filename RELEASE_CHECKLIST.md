# TeamHUB Release Checklist — v1.0.0-rc1

Use this checklist when deploying TeamHUB to staging or production. Official profile: **Ubuntu LTS**, **PHP 8.4**, **Laravel Octane + RoadRunner**, **Redis**, **PostgreSQL 15+** (preferred) or **MySQL 8+**.

**After cutover:** complete [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md).  
**Day-2 ops:** [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md).  
**Backups:** [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md).  
**Example configs:** [deploy/examples/](./deploy/examples/) and [deploy/deploy.sh](./deploy/deploy.sh).

## Pre-deploy

- [ ] Target version: `v1.0.0-rc1` (or newer tag)
- [ ] PHP **8.4** with required extensions (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` or `imagick` for image handling)
- [ ] Node.js **22** (build machine only)
- [ ] Composer 2.x
- [ ] Redis available for cache, sessions, and queues (production)
- [ ] Database provisioned (**PostgreSQL 15+** preferred, or MySQL 8+; SQLite for local/demo only)
- [ ] HTTPS certificate and reverse proxy configured
- [ ] Mail provider credentials ready

## Environment variables

Copy `.env.example` and set at minimum:

| Variable | Production |
| -------- | ---------- |
| `APP_NAME` | `TeamHUB` |
| `APP_ENV` | `production` |
| `APP_KEY` | Generated (`php artisan key:generate`) |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-domain.example` |
| `DB_*` | Managed database credentials |
| `SESSION_DRIVER` | `redis` |
| `CACHE_STORE` | `redis` |
| `QUEUE_CONNECTION` | `redis` |
| `FILESYSTEM_DISK` | `local` or S3-compatible object storage |
| `MAIL_*` | Production SMTP / API credentials |
| `REDIS_*` | Redis host, port, password |
| `DEMO_QUICK_LOGIN` | `false` |
| `DEMO_HOURLY_RESET` | `false` |
| `GEMINI_API_KEY` / `DEEPSEEK_API_KEY` | Set if AI assistant enabled |

See [TEAMHUB_DEPLOY_RUNBOOK.md](./TEAMHUB_DEPLOY_RUNBOOK.md) for full context.

## Build and install

```bash
git clone https://github.com/Alkabkabi1/TeamHUB.git
cd TeamHUB
git checkout v1.0.0-rc1   # when tagged

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

## Database and storage

```bash
php artisan migrate --force
php artisan storage:link
```

Run seeders **only** on staging/demo environments:

```bash
php artisan db:seed --force
```

## Production optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache
php artisan filament:cache-components
```

## Octane + RoadRunner

Install the RoadRunner binary (if not present):

```bash
php artisan octane:install --server=roadrunner
```

Start Octane (use a process manager such as systemd or Supervisor):

```bash
php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000
```

Place nginx (documented default) or Caddy in front of Octane for TLS termination and static asset serving from `public/`. See [deploy/examples/nginx-teamhub.conf](./deploy/examples/nginx-teamhub.conf).

Example Supervisor/systemd units: [deploy/examples/](./deploy/examples/).

## Queue worker

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

Run under Supervisor with `autorestart=true`.

## Scheduler

Add to crontab for the deploy user:

```cron
* * * * * cd /path/to/teamhub && php artisan schedule:run >> /dev/null 2>&1
```

## Health verification

- [ ] `GET /up` returns HTTP 200 (Laravel health endpoint)
- [ ] `GET /` loads without 5xx errors
- [ ] Login and language switch work
- [ ] Workspace and project pages load for authorized users
- [ ] Task create → deliverable submit → review → approve flow works
- [ ] Notifications are delivered (mail + queue configured)
- [ ] File uploads persist (storage disk correct)
- [ ] Filament admin (`/admin`) accessible only to admin users
- [ ] `APP_DEBUG=false` — no stack traces on error pages

## Security verification

- [ ] `DEMO_QUICK_LOGIN=false`
- [ ] `DEMO_HOURLY_RESET=false`
- [ ] No API keys in git history or `.env` committed
- [ ] Reverse proxy sets security headers (see [SECURITY.md](./SECURITY.md))
- [ ] Rate limiting active on assistant routes

## Rollback checklist

See [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md) for full restore procedures.

1. Stop Octane / queue workers
2. Restore previous application release directory or git checkout
3. Restore database backup if migrations ran
4. Restore `.env` from secure backup
5. Run `php artisan config:cache` on rolled-back release
6. Restart Octane and queue workers
7. Re-run smoke checks

## Post-release

- [ ] Monitor application and queue logs for 24–48 hours
- [ ] Confirm backup jobs for database and uploaded deliverables
- [ ] Open [GitHub Release](https://github.com/Alkabkabi1/TeamHUB/releases) notes from `CHANGELOG.md` when publishing

## Known limitations

See [CHANGELOG.md](./CHANGELOG.md#100-rc1---2026-07-07) and [PHASE_7_POLISH_NOTES.md](./PHASE_7_POLISH_NOTES.md).
