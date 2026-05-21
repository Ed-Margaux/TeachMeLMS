<?php

namespace App\Service;

use App\Entity\ClassSession;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\Tutor;

final class EnrollmentApiPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function enrollment(Enrollment $enrollment, bool $includeSessions = false): array
    {
        $student = $enrollment->getStudent();
        $course = $enrollment->getCourse();
        $tutor = $enrollment->getTutor();

        $data = [
            'id' => $enrollment->getId(),
            'status' => $enrollment->getStatus(),
            'parentNote' => $enrollment->getParentNote(),
            'staffNote' => $enrollment->getStaffNote(),
            'requestedAt' => $enrollment->getRequestedAt()?->format(DATE_ATOM),
            'approvedAt' => $enrollment->getApprovedAt()?->format(DATE_ATOM),
            'student' => $student ? $this->studentRef($student) : null,
            'course' => $course ? $this->courseRef($course) : null,
            'tutor' => $tutor ? $this->tutorRef($tutor) : null,
        ];

        if ($includeSessions) {
            $sessions = [];
            foreach ($enrollment->getClassSessions() as $session) {
                $sessions[] = $this->classSession($session);
            }
            $data['sessions'] = $sessions;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function classSession(ClassSession $session): array
    {
        $enrollment = $session->getEnrollment();

        return [
            'id' => $session->getId(),
            'title' => $session->getTitle(),
            'scheduledAt' => $session->getScheduledAt()?->format(DATE_ATOM),
            'durationMinutes' => $session->getDurationMinutes(),
            'meetingUrl' => $session->getMeetingUrl(),
            'status' => $session->getStatus(),
            'enrollmentId' => $enrollment?->getId(),
            'student' => $enrollment?->getStudent() ? $this->studentRef($enrollment->getStudent()) : null,
            'course' => $enrollment?->getCourse() ? $this->courseRef($enrollment->getCourse()) : null,
            'tutor' => $enrollment?->getTutor() ? $this->tutorRef($enrollment->getTutor()) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function courseDetail(Course $course): array
    {
        return [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'level' => $course->getLevel(),
            'slug' => $course->getSlug(),
            'image' => $course->getImage(),
            'createdAt' => $course->getCreatedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tutorDetail(Tutor $tutor): array
    {
        return [
            'id' => $tutor->getId(),
            'fullName' => $tutor->getFullName(),
            'email' => $tutor->getEmail(),
            'phone' => $tutor->getPhone(),
            'specialty' => $tutor->getSpecialty(),
            'image' => $tutor->getImage(),
            'createdAt' => $tutor->getCreatedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentRef(Student $student): array
    {
        return [
            'id' => $student->getId(),
            'fullName' => $student->getFullName(),
            'grade' => $student->getGrade(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseRef(Course $course): array
    {
        return [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'level' => $course->getLevel(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tutorRef(Tutor $tutor): array
    {
        return [
            'id' => $tutor->getId(),
            'fullName' => $tutor->getFullName(),
            'specialty' => $tutor->getSpecialty(),
            'image' => $tutor->getImage(),
        ];
    }
}
