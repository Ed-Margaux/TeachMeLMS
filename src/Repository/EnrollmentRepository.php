<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    /**
     * @param list<int> $studentIds
     *
     * @return list<Enrollment>
     */
    public function findForStudentIds(array $studentIds, ?string $status = null): array
    {
        if ($studentIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.student', 's')
            ->addSelect('s', 'c', 't')
            ->innerJoin('e.course', 'c')
            ->leftJoin('e.tutor', 't')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $studentIds)
            ->orderBy('e.requestedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Enrollment>
     */
    public function findForParent(User $parent, ?string $status = null): array
    {
        $studentIds = array_map(
            static fn (Student $s): int => (int) $s->getId(),
            $this->getEntityManager()->getRepository(Student::class)->findLinkedForParent($parent)
        );

        return $this->findForStudentIds($studentIds, $status);
    }

    public function countPendingForStudentIds(array $studentIds): int
    {
        if ($studentIds === []) {
            return 0;
        }

        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.student', 's')
            ->where('s.id IN (:ids)')
            ->andWhere('e.status = :pending')
            ->setParameter('ids', $studentIds)
            ->setParameter('pending', Enrollment::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasPendingForStudentCourse(int $studentId, int $courseId): bool
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.student = :student')
            ->andWhere('e.course = :course')
            ->andWhere('e.status = :pending')
            ->setParameter('student', $studentId)
            ->setParameter('course', $courseId)
            ->setParameter('pending', Enrollment::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return list<Enrollment>
     */
    public function findAllForAdmin(?string $status = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.student', 's')
            ->addSelect('s', 'c', 't')
            ->innerJoin('e.course', 'c')
            ->leftJoin('e.tutor', 't')
            ->orderBy('e.requestedAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->count(['status' => $status]);
    }
}
