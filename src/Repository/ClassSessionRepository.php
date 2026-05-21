<?php

namespace App\Repository;

use App\Entity\ClassSession;
use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClassSession>
 */
class ClassSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassSession::class);
    }

    /**
     * @return list<ClassSession>
     */
    public function findUpcomingForEnrollment(Enrollment $enrollment, ?\DateTimeImmutable $from = null): array
    {
        $from ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('cs')
            ->where('cs.enrollment = :enrollment')
            ->andWhere('cs.scheduledAt >= :from')
            ->andWhere('cs.status = :scheduled')
            ->setParameter('enrollment', $enrollment)
            ->setParameter('from', $from)
            ->setParameter('scheduled', ClassSession::STATUS_SCHEDULED)
            ->orderBy('cs.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $studentIds
     *
     * @return list<ClassSession>
     */
    public function findUpcomingForStudentIds(array $studentIds, int $daysAhead = 30): array
    {
        if ($studentIds === []) {
            return [];
        }

        $from = new \DateTimeImmutable();
        $to = $from->modify(sprintf('+%d days', $daysAhead));

        return $this->createQueryBuilder('cs')
            ->innerJoin('cs.enrollment', 'e')
            ->addSelect('e', 's', 'c', 't')
            ->innerJoin('e.student', 's')
            ->innerJoin('e.course', 'c')
            ->leftJoin('e.tutor', 't')
            ->where('s.id IN (:ids)')
            ->andWhere('e.status = :active')
            ->andWhere('cs.scheduledAt >= :from')
            ->andWhere('cs.scheduledAt <= :to')
            ->andWhere('cs.status = :scheduled')
            ->setParameter('ids', $studentIds)
            ->setParameter('active', Enrollment::STATUS_ACTIVE)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('scheduled', ClassSession::STATUS_SCHEDULED)
            ->orderBy('cs.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextForParent(User $parent): ?ClassSession
    {
        $studentIds = array_map(
            static fn (Student $s): int => (int) $s->getId(),
            $this->getEntityManager()->getRepository(Student::class)->findLinkedForParent($parent)
        );

        if ($studentIds === []) {
            return null;
        }

        $sessions = $this->findUpcomingForStudentIds($studentIds, 60);

        foreach ($sessions as $session) {
            if ($session->getStatus() === ClassSession::STATUS_SCHEDULED) {
                return $session;
            }
        }

        return null;
    }

    /**
     * @param list<int> $studentIds
     *
     * @return list<ClassSession>
     */
    public function findRecentCompletedForStudentIds(array $studentIds, int $limit = 5): array
    {
        if ($studentIds === []) {
            return [];
        }

        return $this->createQueryBuilder('cs')
            ->innerJoin('cs.enrollment', 'e')
            ->innerJoin('e.student', 's')
            ->where('s.id IN (:ids)')
            ->andWhere('cs.status = :completed')
            ->setParameter('ids', $studentIds)
            ->setParameter('completed', ClassSession::STATUS_COMPLETED)
            ->orderBy('cs.updatedAt', 'DESC')
            ->addOrderBy('cs.scheduledAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countCompletedForEnrollment(Enrollment $enrollment): int
    {
        return (int) $this->count([
            'enrollment' => $enrollment,
            'status' => ClassSession::STATUS_COMPLETED,
        ]);
    }

    /**
     * Latest change among enrollments/sessions for parent sync token.
     */
    public function getLatestChangeTimestampForStudentIds(array $studentIds): ?\DateTimeImmutable
    {
        if ($studentIds === []) {
            return null;
        }

        $latest = null;

        $enrollments = $this->getEntityManager()->getRepository(Enrollment::class)->findForStudentIds($studentIds);
        foreach ($enrollments as $enrollment) {
            foreach ([$enrollment->getUpdatedAt(), $enrollment->getApprovedAt(), $enrollment->getRequestedAt()] as $dt) {
                if ($dt !== null && ($latest === null || $dt > $latest)) {
                    $latest = $dt;
                }
            }
        }

        $sessions = $this->findUpcomingForStudentIds($studentIds, 365);
        foreach ($sessions as $session) {
            $candidate = $session->getUpdatedAt() ?? $session->getCreatedAt();
            if ($candidate !== null && ($latest === null || $candidate > $latest)) {
                $latest = $candidate;
            }
        }

        return $latest;
    }
}
