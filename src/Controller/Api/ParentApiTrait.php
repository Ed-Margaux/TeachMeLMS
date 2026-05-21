<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

trait ParentApiTrait
{
    private function requireParentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Staff accounts manage learners on the web admin.');
        }

        return $user;
    }
}
