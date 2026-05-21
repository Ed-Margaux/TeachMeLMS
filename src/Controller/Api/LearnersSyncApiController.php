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
 * Lightweight sync endpoint so the mobile app can poll for web-admin student changes.
 */
final class LearnersSyncApiController extends AbstractController
{
    #[Route('/api/sync/learners', name: 'api_sync_learners', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function sync(Request $request, RealtimeSyncService $sync): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $this->isGranted('ROLE_STAFF')) {
            return MobileApiResponse::json(false, 'Unauthorized.', null, [], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $sync->buildParentSyncPayload(
            $user,
            $request->query->getString('token'),
            $request->query->getString('learnersToken'),
            $request->query->getString('dashboardToken'),
        );

        return MobileApiResponse::json(true, 'Sync check (use GET /api/sync/wait for full real-time).', $payload, []);
    }
}
