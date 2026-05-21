<?php

namespace App\Controller\Api;

use App\Http\MobileApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CustomerMeApiController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(\App\Service\ParentLearnerService $parentLearners): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return MobileApiResponse::json(false, 'Unauthorized.', null, [], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $isStaff = $this->isGranted('ROLE_STAFF');
        $learners = $isStaff ? [] : $parentLearners->getLearnersForParent($user);

        return MobileApiResponse::json(true, 'Profile loaded.', [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'name' => trim($user->getFirstName().' '.$user->getLastName()),
            'roles' => $user->getRoles(),
            'verified' => $user->isEmailVerified(),
            'isStaff' => $isStaff,
            'accountType' => $isStaff ? 'staff' : 'parent',
            'learnersCount' => \count($learners),
        ], []);
    }
}
