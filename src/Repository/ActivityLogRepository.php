<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Get activity counts grouped by hour for the last 24 hours
     * Returns data for CRUD operations (CREATE, UPDATE, DELETE) and Authentication (LOGIN, LOGOUT)
     */
    public function getActivityTimelineData(int $hours = 24): array
    {
        $now = new \DateTimeImmutable();
        $startTime = $now->modify("-{$hours} hours");
        
        // Get all CRUD activities (CREATE, UPDATE, DELETE) in the time range
        $allCrudActivities = $this->createQueryBuilder('l')
            ->where('l.createdAt >= :startTime')
            ->andWhere('l.action IN (:actions)')
            ->setParameter('startTime', $startTime)
            ->setParameter('actions', ['CREATE', 'UPDATE', 'DELETE'])
            ->getQuery()
            ->getResult();

        // Get all Authentication activities (LOGIN, LOGOUT) in the time range
        $allAuthActivities = $this->createQueryBuilder('l')
            ->where('l.createdAt >= :startTime')
            ->andWhere('l.action IN (:actions)')
            ->setParameter('startTime', $startTime)
            ->setParameter('actions', ['LOGIN', 'LOGOUT'])
            ->getQuery()
            ->getResult();

        // Group activities by hour
        $crudByHour = [];
        $authByHour = [];
        
        foreach ($allCrudActivities as $activity) {
            $hour = (int)$activity->getCreatedAt()->format('H');
            if (!isset($crudByHour[$hour])) {
                $crudByHour[$hour] = 0;
            }
            $crudByHour[$hour]++;
        }
        
        foreach ($allAuthActivities as $activity) {
            $hour = (int)$activity->getCreatedAt()->format('H');
            if (!isset($authByHour[$hour])) {
                $authByHour[$hour] = 0;
            }
            $authByHour[$hour]++;
        }

        // Create arrays for all hours in the range
        $crudData = [];
        $authData = [];
        $categories = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $dateTime = $startTime->modify("+{$i} hours");
            $hour = (int)$dateTime->format('H');
            $categories[] = $dateTime->format('Y-m-d\TH:i:s.000\Z');
            
            // Get count for this hour
            $crudData[] = $crudByHour[$hour] ?? 0;
            $authData[] = $authByHour[$hour] ?? 0;
        }

        return [
            'categories' => $categories,
            'crudData' => $crudData,
            'authData' => $authData,
        ];
    }

    /**
     * Get student and tutor creation counts grouped by hour for the last 24 hours
     * Returns data for students and tutors added over time based on their creation dates
     */
    public function getStudentsAndTutorsTimelineData(int $hours = 24): array
    {
        $now = new \DateTimeImmutable();
        $startTime = $now->modify("-{$hours} hours");
        
        $em = $this->getEntityManager();
        
        // Get all students created in the time range
        $studentQuery = $em->createQuery('
            SELECT s.createdAt 
            FROM App\Entity\Student s 
            WHERE s.createdAt >= :startTime
        ');
        $studentQuery->setParameter('startTime', $startTime);
        $studentResults = $studentQuery->getResult();

        // Get all tutors created in the time range
        $tutorQuery = $em->createQuery('
            SELECT t.createdAt 
            FROM App\Entity\Tutor t 
            WHERE t.createdAt >= :startTime
        ');
        $tutorQuery->setParameter('startTime', $startTime);
        $tutorResults = $tutorQuery->getResult();

        // Group by hour
        $studentsByHour = [];
        $tutorsByHour = [];
        
        foreach ($studentResults as $result) {
            $createdAt = $result['createdAt'];
            if ($createdAt instanceof \DateTimeImmutable) {
                $hour = (int)$createdAt->format('H');
                if (!isset($studentsByHour[$hour])) {
                    $studentsByHour[$hour] = 0;
                }
                $studentsByHour[$hour]++;
            }
        }
        
        foreach ($tutorResults as $result) {
            $createdAt = $result['createdAt'];
            if ($createdAt instanceof \DateTimeImmutable) {
                $hour = (int)$createdAt->format('H');
                if (!isset($tutorsByHour[$hour])) {
                    $tutorsByHour[$hour] = 0;
                }
                $tutorsByHour[$hour]++;
            }
        }

        // Create arrays for all hours in the range
        $studentsData = [];
        $tutorsData = [];
        $categories = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $dateTime = $startTime->modify("+{$i} hours");
            $hour = (int)$dateTime->format('H');
            $categories[] = $dateTime->format('Y-m-d\TH:i:s.000\Z');
            
            // Get count for this hour
            $studentsData[] = $studentsByHour[$hour] ?? 0;
            $tutorsData[] = $tutorsByHour[$hour] ?? 0;
        }

        return [
            'categories' => $categories,
            'studentsData' => $studentsData,
            'tutorsData' => $tutorsData,
        ];
    }
}
