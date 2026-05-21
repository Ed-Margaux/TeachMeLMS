<?php

namespace App\Controller\Api;

use App\Http\MobileApiResponse;
use App\Repository\EnrollmentRepository;
use App\Service\EnrollmentApiPresenter;
use App\Service\EnrollmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EnrollmentApiController extends AbstractController
{
    use ParentApiTrait;

    #[Route('/api/enrollments', name: 'api_enrollments_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(EnrollmentRepository $enrollmentRepository, EnrollmentApiPresenter $presenter): JsonResponse
    {
        $parent = $this->requireParentUser();
        $items = [];
        foreach ($enrollmentRepository->findForParent($parent) as $enrollment) {
            $items[] = $presenter->enrollment($enrollment);
        }

        return MobileApiResponse::json(true, 'Enrollments list.', $items, []);
    }

    #[Route('/api/enrollments', name: 'api_enrollments_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EnrollmentService $enrollmentService): JsonResponse
    {
        $parent = $this->requireParentUser();

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return MobileApiResponse::json(
                false,
                'Invalid JSON body.',
                null,
                ['body' => 'invalid_json'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $studentId = (int) ($data['studentId'] ?? 0);
        $courseId = (int) ($data['courseId'] ?? 0);
        $tutorId = isset($data['tutorId']) ? (int) $data['tutorId'] : null;
        $parentNote = trim((string) ($data['parentNote'] ?? ''));

        $errors = [];
        if ($studentId <= 0) {
            $errors['studentId'] = 'required';
        }
        if ($courseId <= 0) {
            $errors['courseId'] = 'required';
        }
        if ($errors !== []) {
            return MobileApiResponse::json(
                false,
                'Validation failed.',
                null,
                $errors,
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $result = $enrollmentService->requestEnrollment(
            $parent,
            $studentId,
            $courseId,
            $tutorId > 0 ? $tutorId : null,
            $parentNote !== '' ? $parentNote : null,
        );

        if (isset($result['errors'])) {
            $status = ($result['errors']['enrollment'] ?? '') === 'pending_request_exists'
                ? JsonResponse::HTTP_CONFLICT
                : JsonResponse::HTTP_BAD_REQUEST;

            return MobileApiResponse::json(false, 'Could not submit enrollment request.', null, $result['errors'], $status);
        }

        return MobileApiResponse::json(
            true,
            'Enrollment request submitted. Teach Me staff will review it shortly.',
            $result['data'],
            [],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/api/enrollments/{id}', name: 'api_enrollments_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id, EnrollmentService $enrollmentService, EnrollmentApiPresenter $presenter): JsonResponse
    {
        $parent = $this->requireParentUser();
        $enrollment = $enrollmentService->findEnrollmentForParent($id, $parent);

        if ($enrollment === null) {
            return MobileApiResponse::json(false, 'Enrollment not found.', null, [], JsonResponse::HTTP_NOT_FOUND);
        }

        return MobileApiResponse::json(
            true,
            'Enrollment detail.',
            $presenter->enrollment($enrollment, true),
            []
        );
    }

    #[Route('/api/enrollments/{id}', name: 'api_enrollments_cancel', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(int $id, EnrollmentService $enrollmentService, EnrollmentApiPresenter $presenter): JsonResponse
    {
        $parent = $this->requireParentUser();
        $enrollment = $enrollmentService->findEnrollmentForParent($id, $parent);

        if ($enrollment === null) {
            return MobileApiResponse::json(false, 'Enrollment not found.', null, [], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$enrollmentService->cancelPending($enrollment, $parent)) {
            return MobileApiResponse::json(
                false,
                'Only pending enrollment requests can be cancelled.',
                null,
                ['status' => 'not_pending'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return MobileApiResponse::json(
            true,
            'Enrollment request cancelled.',
            $presenter->enrollment($enrollment),
            []
        );
    }
}
