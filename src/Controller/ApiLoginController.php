<?php

namespace App\Controller;

use App\Http\MobileApiResponse;
use App\Repository\UserRepository;
use App\Service\GoogleIdentityTokenVerifier;
use App\Service\GoogleMobileUserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login_check', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data['email']) || empty($data['password'])) {
            return MobileApiResponse::json(
                false,
                'Email and password are required.',
                null,
                ['email' => 'required', 'password' => 'required'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $user = $userRepository->findOneBy(['email' => $data['email']]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return MobileApiResponse::json(
                false,
                'Invalid credentials.',
                null,
                ['credentials' => 'invalid'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        return MobileApiResponse::json(
            true,
            'Login successful.',
            [
                'token' => $jwtManager->create($user),
                'user' => [
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'name' => trim($user->getFirstName().' '.$user->getLastName()),
                    'roles' => $user->getRoles(),
                    'verified' => $user->isEmailVerified(),
                ],
            ],
            []
        );
    }

    #[Route('/api/login/google', name: 'api_login_google', methods: ['POST'])]
    public function googleLogin(
        Request $request,
        GoogleIdentityTokenVerifier $googleTokenVerifier,
        GoogleMobileUserService $googleMobileUserService,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return MobileApiResponse::json(
                false,
                'Invalid JSON body.',
                null,
                ['body' => 'invalid_json'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $googleToken = $data['id_token'] ?? $data['token'] ?? null;
        if (!\is_string($googleToken) || trim($googleToken) === '') {
            return MobileApiResponse::json(
                false,
                'Google token is required.',
                null,
                ['token' => 'required'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $profile = $googleTokenVerifier->verify($googleToken);
            $user = $googleMobileUserService->findOrCreateFromGoogleProfile($profile);
        } catch (\InvalidArgumentException $e) {
            return MobileApiResponse::json(
                false,
                $e->getMessage(),
                null,
                ['token' => 'invalid'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return MobileApiResponse::json(
                false,
                'Failed to verify Google sign-in.',
                null,
                ['google' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return MobileApiResponse::json(
            true,
            'Google login successful.',
            [
                'token' => $jwtManager->create($user),
                'user' => [
                    'email' => $user->getEmail(),
                    'name' => trim($user->getFirstName().' '.$user->getLastName()),
                    'roles' => $user->getRoles(),
                    'verified' => $user->isEmailVerified(),
                ],
            ],
            []
        );
    }
}