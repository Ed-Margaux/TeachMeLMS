<?php

namespace App\Controller\Api;

use App\Http\MobileApiResponse;
use App\Repository\TutorRepository;
use App\Service\EnrollmentApiPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TutorApiController extends AbstractController
{
    #[Route('/api/tutors', name: 'api_tutors_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(TutorRepository $tutors): JsonResponse
    {
        $items = [];
        foreach ($tutors->findAll() as $tutor) {
            $items[] = [
                'id' => $tutor->getId(),
                'fullName' => $tutor->getFullName(),
                'email' => $tutor->getEmail(),
                'phone' => $tutor->getPhone(),
                'specialty' => $tutor->getSpecialty(),
                'image' => $tutor->getImage(),
                'createdAt' => $tutor->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        return MobileApiResponse::json(true, 'Tutors list.', $items, []);
    }

    #[Route('/api/tutors/{id}', name: 'api_tutors_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id, TutorRepository $tutors, EnrollmentApiPresenter $presenter): JsonResponse
    {
        $tutor = $tutors->find($id);
        if ($tutor === null) {
            return MobileApiResponse::json(false, 'Tutor not found.', null, [], Response::HTTP_NOT_FOUND);
        }

        return MobileApiResponse::json(true, 'Tutor detail.', $presenter->tutorDetail($tutor), []);
    }
}

