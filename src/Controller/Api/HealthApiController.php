<?php

namespace App\Controller\Api;

use App\Config\GoogleOAuthEnv;
use App\Http\MobileApiResponse;
use App\Repository\UserRepository;
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
    public function __construct(
        private readonly int $websocketPort,
    ) {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, Connection $connection, UserRepository $userRepository): JsonResponse
    {
        $googleClientId = GoogleOAuthEnv::clientId();
        $envProdLocal = dirname(__DIR__, 3).'/.env.prod.local';
        $fileHasGoogle = is_readable($envProdLocal)
            && (bool) preg_match('/^GOOGLE_OAUTH_CLIENT_ID=\S+/m', (string) @file_get_contents($envProdLocal));

        $databaseReady = false;
        $databaseError = null;
        $ormReady = false;
        $ormError = null;
        try {
            $connection->executeQuery(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user' LIMIT 1"
            )->fetchOne();
            $databaseReady = true;
        } catch (\Throwable $e) {
            $databaseError = $e->getMessage();
        }

        if ($databaseReady) {
            try {
                $userRepository->createQueryBuilder('u')
                    ->select('u.id')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
                $ormReady = true;
            } catch (\Throwable $e) {
                $ormError = $e->getMessage();
            }
        }

        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';
        $databaseHost = '';
        if (is_string($databaseUrl) && $databaseUrl !== '') {
            $parts = parse_url($databaseUrl);
            $databaseHost = is_array($parts) ? (string) ($parts['host'] ?? '') : '';
        }

        $jwtPrivatePath = $this->getParameter('kernel.project_dir').'/config/jwt/private.pem';
        $jwtPublicPath = $this->getParameter('kernel.project_dir').'/config/jwt/public.pem';
        $jwtKeysExist = is_readable($jwtPrivatePath) && is_readable($jwtPublicPath);

        $data = [
            'status' => 'ok',
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'google_oauth_configured' => $googleClientId !== '',
            'google_oauth_login_button' => $googleClientId !== '',
            'google_oauth_redirect_uri' => GoogleOAuthEnv::redirectUri(),
            'google_oauth_env_prod_local' => $fileHasGoogle,
            'database_ready' => $databaseReady,
            'orm_ready' => $ormReady,
            'jwt_keys_ready' => $jwtKeysExist,
            'database_host' => $databaseHost,
            'database_looks_local' => in_array($databaseHost, ['127.0.0.1', 'localhost', '::1'], true),
            'railway_runtime' => isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_SERVER['RAILWAY_ENVIRONMENT']),
            'websocket_port' => $this->websocketPort,
            'websocket_discovery' => '/api/ws',
        ];

        if ($request->query->getBoolean('debug')) {
            if ($databaseError !== null) {
                $data['database_error'] = $databaseError;
            }
            if ($ormError !== null) {
                $data['orm_error'] = $ormError;
            }
            $data['jwt_private_readable'] = is_readable($jwtPrivatePath);
            $data['jwt_public_readable'] = is_readable($jwtPublicPath);
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
