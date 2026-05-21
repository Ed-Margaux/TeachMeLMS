<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Finds or creates a Teach Me user from Google profile data (mobile app login).
 */
final class GoogleMobileUserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @param array{email: string, firstName: string, lastName: string} $profile
     */
    public function findOrCreateFromGoogleProfile(array $profile): User
    {
        $user = $this->users->findOneBy(['email' => $profile['email']]);

        if ($user) {
            if (!$user->isEmailVerified()) {
                $user->setIsEmailVerified(true);
                $user->setEmailVerifiedAt(new \DateTimeImmutable());
                $this->em->flush();
            }

            return $user;
        }

        $user = new User();
        $user->setEmail($profile['email']);
        $user->setFirstName($profile['firstName']);
        $user->setLastName($profile['lastName']);
        $user->setRoles(['ROLE_USER']);
        $user->setStatus('active');
        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
