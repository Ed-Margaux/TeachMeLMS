<?php

namespace App\Controller\Api;

use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\User;
use App\Http\MobileApiResponse;
use App\Repository\ClassSessionRepository;
use App\Repository\EnrollmentRepository;
use App\Service\EnrollmentApiPresenter;
use App\Service\ParentLearnerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MyLearnersApiController extends AbstractController
{
    use ParentApiTrait;

    #[Route('/api/my-learners', name: 'api_my_learners_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(ParentLearnerService $learners): JsonResponse
    {
        $user = $this->requireParentUser();
        $learners->syncWebStudentsToParent($user);

        $items = [];
        foreach ($learners->getLearnersForParent($user, false) as $student) {
            $items[] = $this->serializeLearner($student);
        }

        return MobileApiResponse::json(true, 'Learners list (synced with web admin).', $items, []);
    }

    #[Route('/api/my-learners', name: 'api_my_learners_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, ParentLearnerService $learners): JsonResponse
    {
        $user = $this->requireParentUser();

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

        $firstName = trim((string) ($data['firstName'] ?? ''));
        $lastName = trim((string) ($data['lastName'] ?? ''));
        $grade = trim((string) ($data['grade'] ?? '')) ?: null;

        $errors = [];
        if ($firstName === '') {
            $errors['firstName'] = 'required';
        }
        if ($lastName === '') {
            $errors['lastName'] = 'required';
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

        $learner = $learners->createLearnerForParent($user, $firstName, $lastName, $grade);

        return MobileApiResponse::json(
            true,
            'Learner added.',
            $this->serializeLearner($learner),
            [],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/api/my-learners/{id}', name: 'api_my_learners_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(
        int $id,
        ParentLearnerService $parentLearners,
        EnrollmentRepository $enrollments,
        EnrollmentApiPresenter $presenter,
    ): JsonResponse {
        $parent = $this->requireParentUser();
        $student = $this->requireOwnedStudent($id, $parent, $parentLearners);

        $enrollmentItems = [];
        foreach ($enrollments->findForStudentIds([(int) $student->getId()]) as $enrollment) {
            $enrollmentItems[] = $presenter->enrollment($enrollment);
        }

        return MobileApiResponse::json(true, 'Learner detail.', [
            'learner' => $this->serializeLearner($student),
            'enrollments' => $enrollmentItems,
        ], []);
    }

    #[Route('/api/my-learners/{id}/schedule', name: 'api_my_learners_schedule', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function schedule(
        int $id,
        ParentLearnerService $parentLearners,
        ClassSessionRepository $classSessions,
        EnrollmentApiPresenter $presenter,
    ): JsonResponse {
        $parent = $this->requireParentUser();
        $student = $this->requireOwnedStudent($id, $parent, $parentLearners);

        $sessions = [];
        foreach ($classSessions->findUpcomingForStudentIds([(int) $student->getId()]) as $session) {
            $sessions[] = $presenter->classSession($session);
        }

        return MobileApiResponse::json(true, 'Upcoming class schedule.', $sessions, []);
    }

    #[Route('/api/my-learners/{id}/progress', name: 'api_my_learners_progress', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function progress(
        int $id,
        ParentLearnerService $parentLearners,
        EnrollmentRepository $enrollments,
        ClassSessionRepository $classSessions,
    ): JsonResponse {
        $parent = $this->requireParentUser();
        $student = $this->requireOwnedStudent($id, $parent, $parentLearners);
        $studentId = (int) $student->getId();

        $activeEnrollments = 0;
        $pendingEnrollments = 0;
        $completedSessions = 0;
        $upcomingSessions = 0;

        foreach ($enrollments->findForStudentIds([$studentId]) as $enrollment) {
            if ($enrollment->getStatus() === Enrollment::STATUS_ACTIVE) {
                ++$activeEnrollments;
                $completedSessions += $classSessions->countCompletedForEnrollment($enrollment);
                $upcomingSessions += \count($classSessions->findUpcomingForEnrollment($enrollment));
            } elseif ($enrollment->getStatus() === Enrollment::STATUS_PENDING) {
                ++$pendingEnrollments;
            }
        }

        return MobileApiResponse::json(true, 'Learner progress summary.', [
            'studentId' => $studentId,
            'activeEnrollments' => $activeEnrollments,
            'pendingEnrollments' => $pendingEnrollments,
            'completedSessions' => $completedSessions,
            'upcomingSessions' => $upcomingSessions,
        ], []);
    }

    private function requireOwnedStudent(int $id, User $parent, ParentLearnerService $parentLearners): Student
    {
        $parentLearners->syncWebStudentsToParent($parent);
        foreach ($parentLearners->getLearnersForParent($parent, false) as $student) {
            if ($student->getId() === $id) {
                return $student;
            }
        }

        throw $this->createNotFoundException('Learner not found.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLearner(Student $student): array
    {
        $email = (string) $student->getEmail();

        return [
            'id' => $student->getId(),
            'firstName' => $student->getFirstName(),
            'lastName' => $student->getLastName(),
            'fullName' => $student->getFullName(),
            'grade' => $student->getGrade(),
            'parentEmail' => $student->getParentEmail(),
            'createdAt' => $student->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => ($student->getUpdatedAt() ?? $student->getCreatedAt())?->format(DATE_ATOM),
            'source' => str_contains($email, '@parents.teachme') ? 'mobile' : 'web',
        ];
    }
}
