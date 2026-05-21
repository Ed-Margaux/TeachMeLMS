<?php

namespace App\Service;

use App\Entity\Student;
use App\Entity\User;
use App\Repository\StudentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ParentLearnerService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StudentRepository $students,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * Attach web-admin student rows to the parent account when parent_email matches.
     */
    public function syncWebStudentsToParent(User $parent): int
    {
        $parentEmail = strtolower(trim((string) $parent->getEmail()));
        if ($parentEmail === '') {
            return 0;
        }

        $linked = 0;
        $candidates = $this->students->createQueryBuilder('s')
            ->where('LOWER(s.parentEmail) = :email')
            ->andWhere('s.parentUser IS NULL OR s.parentUser = :parent')
            ->setParameter('email', $parentEmail)
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getResult();

        foreach ($candidates as $student) {
            if (!$student instanceof Student) {
                continue;
            }
            $student->setParentUser($parent);
            $student->setUpdatedAt(new \DateTimeImmutable());
            ++$linked;
        }

        if ($linked > 0) {
            $this->em->flush();
        }

        return $linked;
    }

    public function createLearnerForParent(
        User $parent,
        string $firstName,
        string $lastName,
        ?string $grade = null,
    ): Student {
        $learner = new Student();
        $learner->setFirstName($firstName);
        $learner->setLastName($lastName);
        $learner->setGrade($grade);
        $learner->setParentUser($parent);
        $learner->setParentEmail($parent->getEmail());
        $learner->setCreatedBy($parent);
        $learner->setCreatedAt(new \DateTimeImmutable());
        $learner->setUpdatedAt(new \DateTimeImmutable());
        $learner->setEmail($this->generatePlaceholderEmail($parent));

        $this->em->persist($learner);
        $this->em->flush();

        return $learner;
    }

    /**
     * @return list<Student>
     */
    public function getLearnersForParent(User $parent, bool $runSync = true): array
    {
        if ($runSync) {
            $this->syncWebStudentsToParent($parent);
        }

        return $this->students->findLinkedForParent($parent);
    }

    public function linkParentFromEmailField(Student $student): void
    {
        $email = $student->getParentEmail();
        if ($email === null || $email === '') {
            return;
        }

        $parent = $this->users->findOneBy(['email' => $email]);
        if ($parent === null) {
            return;
        }

        $student->setParentUser($parent);
        $student->setUpdatedAt(new \DateTimeImmutable());
    }

    public function parentOwnsLearner(User $parent, Student $learner): bool
    {
        $this->syncWebStudentsToParent($parent);
        $owner = $learner->getParentUser();

        return $owner !== null && $owner->getId() === $parent->getId();
    }

    public function getSyncTokenForParent(User $parent): string
    {
        $this->syncWebStudentsToParent($parent);
        $latest = $this->students->getLatestChangeTimestampForParent($parent);
        $count = $this->students->countLinkedForParent($parent);

        return hash('crc32b', $count.':'.($latest?->format('U') ?? '0'));
    }

    private function generatePlaceholderEmail(User $parent): string
    {
        $parentId = $parent->getId() ?? 0;
        $token = bin2hex(random_bytes(4));

        return sprintf('learner.%d.%s@parents.teachme', $parentId, $token);
    }
}
