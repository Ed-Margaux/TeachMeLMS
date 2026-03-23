<?php

namespace App\Controller;

use App\Entity\Student;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/students')]
class StudentController extends AbstractController
{
    #[Route('/', name: 'app_student_index', methods: ['GET'])]
    public function index(Request $request, StudentRepository $studentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $search = $request->query->getString('search');

        $allStudents = $studentRepository->findAll();
        $totalStudents = count($allStudents);
        
        // Get unique grades count
        $grades = array_unique(array_filter(array_map(function($student) {
            return $student->getGrade();
        }, $allStudents)));
        $totalGrades = count($grades);
        
        // Get unique grades list for filter dropdown
        $uniqueGrades = array_values($grades);

        // Apply search filter if provided
        $students = $allStudents;
        if ($search && $search !== '') {
            $students = array_filter($students, function($student) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($student->getFirstName() ?? ''), $searchLower) ||
                       str_contains(strtolower($student->getLastName() ?? ''), $searchLower) ||
                       str_contains(strtolower($student->getEmail() ?? ''), $searchLower) ||
                       str_contains(strtolower($student->getPhone() ?? ''), $searchLower) ||
                       str_contains(strtolower($student->getGrade() ?? ''), $searchLower);
            });
        }

        return $this->render('student/index.html.twig', [
            'students' => $students,
            'search' => $search,
            'totalStudents' => $totalStudents,
            'totalGrades' => $totalGrades,
            'grades' => $uniqueGrades,
        ]);
    }

    #[Route('/new', name: 'app_student_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $student->setCreatedBy($user);
            }
            
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/students',
                    $newFilename
                );
                $student->setImage($newFilename);
            }

            $student->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($student);
            $entityManager->flush();
            
            $activityLogger->log('CREATE', 'student', $student->getFullName(), $student->getId());

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    public function show(Student $student): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        return $this->render('student/show.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Student $student, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$student->getCreatedBy() || $student->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot edit this student because you did not create it.');
                return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $oldImage = $student->getImage();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Delete old image
                if ($oldImage) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/students/' . $oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename)->lower();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/students',
                    $newFilename
                );
                $student->setImage($newFilename);
            }

            $student->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            $activityLogger->log('UPDATE', 'student', $student->getFullName(), $student->getId());

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_delete', methods: ['POST'])]
    public function delete(Request $request, Student $student, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Check ownership for staff
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User || !$student->getCreatedBy() || $student->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('error', 'You cannot delete this student because you did not create it.');
                return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        if ($this->isCsrfTokenValid('delete' . $student->getId(), $request->request->getString('_token'))) {
            $studentName = $student->getFullName();
            $studentId = $student->getId();

            // Delete image if exists
            if ($student->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/students/' . $student->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($student);
            $entityManager->flush();
            
            $activityLogger->log('DELETE', 'student', $studentName, $studentId);
        }

        return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
    }
}
