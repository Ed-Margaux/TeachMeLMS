<?php

namespace App\Config;

/**
 * Resolves Google OAuth settings from process env and .env.prod.local (Railway startup).
 *
 * Committed .env must not set GOOGLE_OAUTH_* to empty strings — that blocks real values in
 * Symfony's env() processor while getenv() may still see Railway shell variables.
 */
final class GoogleOAuthEnv
{
    public static function clientId(): string
    {
        return self::resolve('GOOGLE_OAUTH_CLIENT_ID');
    }

    public static function clientSecret(): string
    {
        return self::resolve('GOOGLE_OAUTH_CLIENT_SECRET');
    }

    public static function redirectUri(): string
    {
        return self::resolve('GOOGLE_OAUTH_REDIRECT_URI');
    }

    public static function resolve(string $name): string
    {
        $candidates = [
            getenv($name),
            $_ENV[$name] ?? null,
            $_SERVER[$name] ?? null,
            self::fromProdLocal($name),
        ];

        foreach ($candidates as $value) {
            if (!\is_string($value)) {
                continue;
            }
            $value = trim($value, " \t\n\r\0\x0B\"");
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function fromProdLocal(string $name): ?string
    {
        $path = dirname(__DIR__, 2).'/.env.prod.local';
        if (!is_readable($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);
        if (!preg_match('/^'.preg_quote($name, '/').'=(.*)$/m', $contents, $matches)) {
            return null;
        }

        $value = trim($matches[1]);
        if ($value !== '' && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        return $value !== '' ? $value : null;
    }
}
