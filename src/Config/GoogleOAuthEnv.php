<?php

namespace App\Config;

/**
 * Resolves Google OAuth client id from process env.
 *
 * Committed .env sets GOOGLE_OAUTH_CLIENT_ID= (empty) which populates $_ENV.
 * Railway / .env.prod.local may only be visible via getenv() — check non-empty values in order.
 */
final class GoogleOAuthEnv
{
    public static function clientId(): string
    {
        $candidates = [
            getenv('GOOGLE_OAUTH_CLIENT_ID'),
            $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? null,
            $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? null,
        ];

        foreach ($candidates as $id) {
            if (!is_string($id)) {
                continue;
            }
            $id = trim($id, " \t\n\r\0\x0B\"");
            if ($id !== '') {
                return $id;
            }
        }

        return '';
    }
}
