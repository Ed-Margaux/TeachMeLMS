<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $search = $request->query->getString('search');

        $categories = $categoryRepository->findAll();

        // Apply search filter if provided
        if ($search && $search !== '') {
            $categories = array_filter($categories, function($category) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($category->getName() ?? ''), $searchLower);
            });
        }

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $category->setCreatedBy($user);
            }
            $entityManager->persist($category);
            $entityManager->flush();

            $activityLogger->log('CREATE', 'category', $category->getName(), $category->getId());

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$category->getCreatedBy() || $category->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot edit this category because you did not create it.');
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $activityLogger->log('UPDATE', 'category', $category->getName(), $category->getId());

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$category->getCreatedBy() || $category->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot delete this category because you did not create it.');
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->getString('_token'))) {
            $categoryName = $category->getName();
            $categoryId = $category->getId();

            $entityManager->remove($category);
            $entityManager->flush();

            $activityLogger->log('DELETE', 'category', $categoryName, $categoryId);
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
