<?php

namespace App\Controller\Api;

use App\Http\MobileApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public connectivity / version probe for mobile clients (no auth).
 */
final class HealthApiController extends AbstractController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET', 'HEAD'])]
    public function __invoke(): JsonResponse
    {
        $googleClientId = $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? '';

        return MobileApiResponse::json(
            true,
            'API is reachable.',
            [
                'status' => 'ok',
                'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
                'google_oauth_configured' => is_string($googleClientId) && $googleClientId !== '',
            ],
            [],
            JsonResponse::HTTP_OK
        );
    }
}
