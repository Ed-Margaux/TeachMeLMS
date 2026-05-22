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
        $googleClientId = getenv('GOOGLE_OAUTH_CLIENT_ID') ?: ($_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? '');
        $envProdLocal = dirname(__DIR__, 3).'/.env.prod.local';
        $fileHasGoogle = is_readable($envProdLocal)
            && (bool) preg_match('/^GOOGLE_OAUTH_CLIENT_ID=\S+/m', (string) @file_get_contents($envProdLocal));

        return MobileApiResponse::json(
            true,
            'API is reachable.',
            [
                'status' => 'ok',
                'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
                'google_oauth_configured' => is_string($googleClientId) && $googleClientId !== '',
                'google_oauth_env_prod_local' => $fileHasGoogle,
                'railway_runtime' => isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_SERVER['RAILWAY_ENVIRONMENT']),
            ],
            [],
            JsonResponse::HTTP_OK
        );
    }
}
