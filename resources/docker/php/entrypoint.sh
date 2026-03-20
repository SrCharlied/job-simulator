#!/bin/sh
set -eu

if [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."
  until pg_isready \
    -h "${DB_HOST}" \
    -p "${DB_PORT:-5432}" \
    -U "${DB_USER:-postgres}" \
    -d "${DB_NAME:-postgres}" >/dev/null 2>&1; do
    sleep 2
  done
fi

if [ ! -d "/app/public" ]; then
  echo "No /app/public directory found yet. Container will stay alive for development."
  exec tail -f /dev/null
fi

exec "$@"
