<?php

namespace App\Controller;

use App\Entity\Lessons;
use App\Form\LessonsType;
use App\Repository\LessonsRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lessons')]
class LessonsController extends AbstractController
{
    #[Route(name: 'app_lessons_index', methods: ['GET'])]
    public function index(Request $request, LessonsRepository $lessonsRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $search = $request->query->getString('search');

        $lessons = $lessonsRepository->findAll();

        // Apply search filter if provided
        if ($search && $search !== '') {
            $lessons = array_filter($lessons, function($lesson) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($lesson->getTitle() ?? ''), $searchLower) ||
                       str_contains(strtolower($lesson->getDescription() ?? ''), $searchLower) ||
                       ($lesson->getCourse() && str_contains(strtolower($lesson->getCourse()->getTitle() ?? ''), $searchLower));
            });
        }

        return $this->render('admin/lessons/index.html.twig', [
            'lessons' => $lessons,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_lessons_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $lesson = new Lessons();
        $form = $this->createForm(LessonsType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $lesson->setCreatedBy($user);
            }
            $entityManager->persist($lesson);
            $entityManager->flush();

            $activityLogger->log('CREATE', 'lesson', $lesson->getTitle(), $lesson->getId());

            return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/lessons/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lessons_show', methods: ['GET'])]
    public function show(Lessons $lesson): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        return $this->render('admin/lessons/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lessons_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lessons $lesson, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$lesson->getCreatedBy() || $lesson->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot edit this lesson because you did not create it.');
                return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        
        $form = $this->createForm(LessonsType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $activityLogger->log('UPDATE', 'lesson', $lesson->getTitle(), $lesson->getId());

            return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/lessons/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lessons_delete', methods: ['POST'])]
    public function delete(Request $request, Lessons $lesson, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$lesson->getCreatedBy() || $lesson->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot delete this lesson because you did not create it.');
                return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->request->getString('_token'))) {
            $lessonTitle = $lesson->getTitle();
            $lessonId = $lesson->getId();

            $entityManager->remove($lesson);
            $entityManager->flush();
            
            $activityLogger->log('DELETE', 'lesson', $lessonTitle, $lessonId);
        }

        return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
    }
}
