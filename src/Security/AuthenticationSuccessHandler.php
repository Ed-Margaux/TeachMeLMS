<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        
        // Check if user has admin or staff role, redirect to dashboard
        if ($user instanceof User) {
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_STAFF', $roles)) {
                return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
            }
        }
        
        // For regular users, redirect to profile
        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}

