#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/.."

# Railway injects Variables into the shell environment. Symfony's committed .env leaves
# OAuth keys empty; write .env.prod.local so Dotenv picks them up in prod.
: > .env.prod.local

_redirect_uri="${GOOGLE_OAUTH_REDIRECT_URI:-https://teachmelms-production.up.railway.app/connect/google/check}"
_public_url="${APP_PUBLIC_URL:-https://teachmelms-production.up.railway.app}"
{
    printf 'GOOGLE_OAUTH_REDIRECT_URI=%s\n' "$_redirect_uri"
    printf 'APP_PUBLIC_URL=%s\n' "$_public_url"
} >> .env.prod.local

if [ -n "${GOOGLE_OAUTH_CLIENT_ID:-}" ]; then
    {
        printf 'GOOGLE_OAUTH_CLIENT_ID=%s\n' "$GOOGLE_OAUTH_CLIENT_ID"
        printf 'GOOGLE_OAUTH_CLIENT_SECRET=%s\n' "${GOOGLE_OAUTH_CLIENT_SECRET:-}"
    } >> .env.prod.local
    echo "[railway-start] OAuth env written to .env.prod.local (client id length: ${#GOOGLE_OAUTH_CLIENT_ID})"
else
    echo "[railway-start] WARNING: GOOGLE_OAUTH_CLIENT_ID is not set — Google sign-in will be hidden." >&2
    echo "[railway-start] Add GOOGLE_OAUTH_* on the TeachMeLMS web service in Railway, then redeploy." >&2
fi

# PEM files are generated during build; Railway JWT_PUBLIC_KEY/JWT_SECRET_KEY vars break Lexik if set to raw key text.
{
    echo 'JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem'
    echo 'JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem'
} >> .env.prod.local

# Committed .env points at localhost; Railway DATABASE_URL must override for prod.
if [ -n "${DATABASE_URL:-}" ]; then
    printf 'DATABASE_URL=%s\n' "$DATABASE_URL" >> .env.prod.local
    echo "[railway-start] DATABASE_URL written to .env.prod.local"
else
    echo "[railway-start] WARNING: DATABASE_URL is not set — app will use .env (localhost) and DB routes will fail." >&2
fi

export APP_ENV=prod
export APP_DEBUG=0

# Load OAuth into the shell so cache warmup inlines real client_id for HWI (not empty from .env).
if [ -f .env.prod.local ]; then
    set -a
    # shellcheck disable=SC1091
    . ./.env.prod.local
    set +a
fi

if [ -z "${GOOGLE_OAUTH_CLIENT_ID:-}" ]; then
    echo "[railway-start] WARNING: GOOGLE_OAUTH_CLIENT_ID still empty after .env.prod.local — Google redirect will fail." >&2
else
    echo "[railway-start] GOOGLE_OAUTH_CLIENT_ID available for cache warmup (length: ${#GOOGLE_OAUTH_CLIENT_ID})"
fi

# Keys are created at build with empty JWT_PASSPHRASE from .env; regenerate when Railway sets a passphrase.
if [ -n "${JWT_PASSPHRASE:-}" ]; then
    echo "[railway-start] Regenerating JWT keypair for configured JWT_PASSPHRASE..."
    php bin/console lexik:jwt:generate-keypair --overwrite --no-interaction
else
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

php bin/console cache:clear --no-interaction
php bin/console cache:warmup --no-interaction

exec php -S "0.0.0.0:${PORT:-8000}" -t public public/router.php
