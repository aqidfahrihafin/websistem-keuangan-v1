#!/usr/bin/env bash
set -u

APP_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
PHP_BIN="${PHP_BIN:-$(command -v php)}"
LOG_FILE="$APP_DIR/storage/logs/cron-queue.log"

cd "$APP_DIR" || exit 1
"$PHP_BIN" artisan queue:work \
    --stop-when-empty \
    --max-time=45 \
    --sleep=1 \
    --tries=3 \
    --no-interaction >> "$LOG_FILE" 2>&1
