<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class GoogleUserProvider implements UserProviderInterface, OAuthAwareUserProviderInterface
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $email = $response->getEmail();
        if (!\is_string($email) || $email === '') {
            throw new UserNotFoundException('Google account has no email address.');
        }

        $user = $this->users->findOneBy(['email' => $email]);

        if (!$user) {
            $firstName = trim((string) ($response->getFirstName() ?? ''));
            $lastName = trim((string) ($response->getLastName() ?? ''));
            $realName = trim((string) ($response->getRealName() ?? ''));

            if ($firstName === '' && $realName !== '') {
                $firstName = explode(' ', $realName)[0];
            }
            if ($firstName === '') {
                $firstName = 'Staff';
            }
            if ($lastName === '') {
                $lastName = 'User';
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles(['ROLE_STAFF']);
            $user->setStatus('active');
            $user->setIsEmailVerified(true);
            $user->setEmailVerifiedAt(new \DateTimeImmutable());
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));

            $this->em->persist($user);
        } elseif (!$user->isEmailVerified()) {
            $user->setIsEmailVerified(true);
            $user->setEmailVerifiedAt(new \DateTimeImmutable());
        }

        $this->em->flush();

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findOneBy(['email' => $identifier]);
        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
