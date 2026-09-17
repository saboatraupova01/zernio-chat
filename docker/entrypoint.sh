#!/bin/sh

set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    env -u APP_KEY php artisan key:generate --force
fi

exec "$@"
