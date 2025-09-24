<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LearninglmController extends AbstractController
{
    #[Route('/learninglm', name: 'app_learninglm')]
    public function index(): Response
    {
        return $this->render('learninglm/index.html.twig', [
            'controller_name' => 'LearninglmController',
        ]);
    }
}
