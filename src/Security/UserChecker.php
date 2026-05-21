<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private function shouldRequireEmailVerification(User $user): bool
    {
        // Only enforce verification for accounts that are in the verification flow.
        // This keeps legacy accounts (created before verification was introduced) usable,
        // while still blocking newly registered accounts until they verify.
        return $user->getEmailVerificationToken() !== null;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() !== 'active') {
            throw new CustomUserMessageAccountStatusException('Your account has been disabled. Please contact an administrator.');
        }

        if ($this->shouldRequireEmailVerification($user) && !$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException('Please verify your email before signing in.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() !== 'active') {
            throw new CustomUserMessageAccountStatusException('Your account has been disabled. Please contact an administrator.');
        }

        if ($this->shouldRequireEmailVerification($user) && !$user->isEmailVerified()) {
            throw new CustomUserMessageAccountStatusException('Please verify your email before signing in.');
        }
    }
}






