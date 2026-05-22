<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: ['/login', '/login/'], name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'google_oauth_client_id' => $this->resolveGoogleOAuthClientId(),
        ]);
    }

    private function resolveGoogleOAuthClientId(): string
    {
        $id = $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? $_SERVER['GOOGLE_OAUTH_CLIENT_ID'] ?? getenv('GOOGLE_OAUTH_CLIENT_ID');

        return is_string($id) ? trim($id, " \t\n\r\0\x0B\"") : '';
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
