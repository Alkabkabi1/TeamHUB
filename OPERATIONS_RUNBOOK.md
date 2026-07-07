# TeamHUB Operations Runbook

Day-2 operations for a deployed TeamHUB instance (v1.0.0-rc1+). For initial cutover, see [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md). For post-deploy QA, see [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md).

**Official profile:** Ubuntu LTS, PHP 8.4, Octane + RoadRunner, Redis, PostgreSQL 15+ (preferred) or MySQL 8+, Nginx, Supervisor/systemd.

---

## Process architecture

| Process | Purpose | Manager |
| ------- | ------- | ------- |
| Octane (RoadRunner) | HTTP application server | systemd or Supervisor |
| `queue:work` | Async jobs (notifications, mail) | Supervisor/systemd |
| `schedule:run` | Cron (every minute) | crontab |
| Nginx / Caddy | TLS termination, static files, reverse proxy | system package |
| Redis | Cache, sessions, queues | system package or managed service |

Example configs: [deploy/examples/](./deploy/examples/).

---

## Log locations

| Log | Path / command |
| --- | -------------- |
| Application | `storage/logs/laravel.log` |
| Octane (if `--log-level` enabled) | stdout → journalctl / Supervisor logs |
| Nginx access | `/var/log/nginx/access.log` (default) |
| Nginx error | `/var/log/nginx/error.log` |
| Queue worker | Supervisor log or `journalctl -u teamhub-queue` |
| Scheduler | Cron mail or redirect `schedule:run` output to a file |

### Tail application logs

```bash
tail -f storage/logs/laravel.log
```

### Log rotation

Configure `logrotate` for `storage/logs/*.log` to prevent disk exhaustion. Example policy: daily rotation, 14-day retention, compress old logs.

---

## Monitoring recommendations

No application monitoring package is bundled. Recommended external checks:

| Check | Method | Alert if |
| ----- | ------ | -------- |
| Uptime | HTTP GET `https://your-domain/up` | Non-200 or timeout |
| Homepage | HTTP GET `https://your-domain/` | 5xx |
| Redis | `redis-cli ping` from app host | Not `PONG` |
| Queue depth | `redis-cli LLEN queues:default` (key may vary) | Sustained growth |
| Disk | `df -h` on app and storage volumes | > 85% used |
| Failed jobs | `php artisan queue:failed` | Any critical failures |

Optional future integrations (out of scope for rc1): Sentry, Datadog, UptimeRobot, Healthchecks.io hitting `/up`.

---

## Restart procedures

### Octane (graceful)

```bash
php artisan octane:reload
```

If reload fails or after code deploy:

```bash
sudo systemctl restart teamhub-octane
# or
sudo supervisorctl restart teamhub-octane
```

### Queue workers

After code or config deploy:

```bash
php artisan queue:restart
sudo supervisorctl restart teamhub-queue
```

### Full application restart (ordered)

1. Enable maintenance mode (optional): `php artisan down`
2. Pull/deploy new release
3. `composer install --no-dev`, `npm run build` if needed
4. `php artisan migrate --force`
5. Rebuild caches (see below)
6. `php artisan octane:reload` or restart Octane service
7. `php artisan queue:restart`
8. `php artisan up`

---

## Cache management

### Build production caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache
php artisan filament:cache-components
```

### Clear caches (troubleshooting)

```bash
php artisan optimize:clear
```

Then rebuild caches before returning to production traffic.

---

## Maintenance mode

```bash
php artisan down --secret="your-bypass-token"
# Deploy or investigate
php artisan up
```

Bypass URL: `https://your-domain/your-bypass-token`

---

## Queue monitoring

### Check failed jobs

```bash
php artisan queue:failed
php artisan queue:retry all   # after fixing root cause
php artisan queue:flush       # discard permanently — use with caution
```

### Verify worker is running

```bash
sudo supervisorctl status teamhub-queue
# or
systemctl status teamhub-queue
```

---

## Scheduler monitoring

```bash
php artisan schedule:list
php artisan schedule:test
```

Confirm cron is active:

```bash
crontab -l -u www-data
```

Expected: `* * * * * cd /var/www/teamhub && php artisan schedule:run >> /dev/null 2>&1`

---

## Disk usage

Monitor:

- `storage/app/` — uploads and deliverables
- `storage/logs/` — application logs
- `storage/framework/` — cache, sessions (if file driver used locally)

Deliverable growth is the primary long-term disk risk when `FILESYSTEM_DISK=local`. Plan object storage or volume expansion for pilots.

---

## Backup verification

See [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md). Monthly:

- [ ] Restore database backup to a scratch instance
- [ ] Verify `storage/app` backup contains recent deliverables
- [ ] Document restore time (RTO) and data loss window (RPO)

---

## Common incidents

| Symptom | Likely cause | Action |
| ------- | ------------ | ------ |
| 502 from Nginx | Octane down | Restart Octane; check `journalctl -u teamhub-octane` |
| Sessions lost | Redis down or misconfigured | Check Redis; verify `SESSION_DRIVER=redis` |
| Notifications not sent | Queue worker stopped | Restart queue; check `failed_jobs` |
| Wrong HTTP/HTTPS URLs | Proxy headers | Verify Nginx `X-Forwarded-*`; see trusted proxies in [SECURITY.md](./SECURITY.md) |
| 500 after deploy | Stale config cache | `php artisan optimize:clear` then rebuild caches |
| Upload failures | Permissions or disk full | `chown` storage; check `df -h` |

---

## Related documentation

- [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md)
- [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md)
- [BACKUP_AND_RECOVERY.md](./BACKUP_AND_RECOVERY.md)
- [SECURITY.md](./SECURITY.md)
