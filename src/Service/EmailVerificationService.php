<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class EmailVerificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Generate a unique verification token.
     */
    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Send verification email to user.
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(Address::create($this->mailerFrom))
            ->to((string) $user->getEmail())
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Create token, persist expiry, send mail (used by web + API registration).
     */
    public function beginVerification(User $user): void
    {
        $token = $this->generateVerificationToken();
        $expiresAt = (new \DateTimeImmutable())->modify('+1 day');

        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt($expiresAt);
        $user->setIsEmailVerified(false);
        $user->setEmailVerifiedAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $verificationUrl = $this->router->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->sendVerificationEmail($user, $verificationUrl);
    }

    /**
     * Verify a token and mark user as verified.
     */
    public function verifyToken(string $token): ?User
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $expiresAt = $user->getEmailVerificationTokenExpiresAt();
        if ($expiresAt === null || $expiresAt < new \DateTimeImmutable()) {
            return null;
        }

        $user->setIsEmailVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $this->entityManager->flush();

        return $user;
    }

    public function verifyByToken(string $token): ?User
    {
        return $this->verifyToken($token);
    }

    public function needsVerification(User $user): bool
    {
        return !$user->isEmailVerified();
    }
}
