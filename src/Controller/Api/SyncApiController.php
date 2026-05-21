<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Http\MobileApiResponse;
use App\Service\RealtimeSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Real-time sync for parent mobile app (long-poll + snapshot).
 */
final class SyncApiController extends AbstractController
{
    #[Route('/api/sync', name: 'api_sync_snapshot', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function snapshot(Request $request, RealtimeSyncService $sync): JsonResponse
    {
        $user = $this->parentUser();
        if ($user === null) {
            return MobileApiResponse::json(false, 'Unauthorized.', null, [], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return MobileApiResponse::json(
            true,
            'Sync snapshot.',
            $sync->buildParentSyncPayload(
                $user,
                $request->query->getString('token'),
                $request->query->getString('learnersToken'),
                $request->query->getString('dashboardToken'),
            ),
        );
    }

    /**
     * Long-poll: holds the request until data changes or timeout (seconds).
     */
    #[Route('/api/sync/wait', name: 'api_sync_wait', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function wait(Request $request, RealtimeSyncService $sync): JsonResponse
    {
        $user = $this->parentUser();
        if ($user === null) {
            return MobileApiResponse::json(false, 'Unauthorized.', null, [], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $clientToken = $request->query->getString('token');
        $clientLearnersToken = $request->query->getString('learnersToken');
        $clientDashboardToken = $request->query->getString('dashboardToken');
        $timeout = min(30, max(5, $request->query->getInt('timeout', 25)));
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $current = $sync->getParentToken($user);
            if ($clientToken === '' || $clientToken !== $current) {
                return MobileApiResponse::json(
                    true,
                    'Sync update.',
                    $sync->buildParentSyncPayload(
                        $user,
                        $clientToken,
                        $clientLearnersToken,
                        $clientDashboardToken,
                    ),
                );
            }

            usleep(800_000);
        }

        $syncToken = $sync->getParentToken($user);

        return MobileApiResponse::json(true, 'No changes yet.', [
            'syncToken' => $syncToken,
            'learnersToken' => $sync->getLearnersToken($user),
            'dashboardToken' => $sync->getDashboardToken($user),
            'changed' => false,
            'learnersChanged' => false,
            'dashboardChanged' => false,
            'learners' => [],
            'polledAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    private function parentUser(): ?User
    {
        $user = $this->getUser();
        if (!$user instanceof User || $this->isGranted('ROLE_STAFF')) {
            return null;
        }

        return $user;
    }
}
