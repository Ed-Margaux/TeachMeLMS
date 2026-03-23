<?php

namespace App\Controller;

use App\Entity\Tutor;
use App\Form\TutorType;
use App\Repository\TutorRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/tutors')]
class TutorController extends AbstractController
{
    #[Route('/', name: 'app_tutor_index', methods: ['GET'])]
    public function index(Request $request, TutorRepository $tutorRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $search = $request->query->getString('search');

        $allTutors = $tutorRepository->findAll();
        $totalTutors = count($allTutors);
        
        // Get unique specialties count
        $specialties = array_unique(array_filter(array_map(function($tutor) {
            return $tutor->getSpecialty();
        }, $allTutors)));
        $totalSpecialties = count($specialties);
        
        // Get unique specialties list for filter dropdown
        $uniqueSpecialties = array_values($specialties);

        // Apply search filter if provided
        $tutors = $allTutors;
        if ($search && $search !== '') {
            $tutors = array_filter($tutors, function($tutor) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($tutor->getFirstName() ?? ''), $searchLower) ||
                       str_contains(strtolower($tutor->getLastName() ?? ''), $searchLower) ||
                       str_contains(strtolower($tutor->getEmail() ?? ''), $searchLower) ||
                       str_contains(strtolower($tutor->getPhone() ?? ''), $searchLower) ||
                       str_contains(strtolower($tutor->getSpecialty() ?? ''), $searchLower);
            });
        }

        return $this->render('admin/tutor/index.html.twig', [
            'tutors' => $tutors,
            'search' => $search,
            'totalTutors' => $totalTutors,
            'totalSpecialties' => $totalSpecialties,
            'specialties' => $uniqueSpecialties,
        ]);
    }

    #[Route('/new', name: 'app_tutor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $tutor = new Tutor();
        $form = $this->createForm(TutorType::class, $tutor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $tutor->setCreatedBy($user);
            }
            
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/tutors',
                    $newFilename
                );
                $tutor->setImage($newFilename);
            }

            $tutor->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($tutor);
            $entityManager->flush();

            $activityLogger->log('CREATE', 'tutor', $tutor->getFullName(), $tutor->getId());

            return $this->redirectToRoute('app_tutor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tutor/new.html.twig', [
            'tutor' => $tutor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tutor_show', methods: ['GET'])]
    public function show(Tutor $tutor): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        return $this->render('admin/tutor/show.html.twig', [
            'tutor' => $tutor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tutor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tutor $tutor, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$tutor->getCreatedBy() || $tutor->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot edit this tutor because you did not create it.');
                return $this->redirectToRoute('app_tutor_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $oldImage = $tutor->getImage();
        $form = $this->createForm(TutorType::class, $tutor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Delete old image
                if ($oldImage) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/tutors/' . $oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/tutors',
                    $newFilename
                );
                $tutor->setImage($newFilename);
            }

            $tutor->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $activityLogger->log('UPDATE', 'tutor', $tutor->getFullName(), $tutor->getId());

            return $this->redirectToRoute('app_tutor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tutor/edit.html.twig', [
            'tutor' => $tutor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tutor_delete', methods: ['POST'])]
    public function delete(Request $request, Tutor $tutor, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$tutor->getCreatedBy() || $tutor->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot delete this tutor because you did not create it.');
                return $this->redirectToRoute('app_tutor_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        if ($this->isCsrfTokenValid('delete' . $tutor->getId(), $request->request->getString('_token'))) {
            $tutorName = $tutor->getFullName();
            $tutorId = $tutor->getId();

            // Delete image if exists
            if ($tutor->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/tutors/' . $tutor->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($tutor);
            $entityManager->flush();

            $activityLogger->log('DELETE', 'tutor', $tutorName, $tutorId);
        }

        return $this->redirectToRoute('app_tutor_index', [], Response::HTTP_SEE_OTHER);
    }
}
