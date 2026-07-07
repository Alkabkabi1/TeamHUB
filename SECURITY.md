# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 1.0.x (release candidate) | Yes |
| Earlier development snapshots | No |

## Reporting a vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Email the maintainers with:

- A description of the issue
- Steps to reproduce
- Impact assessment (if known)
- Suggested fix (optional)

We aim to acknowledge reports within **5 business days** and will coordinate disclosure after a fix is available.

## Production hardening checklist

When deploying TeamHUB to production, verify:

| Setting | Production value |
| ------- | ---------------- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DEMO_QUICK_LOGIN` | `false` |
| `DEMO_HOURLY_RESET` | `false` (staging only if ever enabled) |
| `APP_URL` | Your HTTPS site URL |
| AI API keys | Set via environment only; never commit |

Additional guidance: [RELEASE_CHECKLIST.md](./RELEASE_CHECKLIST.md), [PRODUCTION_VERIFICATION_CHECKLIST.md](./PRODUCTION_VERIFICATION_CHECKLIST.md), and [TEAMHUB_DEPLOY_RUNBOOK.md](./TEAMHUB_DEPLOY_RUNBOOK.md).

## Trusted proxies

TeamHUB configures Laravel to trust all proxies:

```php
$middleware->trustProxies(at: '*');
```

This is set in `bootstrap/app.php` so HTTPS scheme, host, and client IP are detected correctly when the application runs behind nginx, Caddy, Traefik, or Coolify.

**Deployment expectations:**

- The reverse proxy must send `X-Forwarded-Proto`, `X-Forwarded-For`, and `Host` headers correctly.
- Terminate TLS at the proxy; Octane listens on HTTP locally (e.g. `127.0.0.1:8000`).
- Restrict which hosts can reach Octane directly (firewall Octane port to localhost only).
- For stricter proxy trust, replacing `*` with specific proxy IPs is a **runtime change** — defer to a future phase and document in your infrastructure instead.

See [deploy/examples/nginx-teamhub.conf](./deploy/examples/nginx-teamhub.conf) for forwarded header examples.

## Production file permissions (least privilege)

| Path | Owner | Permissions |
| ---- | ----- | ----------- |
| Application root | deploy user | readable |
| `storage/`, `bootstrap/cache/` | `www-data` (or PHP user) | writable (775 or 770) |
| `.env` | `www-data` | `600` — not world-readable |
| `public/` | `www-data` | readable |

Queue and Octane processes should run as a dedicated non-root user with write access only to `storage/` and `bootstrap/cache/`.

## Security headers

Configure at the reverse proxy or web server (nginx, Caddy, etc.):

- `Strict-Transport-Security` (HSTS) when serving HTTPS
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN` or `DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` as appropriate for your deployment

Laravel provides CSRF protection, signed URLs for email verification, and rate limiting on sensitive routes (e.g. the AI assistant).

## Dependency audits

Run periodically:

```bash
composer audit --locked --no-dev
npm audit --audit-level=high --omit=dev
```

Report upstream advisories that affect this project through the private channel above if they expose a TeamHUB-specific risk.
