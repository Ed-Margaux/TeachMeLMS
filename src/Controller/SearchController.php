<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\CategoryRepository;
use App\Repository\LessonsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/search')]
class SearchController extends AbstractController
{
    #[Route('/autocomplete', name: 'app_search_autocomplete', methods: ['GET'])]
    public function autocomplete(
        Request $request,
        CourseRepository $courseRepository,
        CategoryRepository $categoryRepository,
        LessonsRepository $lessonsRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        $query = $request->query->getString('q', '');
        $query = trim($query);
        
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }
        
        $results = [];
        $queryLower = strtolower($query);
        
        // Search courses
        $courses = $courseRepository->findAll();
        foreach ($courses as $course) {
            $title = $course->getTitle() ?? '';
            $description = $course->getDescription() ?? '';
            $level = $course->getLevel() ?? '';
            
            if (str_contains(strtolower($title), $queryLower) || 
                str_contains(strtolower($description), $queryLower) ||
                str_contains(strtolower($level), $queryLower)) {
                $results[] = [
                    'type' => 'course',
                    'id' => $course->getId(),
                    'title' => $title,
                    'subtitle' => $level ? "Level: {$level}" : 'Course',
                    'url' => $this->generateUrl('app_course_show', ['id' => $course->getId()]),
                    'icon' => 'bi-book',
                ];
            }
        }
        
        // Search categories
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $name = $category->getName() ?? '';
            $description = $category->getDescription() ?? '';
            
            if (str_contains(strtolower($name), $queryLower) || 
                str_contains(strtolower($description), $queryLower)) {
                $results[] = [
                    'type' => 'category',
                    'id' => $category->getId(),
                    'title' => $name,
                    'subtitle' => $description ? substr($description, 0, 50) . '...' : 'Category',
                    'url' => $this->generateUrl('app_category_show', ['id' => $category->getId()]),
                    'icon' => 'bi-folder',
                ];
            }
        }
        
        // Search lessons (sessions)
        $lessons = $lessonsRepository->findAll();
        foreach ($lessons as $lesson) {
            $title = $lesson->getTitle() ?? '';
            $description = $lesson->getDescription() ?? '';
            $courseTitle = $lesson->getCourse() ? $lesson->getCourse()->getTitle() : '';
            
            if (str_contains(strtolower($title), $queryLower) || 
                str_contains(strtolower($description), $queryLower) ||
                str_contains(strtolower($courseTitle), $queryLower)) {
                $results[] = [
                    'type' => 'lesson',
                    'id' => $lesson->getId(),
                    'title' => $title,
                    'subtitle' => $courseTitle ? "Course: {$courseTitle}" : 'Session',
                    'url' => $this->generateUrl('app_lessons_show', ['id' => $lesson->getId()]),
                    'icon' => 'bi-calendar',
                ];
            }
        }
        
        // Limit results to 10
        $results = array_slice($results, 0, 10);
        
        return new JsonResponse($results);
    }
}






