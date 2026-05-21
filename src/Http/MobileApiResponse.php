<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Standard envelope for mobile / JSON API consumers: success, message, data, errors, meta.
 */
final class MobileApiResponse
{
    public const API_VERSION = '1.0';

    /**
     * @param array<string, mixed>|list<mixed> $errors
     */
    public static function envelope(bool $success, string $message, mixed $data, array $errors = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => [
                'apiVersion' => self::API_VERSION,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|list<mixed> $errors
     */
    public static function json(bool $success, string $message, mixed $data, array $errors = [], int $status = 200): JsonResponse
    {
        return new JsonResponse(self::envelope($success, $message, $data, $errors), $status);
    }
}
