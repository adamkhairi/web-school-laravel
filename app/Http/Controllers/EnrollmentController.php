<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentStatusUpdated;
use App\Models\Enrollment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Services\Enrollment\EnrollmentServiceInterface;

class EnrollmentController extends Controller
{

    /* 
      - Students can enroll in courses.
      - Enrollment requests are initially set to 'pending' status.
      - Administrators or teachers can approve or reject enrollment requests.
      - Students can view their enrollments.
      - Teachers can view enrollments for their courses.
      - Students can withdraw from courses.
      - The system checks for course capacity before allowing new enrollments.
      - Enrollment statistics are available for each course. 
      */


    protected $enrollmentService;

    public function __construct(EnrollmentServiceInterface $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    public function enroll(Request $request): JsonResponse
    {
        if (Gate::denies('enroll', Course::class)) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $result = $this->enrollmentService->enroll($request);
            return $this->successResponse($result, 'Enrollment request submitted successfully', $result['status']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit enrollment request: ' . $e->getMessage(), 500);
        }
    }

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment): JsonResponse
    {
        if (Gate::denies('updateStatus', $enrollment)) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $result = $this->enrollmentService->updateEnrollmentStatus($request, $enrollment);

            // Emit event if enrollment status is approved
            if ($enrollment->status === EnrollmentStatus::Approved) {
                event(new EnrollmentStatusUpdated($enrollment));
            }

            return $this->successResponse($result, 'Enrollment status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update enrollment status: ' . $e->getMessage(), 500);
        }
    }

    public function getStudentEnrollments(): JsonResponse
    {
        if (Gate::denies('view', Enrollment::class)) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $enrollments = $this->enrollmentService->getStudentEnrollments();
            return $this->successResponse($enrollments);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch enrollments: ' . $e->getMessage(), 500);
        }
    }

    public function getCourseEnrollments(Course $course): JsonResponse
    {
        if (Gate::denies('view', [Enrollment::class, $course])) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $enrollments = $this->enrollmentService->getCourseEnrollments($course);
            return $this->successResponse($enrollments);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch course enrollments: ' . $e->getMessage(), 500);
        }
    }

    public function withdrawEnrollment(Enrollment $enrollment): JsonResponse
    {
        if (Gate::denies('withdraw', $enrollment)) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $result = $this->enrollmentService->withdrawEnrollment($enrollment);
            return $this->successResponse($result, 'Enrollment withdrawn successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to withdraw enrollment: ' . $e->getMessage(), 500);
        }
    }

    public function getEnrollmentStatistics(Course $course): JsonResponse
    {
        try {
            $statistics = $this->enrollmentService->getEnrollmentStatistics($course);
            return $this->successResponse($statistics);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch enrollment statistics: ' . $e->getMessage(), 500);
        }
    }
}
