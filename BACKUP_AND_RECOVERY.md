# Backup & Recovery Guide

Operational backup and recovery procedures for TeamHUB v1.0.0-rc1+. This document describes **what to back up and how to restore** — it does not provision backup infrastructure.

For deployment cutover, see [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md). For rollback steps, see the **Rollback** section below and [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md).

---

## What to back up

| Asset | Contents | Priority |
| ----- | -------- | -------- |
| **Database** | Workspaces, projects, tasks, users, notifications | Critical |
| **Storage** | `storage/app/` — deliverable uploads, media, private files | Critical |
| **Environment** | `.env` (secure, encrypted store — not in git) | Critical |
| **Application code** | Git tag/commit deployed | High (recoverable from GitHub) |
| **Built assets** | `public/build/` | Medium (rebuild with `npm run build`) |
| **Redis** | Cache/sessions/queues | Low (ephemeral; rebuild on restore) |

SQLite (local/demo only): copy `database/database.sqlite`.

PostgreSQL / MySQL (production): use native dump tools.

---

## Database backup

### PostgreSQL 15+ (preferred)

```bash
pg_dump -h "$DB_HOST" -U "$DB_USERNAME" -Fc "$DB_DATABASE" \
  > "teamhub-$(date +%Y%m%d-%H%M%S).dump"
```

Restore:

```bash
pg_restore -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" --clean --if-exists teamhub-YYYYMMDD.dump
```

### MySQL 8+

```bash
mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p "$DB_DATABASE" \
  > "teamhub-$(date +%Y%m%d-%H%M%S).sql"
```

Restore:

```bash
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p "$DB_DATABASE" < teamhub-YYYYMMDD.sql
```

### Schedule

- **Production:** daily automated dumps, retain 14–30 days minimum
- **Before migrations:** manual dump immediately before `php artisan migrate --force`
- Store backups off-server (object storage, separate VPS, managed backup service)

---

## Storage backup

TeamHUB deliverables and uploads live under `storage/app/` (and public media via `storage/app/public`).

### Tar archive

```bash
tar -czf "teamhub-storage-$(date +%Y%m%d).tar.gz" -C /var/www/teamhub storage/app
```

### Restore

```bash
php artisan down
tar -xzf teamhub-storage-YYYYMMDD.tar.gz -C /var/www/teamhub
chown -R www-data:www-data storage/
php artisan up
```

When using S3-compatible object storage (`FILESYSTEM_DISK=s3`), enable provider versioning and lifecycle policies instead of filesystem tar.

---

## Environment backup

- Store `.env` in a secrets manager (1Password, Vault, host encrypted backup)
- Never commit `.env` to git
- Document which `APP_KEY` was used — **changing `APP_KEY` invalidates encrypted data and sessions**

---

## Failed deployment recovery

If a deployment fails **before** cutover:

1. Stop Octane and queue workers
2. Revert to previous Git checkout or release directory
3. Restore `.env` if it was modified
4. `php artisan optimize:clear && php artisan config:cache`
5. Restart Octane and queue workers
6. Run [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md)

If migrations ran and caused issues:

1. Stop traffic (`php artisan down`)
2. Restore database from pre-migration dump
3. Revert application code to previous release
4. Restart services and verify

**Migration rollback:** TeamHUB does not ship down-migration playbooks for every release. Treat forward-fix or database restore as the primary recovery path for production.

---

## Rollback checklist

1. Enable maintenance mode: `php artisan down`
2. Stop Octane and queue workers
3. Checkout previous tag/commit or swap release symlink
4. Restore database backup if migrations ran on failed release
5. Restore `storage/app` if corrupted during failed release
6. `composer install --no-dev` (matching rolled-back `composer.lock`)
7. `php artisan optimize:clear`
8. Rebuild caches (`config:cache`, `route:cache`, etc.)
9. Restart Octane and queue workers
10. `php artisan up`
11. Verify `/up` and core workflows

---

## Disaster recovery

| Scenario | Recovery approach |
| -------- | ----------------- |
| Database corruption / loss | Restore latest `pg_dump` / `mysqldump` to new or wiped DB |
| Storage loss | Restore `storage/app` tar or object storage version |
| Full server loss | Provision new VPS per [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md); restore DB + storage + `.env` |
| Compromised server | Rotate all secrets (`APP_KEY` only as last resort), redeploy from clean Git tag, restore DB from pre-incident backup |

### Recovery objectives (targets — adjust for your pilot)

| Metric | Suggested target |
| ------ | ---------------- |
| RPO (max data loss) | 24 hours (daily backups) or 1 hour with frequent dumps |
| RTO (time to restore) | 2–4 hours for full VPS rebuild |

---

## Backup verification

Monthly procedure:

1. Restore latest database dump to an isolated database instance
2. Deploy matching application tag against restored DB
3. Confirm login and task list load
4. Spot-check a deliverable file exists in restored storage
5. Log verification date and result in your ops log

---

## Related documentation

- [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md) — rollback section
- [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md) — restart and incident response
- [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md) — post-restore QA
