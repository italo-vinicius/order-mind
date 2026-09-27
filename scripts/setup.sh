#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

command -v docker >/dev/null || { echo 'Instale Docker com Compose antes de continuar.' >&2; exit 1; }
docker compose version >/dev/null

if [ ! -f .env ]; then
  cp .env.example .env
  printf '\nLOCAL_UID=%s\nLOCAL_GID=%s\n' "$(id -u)" "$(id -g)" >> .env
fi
[ -f backend/.env ] || cp backend/.env.example backend/.env
[ -f frontend/.env ] || cp frontend/.env.example frontend/.env

docker compose build backend
docker compose run --rm --no-deps backend composer install --no-interaction --prefer-dist
docker compose run --rm --no-deps frontend npm ci

if grep -Eq '^APP_KEY=$' backend/.env; then
  docker compose run --rm --no-deps backend php artisan key:generate --no-interaction
fi

docker compose up -d --wait database
docker compose run --rm backend php artisan migrate --no-interaction
docker compose up -d --wait backend queue frontend

printf '\nAmbiente pronto. Consulte as portas em docker compose ps.\n'
