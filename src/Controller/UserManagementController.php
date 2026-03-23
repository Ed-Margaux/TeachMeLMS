<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class UserManagementController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $search = $request->query->getString('search');

        $users = $userRepository->findAll();

        // Apply search filter if provided
        if ($search && $search !== '') {
            $users = array_filter($users, function($user) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($user->getFirstName() ?? ''), $searchLower) ||
                       str_contains(strtolower($user->getLastName() ?? ''), $searchLower) ||
                       str_contains(strtolower($user->getEmail() ?? ''), $searchLower) ||
                       str_contains(strtolower($user->getFullName() ?? ''), $searchLower);
            });
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Roles are automatically converted by the form transformer
            // Hash password
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $activityLogger->log('CREATE', 'user', $user->getFullName(), $user->getId());

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Roles are automatically converted by the form transformer
            // Hash password if provided
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $activityLogger->log('UPDATE', 'user', $user->getFullName(), $user->getId());

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Prevent admin from deleting their own account
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->getString('_token'))) {
            $userName = $user->getFullName();
            $userId = $user->getId();

            $entityManager->remove($user);
            $entityManager->flush();

            $activityLogger->log('DELETE', 'user', $userName, $userId);
        }

        return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
