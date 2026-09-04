#!/bin/sh
set -e

# create temp folder
mkdir -p tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/phpunit logs

# check dependencies
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] installing composer dependencies..."
    composer install --no-interaction --no-progress
fi

# prepare the database when actually starting the web server
case "$*" in
    *"server"*)
        echo "[entrypoint] running migrations..."
        php bin/cake.php migrations migrate --no-lock

        echo "[entrypoint] seeding sample tenants/rules (idempotent)..."
        php bin/cake.php seeds run --quiet || echo "[entrypoint] seed failed (continuing)"
        ;;
esac

exec "$@"
