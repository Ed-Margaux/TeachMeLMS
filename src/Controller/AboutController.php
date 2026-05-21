<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function index(): Response
    {
        $team = [
            ['name' => 'Jennie', 'position' => 'Curriculum & learning design', 'image' => 'img/team/jennie.jpg'],
            ['name' => 'Jisoo', 'position' => 'Community & partnerships', 'image' => 'img/team/jisoo.jpg'],
            ['name' => 'Lisa', 'position' => 'Student experience', 'image' => 'img/team/lisa.jpg'],
            ['name' => 'Rosé', 'position' => 'Brand & creative', 'image' => 'img/team/rose.jpg'],
        ];

        return $this->render('about/index.html.twig', [
            'team' => $team,
        ]);
    }
}

