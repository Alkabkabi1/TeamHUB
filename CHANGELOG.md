# Changelog

All notable changes to TeamHUB are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0-rc1] - 2026-07-08

First public release candidate.

### Added

- Open-source contribution files (`CONTRIBUTING.md`, `SECURITY.md`, `CODE_OF_CONDUCT.md`)
- PHPStan / Larastan static analysis (level 5 with baseline)
- `RELEASE_CHECKLIST.md` for production deployments
- GitHub Dependabot, issue template, and pull request template
- Phase 6 ops docs: production verification, operations runbook, backup/recovery, deploy examples
- Phase 7 policy tests (`WorkspacePolicyTest`, `ProjectPolicyTest`)
- Phase 7 RTL layout tests (`TaskRtlLayoutTest`) and Octane smoke tests (`OctaneSmokeTest`)
- Phase 7 closure and checklist documentation

### Changed

- Updated Laravel Framework and transitive dependencies for security advisories
- CI workflows: Composer caching, static analysis job, stricter security audits
- Deployment and README documentation aligned with v1.0.0-rc1 release engineering
- Refreshed `TEAMHUB_USER_GUIDE.md` for Workspace → Project → Task vocabulary
- Task UI RTL/mobile hardening (logical spacing, full-width mobile actions, `dir="ltr"` datetimes)
- Appearance mode persists across visits (light/dark)

### Highlights

- **Workspace → Project → Task** domain model across backend, API contracts, and UI
- Task deliverables with review workflow (`Todo` → `In Progress` → `Review` → `Done`)
- Arabic-first bilingual UI (RTL) with English support
- Inertia.js v3 + Svelte 5 frontend
- Laravel Fortify authentication with optional demo quick-login for local/staging
- Filament v4 admin panel
- AI assistant with confirm-before-write task tools
- Laravel Octane + RoadRunner deployment path
- 339 automated Pest tests

### Supported environments

| Component | Version |
| --------- | ------- |
| PHP | 8.4 (minimum `^8.3`) |
| Node.js | 22 |
| Database | SQLite (local), MySQL/PostgreSQL (production) |
| Cache / queue / sessions | Redis recommended for production |
| Runtime | Laravel Octane + RoadRunner on Linux VPS |

### Known limitations

- Historical database migration filenames retain legacy `clubs` / `committees` table names; runtime uses canonical vocabulary. See [docs/DOMAIN_MODEL.md](./docs/DOMAIN_MODEL.md).
- `fakerphp/faker` remains in production Composer dependencies because seeders run after `composer install --no-dev` in staging/demo profiles.
- Svelte SSR-related npm advisories (moderate) are deferred; production serves a client-rendered Inertia SPA.

### Upgrade notes

This is the first release candidate. There are no upgrade steps from a prior TeamHUB version. Teams migrating from the legacy Ruwad codebase should treat this as a new product baseline.

[Unreleased]: https://github.com/Alkabkabi1/TeamHUB/compare/v1.0.0-rc1...HEAD
[1.0.0-rc1]: https://github.com/Alkabkabi1/TeamHUB/releases/tag/v1.0.0-rc1
