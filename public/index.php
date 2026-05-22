<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Railway / reverse proxy: PHP built-in server sees HTTP; Google OAuth needs https redirect_uri.
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $forwardedProto = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
    if (str_contains($forwardedProto, 'https')) {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_SCHEME'] = 'https';
    }
}

return function (array $context) {
    // Prefer real environment (Railway Variables, shell) over .env defaults in $context.
    $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $context['APP_ENV'] ?? 'prod';
    $debug = filter_var(
        $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? $context['APP_DEBUG'] ?? false,
        FILTER_VALIDATE_BOOL
    );

    // Railway/production: composer install --no-dev does not ship DebugBundle.
    if ('dev' === $env && !class_exists(\Symfony\Bundle\DebugBundle\DebugBundle::class)) {
        $env = 'prod';
        $debug = false;
    }

    return new Kernel($env, $debug);
};
