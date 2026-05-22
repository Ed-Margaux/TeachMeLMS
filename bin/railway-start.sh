#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/.."

# Railway injects Variables into the shell environment. Symfony's committed .env leaves
# OAuth keys empty; write .env.prod.local so Dotenv picks them up in prod.
: > .env.prod.local

if [ -n "${GOOGLE_OAUTH_CLIENT_ID:-}" ]; then
    {
        printf 'GOOGLE_OAUTH_CLIENT_ID=%s\n' "$GOOGLE_OAUTH_CLIENT_ID"
        printf 'GOOGLE_OAUTH_CLIENT_SECRET=%s\n' "${GOOGLE_OAUTH_CLIENT_SECRET:-}"
        printf 'GOOGLE_OAUTH_REDIRECT_URI=%s\n' "${GOOGLE_OAUTH_REDIRECT_URI:-https://teachmelms-production.up.railway.app/connect/google/check}"
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

export APP_ENV=prod
export APP_DEBUG=0

php bin/console cache:clear --no-interaction
php bin/console cache:warmup --no-interaction

exec php -S "0.0.0.0:${PORT:-8000}" -t public public/router.php
