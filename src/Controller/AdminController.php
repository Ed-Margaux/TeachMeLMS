<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\CourseRepository;
use App\Repository\StudentRepository;
use App\Repository\TutorRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        ActivityLogRepository $activityLogRepository,
        CourseRepository $courseRepository,
        StudentRepository $studentRepository,
        TutorRepository $tutorRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // TEMPORARILY: Use mock data for UI preview (no database required)
        try {
            $totalUsers = $userRepository->count([]);
            $totalStaff = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_STAFF%')
                ->getQuery()
                ->getSingleScalarResult();

            // Calculate total records (courses + students + tutors)
        $totalCourses = $courseRepository->count([]);
        $totalStudents = $studentRepository->count([]);
            $totalTutors = $tutorRepository->count([]);
            $totalRecords = $totalCourses + $totalStudents + $totalTutors;

            // Get active users count
            $activeUsers = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.status = :status')
                ->setParameter('status', 'active')
                ->getQuery()
                ->getSingleScalarResult();

            // Get recent activities
            $recentActivities = $activityLogRepository->createQueryBuilder('l')
                ->leftJoin('l.user', 'u')
                ->addSelect('u')
                ->orderBy('l.createdAt', 'DESC')
                ->setMaxResults(10)
            ->getQuery()
            ->getResult();

            // Get today's activities count
            $todayActivities = $activityLogRepository->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.createdAt >= :today')
                ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
                ->getSingleScalarResult();

            // Get students and tutors timeline data for the chart (last 24 hours)
            $timelineData = $activityLogRepository->getStudentsAndTutorsTimelineData(24);
        } catch (\Exception $e) {
            // If database connection fails, use mock data for UI preview
            $totalUsers = 25;
            $totalStaff = 8;
            $activeUsers = 23;
            $totalCourses = 12;
            $totalStudents = 45;
            $totalTutors = 18;
            $totalRecords = $totalCourses + $totalStudents + $totalTutors;
            $todayActivities = 5;
            $recentActivities = [];
            
            // Mock timeline data
            $timelineData = [
                'categories' => [],
                'studentsData' => [0, 0, 0, 0, 0, 0, 0],
                'tutorsData' => [0, 0, 0, 0, 0, 0, 0],
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'activeUsers' => $activeUsers,
            'totalRecords' => $totalRecords,
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'totalTutors' => $totalTutors,
            'todayActivities' => $todayActivities,
            'recentActivities' => $recentActivities,
            'timelineData' => $timelineData,
        ]);
    }

    #[Route('/settings', name: 'app_admin_settings', methods: ['GET'])]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->redirectToRoute('app_profile');
    }
}
