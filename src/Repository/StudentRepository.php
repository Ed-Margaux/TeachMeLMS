<?php

namespace App\Repository;

use App\Entity\Student;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Student>
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

    /**
     * Link web-admin students whose parent_email matches this parent login, then return all linked rows.
     *
     * @return list<Student>
     */
    public function findLinkedForParent(User $parent): array
    {
        $parentEmail = strtolower(trim((string) $parent->getEmail()));
        if ($parentEmail === '') {
            return $this->findBy(['parentUser' => $parent], ['updatedAt' => 'DESC', 'createdAt' => 'DESC']);
        }

        $qb = $this->createQueryBuilder('s');
        $qb
            ->where('s.parentUser = :parent')
            ->orWhere('LOWER(s.parentEmail) = :parentEmail')
            ->setParameter('parent', $parent)
            ->setParameter('parentEmail', $parentEmail)
            ->orderBy('s.updatedAt', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countLinkedForParent(User $parent): int
    {
        return \count($this->findLinkedForParent($parent));
    }

    /**
     * Latest change timestamp among students visible to this parent (for sync polling).
     */
    public function getLatestChangeTimestampForParent(User $parent): ?\DateTimeImmutable
    {
        $learners = $this->findLinkedForParent($parent);
        $latest = null;
        foreach ($learners as $learner) {
            $candidate = $learner->getUpdatedAt() ?? $learner->getCreatedAt();
            if ($candidate !== null && ($latest === null || $candidate > $latest)) {
                $latest = $candidate;
            }
        }

        return $latest;
    }
}
