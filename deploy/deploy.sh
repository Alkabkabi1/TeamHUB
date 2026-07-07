#!/usr/bin/env bash
# TeamHUB deployment helper (example)
#
# Usage: ./deploy/deploy.sh
# Run from the application root on the target server AFTER configuring .env.
#
# This script does NOT provision infrastructure, configure DNS, or manage TLS.
# It performs a standard Laravel production deploy sequence.
#
# Prerequisites: PHP 8.4, Composer, Node 22, Redis, database, Octane/RoadRunner installed.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

echo "==> TeamHUB deploy in ${APP_DIR}"

if [[ ! -f .env ]]; then
    echo "ERROR: .env not found. Copy .env.example and configure before deploying."
    exit 1
fi

echo "==> Installing PHP dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Building frontend assets"
npm ci
npm run build

echo "==> Running migrations"
php artisan migrate --force

echo "==> Storage link"
php artisan storage:link 2>/dev/null || true

echo "==> Caching configuration"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache
php artisan filament:cache-components

echo "==> Signaling queue workers to restart"
php artisan queue:restart

echo "==> Reloading Octane (if running)"
php artisan octane:reload 2>/dev/null || echo "    (Octane not running — start manually)"

echo "==> Deploy complete. Run PRODUCTION_VERIFICATION_CHECKLIST.md next."
