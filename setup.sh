#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

say() {
    printf '\n==> %s\n' "$1"
}

fail() {
    printf 'Setup failed: %s\n' "$1" >&2
    exit 1
}

env_value() {
    local key="$1"
    local value

    value="$(awk -F= -v key="$key" '$1 == key { sub(/^[^=]*=/, ""); print; exit }' .env 2>/dev/null || true)"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    printf '%s' "$value"
}

say "Preparing environment file"
if [[ ! -f .env ]]; then
    [[ -f .env.example ]] || fail ".env.example is missing. Run this script from the project root."
    cp .env.example .env
    printf 'Created .env from .env.example.\n'
else
    printf 'Keeping existing .env settings.\n'
fi

DB_CONNECTION="$(env_value DB_CONNECTION)"
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
if [[ "$DB_CONNECTION" == "sqlite" ]]; then
    DB_DATABASE="$(env_value DB_DATABASE)"
    DB_DATABASE="${DB_DATABASE:-database/database.sqlite}"
    if [[ "$DB_DATABASE" != ":memory:" ]]; then
        if [[ "$DB_DATABASE" != /* ]]; then
            DB_DATABASE="$PROJECT_ROOT/$DB_DATABASE"
        fi
        mkdir -p "$(dirname "$DB_DATABASE")"
        touch "$DB_DATABASE"
    fi
fi

RUNTIME="native"
PHP_VERSION_ID=0
if command -v php >/dev/null 2>&1; then
    PHP_VERSION_ID="$(php -r 'echo PHP_VERSION_ID;' 2>/dev/null || printf '0')"
fi

REQUIRED_EXTENSION=""
case "$DB_CONNECTION" in
    sqlite) REQUIRED_EXTENSION="pdo_sqlite" ;;
    mysql|mariadb) REQUIRED_EXTENSION="pdo_mysql" ;;
    pgsql) REQUIRED_EXTENSION="pdo_pgsql" ;;
    sqlsrv) REQUIRED_EXTENSION="pdo_sqlsrv" ;;
    *) fail "Unsupported DB_CONNECTION '$DB_CONNECTION'. Use sqlite, mysql, mariadb, pgsql, or sqlsrv." ;;
esac

NATIVE_READY=false
if command -v php >/dev/null 2>&1 && command -v composer >/dev/null 2>&1 && (( PHP_VERSION_ID >= 80300 )); then
    if php -m 2>/dev/null | grep -Eiq "^${REQUIRED_EXTENSION}$"; then
        NATIVE_READY=true
    fi
fi

if [[ "$NATIVE_READY" != true ]]; then
    if [[ "$DB_CONNECTION" == "sqlite" ]] && command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
        RUNTIME="docker"
        printf 'Using Docker because local PHP 8.3+, Composer, or pdo_sqlite is unavailable.\n'
    else
        if [[ "$DB_CONNECTION" != "sqlite" ]]; then
            fail "Local PHP 8.3+, Composer, and $REQUIRED_EXTENSION are required for DB_CONNECTION=$DB_CONNECTION. For Docker setup, use SQLite in .env."
        fi
        if ! command -v docker >/dev/null 2>&1; then
            fail "Install PHP 8.3+ with pdo_sqlite and Composer, or install Docker and rerun this script."
        fi
        fail "Docker is installed but unavailable. Start Docker and rerun this script, or install PHP 8.3+ with pdo_sqlite and Composer."
    fi
fi

run_php() {
    if [[ "$RUNTIME" == "native" ]]; then
        php "$@"
    else
        docker run --rm --user "$(id -u):$(id -g)" -v "$PROJECT_ROOT:/app" -w /app composer:2 php "$@"
    fi
}

run_composer() {
    if [[ "$RUNTIME" == "native" ]]; then
        composer "$@"
    else
        docker run --rm -e COMPOSER_HOME=/tmp/composer -e GIT_CONFIG_COUNT=1 -e GIT_CONFIG_KEY_0=safe.directory -e GIT_CONFIG_VALUE_0=/app -v "$PROJECT_ROOT:/app" -w /app composer:2 composer "$@"
    fi
}

say "Installing PHP dependencies"
run_composer install --no-interaction --prefer-dist

say "Preparing Laravel application"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
if ! grep -Eq '^APP_KEY=.+$' .env; then
    run_php artisan key:generate --force
fi

say "Applying database migrations and seeding demo accounts"
run_php artisan migrate --force
run_php artisan db:seed --force

printf '\nSetup complete.\n'
printf 'Run the app:       php artisan serve\n'
printf 'Run the scheduler: php artisan schedule:work  (second terminal; required for 15-minute webhook timeouts)\n'
printf 'Open:              http://127.0.0.1:8000\n'
ADMIN_EMAIL="$(env_value DEMO_ADMIN_EMAIL)"
ADMIN_PASSWORD="$(env_value DEMO_ADMIN_PASSWORD)"
MERCHANT_EMAIL="$(env_value DEMO_MERCHANT_EMAIL)"
MERCHANT_PASSWORD="$(env_value DEMO_MERCHANT_PASSWORD)"
printf 'Admin:             %s / password: %s\n' "${ADMIN_EMAIL:-admin@example.com}" "${ADMIN_PASSWORD:-password}"
printf 'Merchant:          %s / password: %s\n' "${MERCHANT_EMAIL:-merchant@example.com}" "${MERCHANT_PASSWORD:-password}"
