<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/user', name: 'app_debug_user')]
    public function debugUser(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new Response('Not logged in', 200);
        }
        
        $roles = $user->getRoles();
        $hasStaff = in_array('ROLE_STAFF', $roles) || in_array('ROLE_ADMIN', $roles);
        $hasAdmin = in_array('ROLE_ADMIN', $roles);
        
        $info = [
            'Email' => $user->getEmail(),
            'Roles' => $roles,
            'Status' => $user->getStatus(),
            'First Name' => $user->getFirstName(),
            'Last Name' => $user->getLastName(),
            'Has ROLE_STAFF or ROLE_ADMIN' => $hasStaff ? 'YES' : 'NO',
            'Has ROLE_ADMIN' => $hasAdmin ? 'YES' : 'NO',
        ];
        
        $html = '<h1>User Debug Info</h1><pre>' . print_r($info, true) . '</pre>';
        $html .= '<p><strong>Can access dashboard:</strong> ' . ($hasStaff ? 'YES' : 'NO') . '</p>';
        $html .= '<p><a href="' . $this->generateUrl('app_admin_dashboard') . '">Try Dashboard</a></p>';
        $html .= '<p><a href="' . $this->generateUrl('app_login') . '">Go to Login</a></p>';
        
        return new Response($html);
    }
}

