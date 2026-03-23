<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/courses')]
class CourseController extends AbstractController
{
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(Request $request, CourseRepository $courseRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $search = $request->query->getString('search');

        // All staff can view all courses (but can only edit/delete their own)
        $courses = $courseRepository->findAll();

        // Apply search filter if provided
        if ($search && $search !== '') {
            $courses = array_filter($courses, function($course) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($course->getTitle() ?? ''), $searchLower) ||
                       str_contains(strtolower($course->getDescription() ?? ''), $searchLower) ||
                       str_contains(strtolower($course->getSlug() ?? ''), $searchLower) ||
                       str_contains(strtolower($course->getLevel() ?? ''), $searchLower);
            });
        }

        return $this->render('admin/course/index.html.twig', [
            'courses' => $courses,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $course->setCreatedBy($user);
            }

            // Handle slug
            if (!$course->getSlug()) {
                $slug = $slugger->slug($course->getTitle())->lower();
                $course->setSlug($slug);
            }

            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/courses',
                    $newFilename
                );
                $course->setImage($newFilename);
            }

            $course->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($course);
            $entityManager->flush();

            $activityLogger->log('CREATE', 'course', $course->getTitle(), $course->getId());

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        // All staff can view all courses (no restrictions on viewing)

        return $this->render('admin/course/show.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof User || !$course->getCreatedBy() || $course->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot edit this course because you did not create it.');
                return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $oldImage = $course->getImage();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle slug
            if (!$course->getSlug()) {
                $slug = $slugger->slug($course->getTitle())->lower();
                $course->setSlug($slug);
            }

            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Delete old image
                if ($oldImage) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/' . $oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/courses',
                    $newFilename
                );
                $course->setImage($newFilename);
            }

            $course->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $activityLogger->log('UPDATE', 'course', $course->getTitle(), $course->getId());

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof User || !$course->getCreatedBy() || $course->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot delete this course because you did not create it.');
                return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->getString('_token'))) {
            $courseTitle = $course->getTitle();
            $courseId = $course->getId();

            // Delete image if exists
            if ($course->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/' . $course->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($course);
            $entityManager->flush();

            $activityLogger->log('DELETE', 'course', $courseTitle, $courseId);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    private function assertOwnershipOrAdmin(Course $course): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();
        if ($user instanceof User && $course->getCreatedBy() && $course->getCreatedBy()->getId() === $user->getId()) {
            return;
        }

        $this->addFlash('error', 'You cannot edit or delete this course because you did not create it.');
        throw $this->createAccessDeniedException('You can only manage your own courses.');
    }
}

