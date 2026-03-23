<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public function log(
        string $action,
        string $targetType,
        ?string $targetLabel = null,
        ?int $targetId = null,
        ?User $actor = null
    ): void {
        $user = $actor ?? $this->security->getUser();

        $log = new ActivityLog();
        if ($user instanceof User) {
            $log->setUser($user);
            $log->setRole($this->getPrimaryRole($user));
        } else {
            $log->setRole('ROLE_ANONYMOUS');
        }

        $log->setAction($action);
        $log->setTargetType($targetType);
        $log->setTargetLabel($targetLabel);
        $log->setTargetId($targetId);
        
        // Explicitly set the current timestamp with timezone
        $log->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get())));

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function getPrimaryRole(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'ROLE_ADMIN';
        }
        if (in_array('ROLE_STAFF', $roles, true)) {
            return 'ROLE_STAFF';
        }

        return 'ROLE_USER';
    }
}
