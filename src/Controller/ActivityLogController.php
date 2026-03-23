<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/logs')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_admin_logs', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $activityLogRepository, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $action = $request->query->getString('action');
        $userParam = $request->query->get('user');
        $userId = null;
        // Handle user filter - check for empty, 'all', or '0' first, then validate integer
        if ($userParam !== null && $userParam !== '' && $userParam !== '0' && strtolower($userParam) !== 'all') {
            $userId = filter_var($userParam, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            // If filter_var returns false or null, set userId to null to avoid errors
            if ($userId === false || $userId === null) {
                $userId = null;
            }
        }
        $dateFrom = $request->query->getString('from');
        $dateTo = $request->query->getString('to');
        $search = $request->query->getString('search');

        $qb = $activityLogRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        if ($action && $action !== '') {
            $qb->andWhere('l.action = :action')->setParameter('action', strtoupper($action));
        }

        if ($userId !== null && $userId > 0) {
            $qb->andWhere('l.user = :userId')->setParameter('userId', $userId);
        }

        if ($dateFrom) {
            try {
                $qb->andWhere('l.createdAt >= :from')->setParameter('from', new \DateTimeImmutable($dateFrom));
            } catch (\Exception) {
                // ignore invalid date filter
            }
        }

        if ($dateTo) {
            try {
                $qb->andWhere('l.createdAt <= :to')->setParameter('to', new \DateTimeImmutable($dateTo . ' 23:59:59'));
            } catch (\Exception) {
                // ignore invalid date filter
            }
        }

        if ($search && $search !== '') {
            $qb->andWhere('(l.targetLabel LIKE :search OR l.targetType LIKE :search OR u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR l.action LIKE :search)')
               ->setParameter('search', '%' . $search . '%');
        }

        $logs = $qb->setMaxResults(300)->getQuery()->getResult();

        // Get the original userParam for display (to show "all" if selected)
        $displayUser = ($userParam === 'all' || $userParam === '' || $userParam === null) ? 'all' : $userId;
        
        return $this->render('admin/logs/index.html.twig', [
            'logs' => $logs,
            'users' => $userRepository->findAll(),
            'filters' => [
                'action' => $action,
                'user' => $displayUser,
                'from' => $dateFrom,
                'to' => $dateTo,
                'search' => $search,
            ],
        ]);
    }
}
