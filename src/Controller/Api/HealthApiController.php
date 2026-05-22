<?php

namespace App\Controller\Api;

use App\Config\GoogleOAuthEnv;
use App\Http\MobileApiResponse;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public connectivity / version probe for mobile clients (no auth).
 */
final class HealthApiController extends AbstractController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, Connection $connection): JsonResponse
    {
        $googleClientId = GoogleOAuthEnv::clientId();
        $envProdLocal = dirname(__DIR__, 3).'/.env.prod.local';
        $fileHasGoogle = is_readable($envProdLocal)
            && (bool) preg_match('/^GOOGLE_OAUTH_CLIENT_ID=\S+/m', (string) @file_get_contents($envProdLocal));

        $databaseReady = false;
        $databaseError = null;
        try {
            $connection->executeQuery(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user' LIMIT 1"
            )->fetchOne();
            $databaseReady = true;
        } catch (\Throwable $e) {
            $databaseError = $e->getMessage();
        }

        $data = [
            'status' => 'ok',
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'google_oauth_configured' => $googleClientId !== '',
            'google_oauth_login_button' => $googleClientId !== '',
            'google_oauth_env_prod_local' => $fileHasGoogle,
            'database_ready' => $databaseReady,
            'railway_runtime' => isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_SERVER['RAILWAY_ENVIRONMENT']),
        ];

        if ($request->query->getBoolean('debug') && $databaseError !== null) {
            $data['database_error'] = $databaseError;
        }

        return MobileApiResponse::json(
            true,
            'API is reachable.',
            $data,
            [],
            JsonResponse::HTTP_OK
        );
    }
}
