<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Redirect to admin dashboard if user is authenticated, otherwise to login
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        return $this->redirectToRoute('app_login');
    }
}


