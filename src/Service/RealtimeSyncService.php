<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ClassSessionRepository;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\StudentRepository;
use App\Repository\TutorRepository;

/**
 * Change fingerprints for mobile (parents) and web admin (staff).
 * Clients long-poll until the token changes, then apply pushed data (no full reload).
 */
final class RealtimeSyncService
{
    public function __construct(
        private readonly ParentLearnerService $parentLearners,
        private readonly EnrollmentService $enrollmentService,
        private readonly EnrollmentApiPresenter $enrollmentPresenter,
        private readonly StudentRepository $students,
        private readonly EnrollmentRepository $enrollmentRepo,
        private readonly ClassSessionRepository $classSessions,
        private readonly CourseRepository $courses,
        private readonly TutorRepository $tutors,
    ) {
    }

    public function getLearnersToken(User $parent): string
    {
        return $this->parentLearners->getSyncTokenForParent($parent);
    }

    public function getDashboardToken(User $parent): string
    {
        return $this->enrollmentService->getDashboardSyncToken($parent);
    }

    /** Combined token for parent mobile long-poll. */
    public function getParentToken(User $parent): string
    {
        return hash('crc32b', $this->getLearnersToken($parent).':'.$this->getDashboardToken($parent));
    }

    /** Fingerprint for staff web admin (students, enrollments, class sessions). */
    public function getStaffToken(): string
    {
        $studentRow = $this->students->createQueryBuilder('s')
            ->select('COUNT(s.id) AS cnt', 'MAX(COALESCE(s.updatedAt, s.createdAt)) AS latest')
            ->getQuery()
            ->getOneOrNullResult();

        $enrollmentRow = $this->enrollmentRepo->createQueryBuilder('e')
            ->select('COUNT(e.id) AS cnt', 'MAX(COALESCE(e.updatedAt, e.requestedAt)) AS latest')
            ->getQuery()
            ->getOneOrNullResult();

        $sessionRow = $this->classSessions->createQueryBuilder('cs')
            ->select('COUNT(cs.id) AS cnt', 'MAX(COALESCE(cs.updatedAt, cs.createdAt)) AS latest')
            ->getQuery()
            ->getOneOrNullResult();

        return hash('crc32b', implode(':', [
            $this->formatAggregateRow($studentRow),
            $this->formatAggregateRow($enrollmentRow),
            $this->formatAggregateRow($sessionRow),
        ]));
    }

    /**
     * @return array{
     *   syncToken: string,
     *   learnersToken: string,
     *   dashboardToken: string,
     *   changed: bool,
     *   learnersChanged: bool,
     *   dashboardChanged: bool,
     *   learners: list<array<string, mixed>>,
     *   enrollments: list<array<string, mixed>>,
     *   dashboard: array<string, mixed>|null,
     *   polledAt: string
     * }
     */
    public function buildParentSyncPayload(
        User $parent,
        string $clientToken,
        string $clientLearnersToken = '',
        string $clientDashboardToken = '',
    ): array {
        $learnersToken = $this->getLearnersToken($parent);
        $dashboardToken = $this->getDashboardToken($parent);
        $syncToken = $this->getParentToken($parent);

        $learnersChanged = $clientLearnersToken === '' || $clientLearnersToken !== $learnersToken;
        $dashboardChanged = $clientDashboardToken === '' || $clientDashboardToken !== $dashboardToken;
        $changed = $clientToken === '' || $clientToken !== $syncToken
            || $learnersChanged
            || $dashboardChanged;

        $learners = [];
        if ($learnersChanged) {
            foreach ($this->parentLearners->getLearnersForParent($parent, false) as $student) {
                $learners[] = [
                    'id' => $student->getId(),
                    'firstName' => $student->getFirstName(),
                    'lastName' => $student->getLastName(),
                    'fullName' => $student->getFullName(),
                    'grade' => $student->getGrade(),
                    'parentEmail' => $student->getParentEmail(),
                    'updatedAt' => ($student->getUpdatedAt() ?? $student->getCreatedAt())?->format(DATE_ATOM),
                    'source' => str_contains((string) $student->getEmail(), '@parents.teachme')
                        ? 'mobile'
                        : 'web',
                ];
            }
        }

        $enrollments = [];
        $dashboard = null;
        if ($dashboardChanged) {
            foreach ($this->enrollmentRepo->findForParent($parent) as $enrollment) {
                $enrollments[] = $this->enrollmentPresenter->enrollment($enrollment);
            }
            $dashboard = $this->enrollmentService->buildMobileDashboardSnapshot(
                $parent,
                $this->courses,
                $this->tutors,
                $this->enrollmentPresenter,
            );
        }

        return [
            'syncToken' => $syncToken,
            'learnersToken' => $learnersToken,
            'dashboardToken' => $dashboardToken,
            'changed' => $changed,
            'learnersChanged' => $learnersChanged,
            'dashboardChanged' => $dashboardChanged,
            'learners' => $learners,
            'enrollments' => $enrollments,
            'dashboard' => $dashboard,
            'polledAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }

    /**
     * @param array{cnt?: int|string|null, latest?: mixed}|null $row
     */
    private function formatAggregateRow(?array $row): string
    {
        $cnt = (string) ($row['cnt'] ?? 0);
        $latest = $row['latest'] ?? null;
        if ($latest instanceof \DateTimeInterface) {
            $latest = $latest->format('U');
        } else {
            $latest = '0';
        }

        return $cnt.':'.$latest;
    }
}
