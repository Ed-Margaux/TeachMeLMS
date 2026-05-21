<?php

namespace App\Controller\Api;

use App\Http\MobileApiResponse;
use App\Repository\CourseRepository;
use App\Service\EnrollmentApiPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CourseApiController extends AbstractController
{
    #[Route('/api/courses', name: 'api_courses_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(CourseRepository $courses): JsonResponse
    {
        $items = [];
        foreach ($courses->findAll() as $course) {
            $items[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'level' => $course->getLevel(),
                'image' => $course->getImage(),
                'createdAt' => $course->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        return MobileApiResponse::json(true, 'Courses list.', $items, []);
    }

    #[Route('/api/courses/{id}', name: 'api_courses_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id, CourseRepository $courses, EnrollmentApiPresenter $presenter): JsonResponse
    {
        $course = $courses->find($id);
        if ($course === null) {
            return MobileApiResponse::json(false, 'Course not found.', null, [], Response::HTTP_NOT_FOUND);
        }

        return MobileApiResponse::json(true, 'Course detail.', $presenter->courseDetail($course), []);
    }
}

