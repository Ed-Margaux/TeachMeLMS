<?php

namespace App\Controller\Api;

use App\Http\MobileApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api — lists mobile JSON endpoints (static route wins over API Platform entrypoint).
 */
final class ApiIndexController extends AbstractController
{
    #[Route('/api', name: 'api_index', methods: ['GET', 'HEAD'], priority: 20)]
    public function __invoke(): JsonResponse
    {
        if (!$this->getUser()) {
            return MobileApiResponse::json(
                false,
                'Unauthorized.',
                null,
                ['auth' => 'missing_or_invalid_token'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $endpoints = [
            ['method' => 'GET', 'path' => '/api/health', 'description' => 'Health check.', 'auth' => 'none'],
            ['method' => 'POST', 'path' => '/api/login', 'description' => 'Login; JWT in data.token.', 'auth' => 'none'],
            ['method' => 'POST', 'path' => '/api/login/google', 'description' => 'Google Sign-In.', 'auth' => 'none'],
            ['method' => 'POST', 'path' => '/api/register', 'description' => 'Register parent + first child.', 'auth' => 'none'],
            ['method' => 'POST', 'path' => '/api/verify-email', 'description' => 'Verify email.', 'auth' => 'none'],
            ['method' => 'GET', 'path' => '/api/me', 'description' => 'Parent profile.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/dashboard/customer', 'description' => 'Home dashboard.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/my-learners', 'description' => 'List children.', 'auth' => 'bearer'],
            ['method' => 'POST', 'path' => '/api/my-learners', 'description' => 'Add child.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/my-learners/{id}', 'description' => 'Child detail + enrollments.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/my-learners/{id}/schedule', 'description' => 'Upcoming online classes.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/my-learners/{id}/progress', 'description' => 'Progress summary.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/sync/learners', 'description' => 'Poll learner list changes.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/sync', 'description' => 'Sync snapshot (learners + dashboard tokens).', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/sync/wait', 'description' => 'Long-poll until parent data changes.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/courses', 'description' => 'Course catalog.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/courses/{id}', 'description' => 'Course detail.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/tutors', 'description' => 'Tutor roster.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/tutors/{id}', 'description' => 'Tutor detail.', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/enrollments', 'description' => 'List enrollment requests.', 'auth' => 'bearer'],
            ['method' => 'POST', 'path' => '/api/enrollments', 'description' => 'Request enrollment (pending).', 'auth' => 'bearer'],
            ['method' => 'GET', 'path' => '/api/enrollments/{id}', 'description' => 'Enrollment detail + sessions.', 'auth' => 'bearer'],
            ['method' => 'DELETE', 'path' => '/api/enrollments/{id}', 'description' => 'Cancel pending request.', 'auth' => 'bearer'],
        ];

        return MobileApiResponse::json(
            true,
            'Teach Me mobile API (Model C: parent requests, staff approves).',
            [
                'endpoints' => $endpoints,
                'note' => 'Authorization: Bearer <token>. See docs/API_MOBILE.md for samples.',
            ],
            []
        );
    }
}
