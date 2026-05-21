<?php

namespace App\Controller;

use App\Entity\ClassSession;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Form\ClassSessionType;
use App\Form\EnrollmentApproveType;
use App\Form\EnrollmentRejectType;
use App\Repository\EnrollmentRepository;
use App\Service\ActivityLogger;
use App\Service\EnrollmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/enrollments')]
class EnrollmentController extends AbstractController
{
    #[Route('/', name: 'app_enrollment_index', methods: ['GET'])]
    public function index(Request $request, EnrollmentRepository $enrollmentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $status = $request->query->getString('status');
        $statusFilter = $status !== '' ? $status : null;

        $viewData = [
            'enrollments' => $enrollmentRepository->findAllForAdmin($statusFilter),
            'status' => $status,
            'pendingCount' => $enrollmentRepository->countByStatus(Enrollment::STATUS_PENDING),
        ];

        if ($request->query->getBoolean('partial')) {
            return $this->render('admin/enrollment/_list_content.html.twig', $viewData);
        }

        return $this->render('admin/enrollment/index.html.twig', $viewData);
    }

    #[Route('/{id}', name: 'app_enrollment_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Request $request, Enrollment $enrollment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        if ($request->query->getBoolean('partial')) {
            return $this->render('admin/enrollment/_show_sync_content.html.twig', [
                'enrollment' => $enrollment,
            ]);
        }

        return $this->render('admin/enrollment/show.html.twig', [
            'enrollment' => $enrollment,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_enrollment_approve', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function approve(
        Request $request,
        Enrollment $enrollment,
        EnrollmentService $enrollmentService,
        ActivityLogger $activityLogger,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        if ($enrollment->getStatus() !== Enrollment::STATUS_PENDING) {
            $this->addFlash('error', 'Only pending requests can be approved.');

            return $this->redirectToRoute('app_enrollment_show', ['id' => $enrollment->getId()]);
        }

        $form = $this->createForm(EnrollmentApproveType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $tutor = $data['tutor'];
            $user = $this->getUser();
            if ($user instanceof User) {
                $enrollmentService->approve($enrollment, $user, $tutor, $data['staffNote'] ?? null);
            }
            $activityLogger->log(
                'UPDATE',
                'enrollment',
                sprintf('Approved: %s', $enrollment->getCourse()?->getTitle()),
                $enrollment->getId()
            );
            $this->addFlash('success', 'Enrollment approved. Add class sessions with the online meeting link.');

            return $this->redirectToRoute('app_enrollment_show', ['id' => $enrollment->getId()]);
        }

        return $this->render('admin/enrollment/approve.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/reject', name: 'app_enrollment_reject', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function reject(
        Request $request,
        Enrollment $enrollment,
        EnrollmentService $enrollmentService,
        ActivityLogger $activityLogger,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        if ($enrollment->getStatus() !== Enrollment::STATUS_PENDING) {
            $this->addFlash('error', 'Only pending requests can be rejected.');

            return $this->redirectToRoute('app_enrollment_show', ['id' => $enrollment->getId()]);
        }

        $form = $this->createForm(EnrollmentRejectType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $this->getUser();
            if ($user instanceof User) {
                $enrollmentService->reject($enrollment, $user, (string) $data['staffNote']);
            }
            $activityLogger->log('UPDATE', 'enrollment', 'Rejected enrollment request', $enrollment->getId());
            $this->addFlash('success', 'Enrollment request rejected.');

            return $this->redirectToRoute('app_enrollment_index', ['status' => Enrollment::STATUS_PENDING]);
        }

        return $this->render('admin/enrollment/reject.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/sessions/new', name: 'app_enrollment_session_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function newSession(
        Request $request,
        Enrollment $enrollment,
        EnrollmentService $enrollmentService,
        ActivityLogger $activityLogger,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        if ($enrollment->getStatus() !== Enrollment::STATUS_ACTIVE) {
            $this->addFlash('error', 'Schedule sessions only for active enrollments.');

            return $this->redirectToRoute('app_enrollment_show', ['id' => $enrollment->getId()]);
        }

        $session = new ClassSession();
        $courseTitle = $enrollment->getCourse()?->getTitle() ?? 'Class';
        $session->setTitle($courseTitle.' – Session');
        $session->setScheduledAt(new \DateTimeImmutable('+1 day 16:00'));

        $form = $this->createForm(ClassSessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $session->setEnrollment($enrollment);
                $session->setCreatedBy($user);
                $session->setStatus(ClassSession::STATUS_SCHEDULED);
                $enrollment->addClassSession($session);
                $enrollment->touch();
                $enrollmentService->persistClassSession($session);
            }
            $activityLogger->log('CREATE', 'class_session', (string) $session->getTitle(), $enrollment->getId());
            $this->addFlash('success', 'Class session scheduled.');

            return $this->redirectToRoute('app_enrollment_show', ['id' => $enrollment->getId()]);
        }

        return $this->render('admin/enrollment/session_new.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/sessions/{id}/complete', name: 'app_class_session_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function completeSession(
        Request $request,
        ClassSession $session,
        EnrollmentService $enrollmentService,
        ActivityLogger $activityLogger,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $token = new CsrfToken('complete'.$session->getId(), $request->request->getString('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $enrollmentId = $session->getEnrollment()?->getId();
        $enrollmentService->markSessionCompleted($session);
        $activityLogger->log('UPDATE', 'class_session', 'Marked completed', $session->getId());
        $this->addFlash('success', 'Session marked as completed.');

        return $this->redirectToRoute('app_enrollment_show', ['id' => $enrollmentId]);
    }
}
