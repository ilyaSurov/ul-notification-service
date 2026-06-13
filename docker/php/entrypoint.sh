#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
fi

echo "Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done

echo "Waiting for Redis..."
until php -r "\$r = new Redis(); \$r->connect(getenv('REDIS_HOST'), (int) getenv('REDIS_PORT')); echo \$r->ping();" 2>/dev/null; do
    sleep 2
done

php artisan migrate --force
php artisan l5-swagger:generate || true

exec "$@"
