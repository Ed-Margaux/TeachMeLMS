<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Http\MobileApiResponse;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use App\Service\ParentLearnerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationApiController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        EmailVerificationService $verification,
        ParentLearnerService $parentLearners,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return MobileApiResponse::json(
                false,
                'Invalid JSON body.',
                null,
                ['body' => 'invalid_json'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $firstName = trim((string) ($data['firstName'] ?? ''));
        $lastName = trim((string) ($data['lastName'] ?? ''));
        $learner = \is_array($data['learner'] ?? null) ? $data['learner'] : [];
        $learnerFirstName = trim((string) ($learner['firstName'] ?? ''));
        $learnerLastName = trim((string) ($learner['lastName'] ?? ''));
        $learnerGrade = trim((string) ($learner['grade'] ?? '')) ?: null;

        $errors = [];
        if ($email === '') $errors['email'] = 'required';
        if ($password === '') $errors['password'] = 'required';
        if ($firstName === '') $errors['firstName'] = 'required';
        if ($lastName === '') $errors['lastName'] = 'required';
        if ($learnerFirstName === '') $errors['learner.firstName'] = 'required';
        if ($learnerLastName === '') $errors['learner.lastName'] = 'required';

        if ($errors) {
            return MobileApiResponse::json(
                false,
                'Validation failed.',
                null,
                $errors,
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        if ($users->findOneBy(['email' => $email])) {
            return MobileApiResponse::json(
                false,
                'An account with this email already exists.',
                null,
                ['email' => 'already_exists'],
                JsonResponse::HTTP_CONFLICT
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setStatus('active');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        $learnerEntity = $parentLearners->createLearnerForParent(
            $user,
            $learnerFirstName,
            $learnerLastName,
            $learnerGrade,
        );

        $verification->beginVerification($user);

        return MobileApiResponse::json(
            true,
            'Parent account created. Please verify your email.',
            [
                'email' => $user->getEmail(),
                'accountType' => 'parent',
                'learner' => [
                    'id' => $learnerEntity->getId(),
                    'firstName' => $learnerEntity->getFirstName(),
                    'lastName' => $learnerEntity->getLastName(),
                    'fullName' => $learnerEntity->getFullName(),
                    'grade' => $learnerEntity->getGrade(),
                ],
            ],
            [],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = is_array($data) ? (string) ($data['token'] ?? '') : '';

        $user = $verification->verifyByToken($token);
        if (!$user) {
            return MobileApiResponse::json(
                false,
                'Invalid or expired token.',
                null,
                ['token' => 'invalid_or_expired'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return MobileApiResponse::json(
            true,
            'Email verified.',
            [
                'email' => $user->getEmail(),
                'verified' => $user->isEmailVerified(),
            ],
            []
        );
    }
}

