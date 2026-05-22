#!/usr/bin/env sh
# Runs on Railway release (has mysql.railway.internal). Bootstraps empty/broken DBs.
set -eu

cd "$(dirname "$0")/.."
export APP_ENV=prod
export APP_DEBUG=0

user_table_exists() {
    php bin/console doctrine:query:sql \
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user' LIMIT 1" \
        2>/dev/null | grep -q '1'
}

if user_table_exists; then
    echo '[railway-release] user table found — running migrations.'
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    echo '[railway-release] Syncing schema to entities (fixes missing columns e.g. is_email_verified)...'
    php bin/console doctrine:schema:update --force --complete --no-interaction

    echo '[railway-release] Ensuring default admin user (marga@test.com)...'
    php bin/console app:create-admin --no-interaction

    echo '[railway-release] ORM smoke test...'
    php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \`user\`" --no-interaction

    exit 0
fi

echo '[railway-release] No user table — database is empty or half-migrated; rebuilding schema.'
php bin/console doctrine:database:drop --force --full-database --no-interaction
php bin/console doctrine:schema:create --no-interaction
php bin/console doctrine:migrations:version --add --all --no-interaction
php bin/console app:create-admin --no-interaction
echo '[railway-release] Done. Default admin: marga@test.com / m111'
