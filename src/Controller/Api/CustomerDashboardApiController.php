<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Http\MobileApiResponse;
use App\Repository\CourseRepository;
use App\Repository\TutorRepository;
use App\Service\EnrollmentApiPresenter;
use App\Service\EnrollmentService;
use App\Service\ParentLearnerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CustomerDashboardApiController extends AbstractController
{
    #[Route('/api/dashboard/customer', name: 'api_dashboard_customer', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function customer(
        CourseRepository $courses,
        TutorRepository $tutors,
        EnrollmentService $enrollmentService,
        EnrollmentApiPresenter $presenter,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return MobileApiResponse::json(false, 'Unauthorized.', null, [], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if ($this->isGranted('ROLE_STAFF')) {
            return MobileApiResponse::json(
                true,
                'Staff accounts use the web admin dashboard.',
                [
                    'isStaff' => true,
                    'coursesCount' => $courses->count([]),
                    'tutorsCount' => $tutors->count([]),
                    'learningPathsCount' => 4,
                    'weeklyProgress' => [0, 0, 0, 0, 0, 0, 0],
                    'recentActivity' => [],
                ],
                []
            );
        }

        $snapshot = $enrollmentService->buildMobileDashboardSnapshot($user, $courses, $tutors, $presenter);

        return MobileApiResponse::json(true, 'Customer dashboard loaded.', [
            'isStaff' => false,
            ...$snapshot,
            'syncToken' => $enrollmentService->getDashboardSyncToken($user),
        ], []);
    }
}
