#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

docker compose up -d --wait database-test

docker compose run --rm --no-deps \
  -e APP_ENV=testing \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=database-test \
  -e DB_PORT=5432 \
  -e DB_DATABASE=ordermind_test \
  -e DB_USERNAME=ordermind_test \
  -e DB_PASSWORD=local-test-only \
  backend php artisan migrate:fresh --force --no-interaction

docker compose run --rm --no-deps backend vendor/bin/pest
