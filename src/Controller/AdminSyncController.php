<?php

namespace App\Controller;

use App\Service\RealtimeSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Long-poll sync for staff web admin (session auth).
 */
#[Route('/admin/sync')]
#[IsGranted('ROLE_STAFF')]
final class AdminSyncController extends AbstractController
{
    #[Route('/check', name: 'app_admin_sync_check', methods: ['GET'])]
    public function check(Request $request, RealtimeSyncService $sync): JsonResponse
    {
        $clientToken = $request->query->getString('token');
        $syncToken = $sync->getStaffToken();
        $changed = $clientToken === '' || $clientToken !== $syncToken;

        return new JsonResponse([
            'syncToken' => $syncToken,
            'changed' => $changed,
            'polledAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    #[Route('/wait', name: 'app_admin_sync_wait', methods: ['GET'])]
    public function wait(Request $request, RealtimeSyncService $sync): JsonResponse
    {
        $clientToken = $request->query->getString('token');
        $timeout = min(30, max(5, $request->query->getInt('timeout', 25)));
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $current = $sync->getStaffToken();
            if ($clientToken === '' || $clientToken !== $current) {
                return new JsonResponse([
                    'syncToken' => $current,
                    'changed' => true,
                    'polledAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
                ]);
            }

            usleep(800_000);
        }

        return new JsonResponse([
            'syncToken' => $sync->getStaffToken(),
            'changed' => false,
            'polledAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}
