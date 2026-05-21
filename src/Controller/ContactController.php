<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {
        $embed = (string) ($this->getParameter('app.contact_form_embed') ?? '');

        return $this->render('contact/index.html.twig', [
            'contactFormEmbed' => $embed,
        ]);
    }

    #[Route('/contact/thanks', name: 'app_contact_thanks', methods: ['GET'])]
    public function thanks(): Response
    {
        return $this->render('contact/thanks.html.twig');
    }
}

