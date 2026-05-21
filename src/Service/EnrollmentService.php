<?php

namespace App\Service;

use App\Entity\ClassSession;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\Tutor;
use App\Entity\User;
use App\Repository\ClassSessionRepository;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\StudentRepository;
use App\Repository\TutorRepository;
use Doctrine\ORM\EntityManagerInterface;

final class EnrollmentService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EnrollmentRepository $enrollments,
        private readonly ClassSessionRepository $classSessions,
        private readonly StudentRepository $students,
        private readonly CourseRepository $courses,
        private readonly TutorRepository $tutors,
        private readonly ParentLearnerService $parentLearners,
        private readonly EnrollmentApiPresenter $presenter,
    ) {
    }

    /**
     * @return array{enrollment: Enrollment, data: array<string, mixed>}|array{errors: array<string, string>}
     */
    public function requestEnrollment(
        User $parent,
        int $studentId,
        int $courseId,
        ?int $tutorId = null,
        ?string $parentNote = null,
    ): array {
        $this->parentLearners->syncWebStudentsToParent($parent);

        $student = $this->students->find($studentId);
        if ($student === null || !$this->parentLearners->parentOwnsLearner($parent, $student)) {
            return ['errors' => ['studentId' => 'not_found_or_not_owned']];
        }

        $course = $this->courses->find($courseId);
        if ($course === null) {
            return ['errors' => ['courseId' => 'not_found']];
        }

        $tutor = null;
        if ($tutorId !== null) {
            $tutor = $this->tutors->find($tutorId);
            if ($tutor === null) {
                return ['errors' => ['tutorId' => 'not_found']];
            }
        }

        if ($this->enrollments->hasPendingForStudentCourse($studentId, $courseId)) {
            return ['errors' => ['enrollment' => 'pending_request_exists']];
        }

        $enrollment = new Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setCourse($course);
        $enrollment->setTutor($tutor);
        $enrollment->setStatus(Enrollment::STATUS_PENDING);
        $enrollment->setParentNote($parentNote !== '' ? $parentNote : null);
        $enrollment->setRequestedAt(new \DateTimeImmutable());
        $enrollment->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($enrollment);
        $this->em->flush();

        return [
            'enrollment' => $enrollment,
            'data' => $this->presenter->enrollment($enrollment),
        ];
    }

    public function cancelPending(Enrollment $enrollment, User $parent): bool
    {
        if ($enrollment->getStatus() !== Enrollment::STATUS_PENDING) {
            return false;
        }

        $student = $enrollment->getStudent();
        if ($student === null || !$this->parentLearners->parentOwnsLearner($parent, $student)) {
            return false;
        }

        $enrollment->setStatus(Enrollment::STATUS_CANCELLED);
        $enrollment->touch();
        $this->em->flush();

        return true;
    }

    public function approve(Enrollment $enrollment, User $staff, Tutor $tutor, ?string $staffNote = null): void
    {
        $enrollment->setStatus(Enrollment::STATUS_ACTIVE);
        $enrollment->setTutor($tutor);
        $enrollment->setApprovedAt(new \DateTimeImmutable());
        $enrollment->setApprovedBy($staff);
        if ($staffNote !== null && $staffNote !== '') {
            $enrollment->setStaffNote($staffNote);
        }
        $enrollment->touch();
        $this->em->flush();
    }

    public function reject(Enrollment $enrollment, User $staff, string $staffNote): void
    {
        $enrollment->setStatus(Enrollment::STATUS_REJECTED);
        $enrollment->setStaffNote($staffNote);
        $enrollment->setApprovedBy($staff);
        $enrollment->setApprovedAt(new \DateTimeImmutable());
        $enrollment->touch();
        $this->em->flush();
    }

    public function persistClassSession(ClassSession $session): void
    {
        $this->em->persist($session);
        $this->em->flush();
    }

    public function markSessionCompleted(ClassSession $session): void
    {
        $session->setStatus(ClassSession::STATUS_COMPLETED);
        $session->touch();
        $enrollment = $session->getEnrollment();
        if ($enrollment !== null) {
            $enrollment->touch();
        }
        $this->em->flush();
    }

    /**
     * @return list<int>
     */
    public function studentIdsForParent(User $parent): array
    {
        $this->parentLearners->syncWebStudentsToParent($parent);

        return array_map(
            static fn (Student $s): int => (int) $s->getId(),
            $this->parentLearners->getLearnersForParent($parent, false)
        );
    }

    public function findEnrollmentForParent(int $id, User $parent): ?Enrollment
    {
        foreach ($this->enrollments->findForParent($parent) as $enrollment) {
            if ($enrollment->getId() === $id) {
                return $enrollment;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentActivityForParent(User $parent, int $limit = 8): array
    {
        $items = [];
        $studentIds = $this->studentIdsForParent($parent);

        foreach ($this->enrollments->findForStudentIds($studentIds) as $enrollment) {
            $student = $enrollment->getStudent();
            $course = $enrollment->getCourse();
            $name = $student?->getFullName() ?? 'Learner';
            $courseTitle = $course?->getTitle() ?? 'Course';

            if ($enrollment->getStatus() === Enrollment::STATUS_PENDING) {
                $items[] = [
                    'type' => 'enrollment_pending',
                    'title' => 'Enrollment requested',
                    'subtitle' => sprintf('%s · %s', $name, $courseTitle),
                    'timestamp' => $enrollment->getRequestedAt()?->format(DATE_ATOM) ?? '',
                ];
            } elseif ($enrollment->getStatus() === Enrollment::STATUS_ACTIVE) {
                $items[] = [
                    'type' => 'enrollment_active',
                    'title' => 'Enrollment approved',
                    'subtitle' => sprintf('%s · %s', $name, $courseTitle),
                    'timestamp' => $enrollment->getApprovedAt()?->format(DATE_ATOM) ?? '',
                ];
            } elseif ($enrollment->getStatus() === Enrollment::STATUS_REJECTED) {
                $items[] = [
                    'type' => 'enrollment_rejected',
                    'title' => 'Enrollment not approved',
                    'subtitle' => sprintf('%s · %s', $name, $courseTitle),
                    'timestamp' => $enrollment->getApprovedAt()?->format(DATE_ATOM) ?? '',
                ];
            }
        }

        foreach ($this->classSessions->findRecentCompletedForStudentIds($studentIds, 5) as $session) {
            $items[] = [
                'type' => 'session_completed',
                'title' => 'Class completed',
                'subtitle' => $session->getTitle(),
                'timestamp' => ($session->getUpdatedAt() ?? $session->getScheduledAt())?->format(DATE_ATOM) ?? '',
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp($b['timestamp'], $a['timestamp']));

        return \array_slice($items, 0, $limit);
    }

    public function getDashboardSyncToken(User $parent): string
    {
        $studentIds = $this->studentIdsForParent($parent);
        $pending = $this->enrollments->countPendingForStudentIds($studentIds);
        $latestEnrollment = $this->classSessions->getLatestChangeTimestampForStudentIds($studentIds);
        $latestLearner = $this->students->getLatestChangeTimestampForParent($parent);

        $parts = [(string) $pending];
        $parts[] = $latestEnrollment?->format('U') ?? '0';
        $parts[] = $latestLearner?->format('U') ?? '0';

        return hash('crc32b', implode(':', $parts));
    }

    /**
     * Dashboard fields for mobile home + sync push (keeps API and long-poll in sync).
     *
     * @return array<string, mixed>
     */
    public function buildMobileDashboardSnapshot(
        User $parent,
        \App\Repository\CourseRepository $courses,
        \App\Repository\TutorRepository $tutors,
        EnrollmentApiPresenter $presenter,
    ): array {
        $myLearners = $this->parentLearners->getLearnersForParent($parent);
        $learnersCount = \count($myLearners);
        $studentIds = $this->studentIdsForParent($parent);
        $pendingEnrollmentsCount = $this->enrollments->countPendingForStudentIds($studentIds);

        $nextSession = $this->classSessions->findNextForParent($parent);
        $nextSessionData = null;
        if ($nextSession instanceof ClassSession) {
            $nextSessionData = $presenter->classSession($nextSession);
        }

        $activeEnrollments = 0;
        foreach ($this->enrollments->findForStudentIds($studentIds, Enrollment::STATUS_ACTIVE) as $ignored) {
            ++$activeEnrollments;
        }

        $base = max(1, $learnersCount + $activeEnrollments);
        $multipliers = [0.4, 0.55, 0.7, 0.85, 1.0, 0.75, 0.6];
        $weeklyProgress = array_map(
            static fn (float $m): int => max(0, (int) round($base * $m)),
            $multipliers
        );

        return [
            'learnersCount' => $learnersCount,
            'coursesCount' => $courses->count([]),
            'tutorsCount' => $tutors->count([]),
            'activeEnrollmentsCount' => $activeEnrollments,
            'pendingEnrollmentsCount' => $pendingEnrollmentsCount,
            'learningPathsCount' => 4,
            'nextSession' => $nextSessionData,
            'weeklyProgress' => $weeklyProgress,
            'recentActivity' => $this->recentActivityForParent($parent),
        ];
    }

}
