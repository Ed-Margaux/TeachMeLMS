<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage
    ): Response {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->render('registration/register.html.twig', [
                    'form' => $form,
                ]);
            }

            // Hash password
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Set default status to active
            $user->setStatus('active');

            // Set role to Staff for all new registrations
            $user->setRoles(['ROLE_STAFF']);

            // Set createdAt timestamp
            $user->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            // Automatically log in the user after registration
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);

            $this->addFlash('success', 'Registration successful! Welcome to Teach Me.');

            // Redirect to dashboard
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }
}

