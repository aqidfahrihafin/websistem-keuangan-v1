#!/usr/bin/env bash
set -u

APP_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
PHP_BIN="${PHP_BIN:-$(command -v php)}"
LOG_FILE="$APP_DIR/storage/logs/cron-schedule.log"

cd "$APP_DIR" || exit 1
"$PHP_BIN" artisan schedule:run --no-interaction >> "$LOG_FILE" 2>&1
