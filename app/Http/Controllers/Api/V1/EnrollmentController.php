<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentStatusUpdated;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\Enrollment\EnrollmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
        Log::info('Attempting to enroll', ['request' => $request->all()]);

        if (Gate::denies('enroll', Course::class)) {
            Log::warning('Unauthorized enrollment attempt', ['user_id' => $request->user()->id]);
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $result = $this->enrollmentService->enroll($request);
            Log::info('Enrollment request submitted successfully', ['result' => $result]);
            return $this->successResponse($result, 'Enrollment request submitted successfully', $result['status']);
        } catch (\Exception $e) {
            Log::error('Failed to submit enrollment request', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to submit enrollment request: ' . $e->getMessage(), 500);
        }
    }

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment): JsonResponse
    {
        Log::info('Updating enrollment status', ['enrollment_id' => $enrollment->id]);

        if (Gate::denies('updateStatus', $enrollment)) {
            Log::warning('Unauthorized status update attempt', ['user_id' => $request->user()->id, 'enrollment_id' => $enrollment->id]);
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $result = $this->enrollmentService->updateEnrollmentStatus($request, $enrollment);

            // Emit event if enrollment status is approved
            if ($enrollment->status === EnrollmentStatus::Approved) {
                event(new EnrollmentStatusUpdated($enrollment));
                Log::info('Enrollment status approved', ['enrollment_id' => $enrollment->id]);
            }

            Log::info('Enrollment status updated successfully', ['result' => $result]);
            return $this->successResponse($result, 'Enrollment status updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update enrollment status', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to update enrollment status: ' . $e->getMessage(), 500);
        }
    }

    public function getStudentEnrollments(): JsonResponse
    {
        Log::info('Fetching student enrollments');

        if (Gate::denies('view', Enrollment::class)) {
            Log::warning('Unauthorized view enrollments attempt', ['user_id' => request()->user()->id]);
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $enrollments = $this->enrollmentService->getStudentEnrollments();
            Log::info('Student enrollments fetched successfully', ['enrollments_count' => count($enrollments)]);
            return $this->successResponse($enrollments);
        } catch (\Exception $e) {
            Log::error('Failed to fetch enrollments', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch enrollments: ' . $e->getMessage(), 500);
        }
    }

    public function getCourseEnrollments(Course $course): JsonResponse
    {
        Log::info('Fetching course enrollments', ['course_id' => $course->id]);

        if (Gate::denies('view', [Enrollment::class, $course])) {
            Log::warning('Unauthorized view course enrollments attempt', ['user_id' => request()->user()->id, 'course_id' => $course->id]);
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $enrollments = $this->enrollmentService->getCourseEnrollments($course);
            Log::info('Course enrollments fetched successfully', ['course_id' => $course->id, 'enrollments_count' => count($enrollments)]);
            return $this->successResponse($enrollments);
        } catch (\Exception $e) {
            Log::error('Failed to fetch course enrollments', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch course enrollments: ' . $e->getMessage(), 500);
        }
    }

    public function withdrawEnrollment(Enrollment $enrollment): JsonResponse
    {
        Log::info('Withdrawing enrollment', ['enrollment_id' => $enrollment->id]);

        if (Gate::denies('withdraw', $enrollment)) {
            Log::warning('Unauthorized withdraw attempt', ['user_id' => request()->user()->id, 'enrollment_id' => $enrollment->id]);
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $result = $this->enrollmentService->withdrawEnrollment($enrollment);
            Log::info('Enrollment withdrawn successfully', ['enrollment_id' => $enrollment->id]);
            return $this->successResponse($result, 'Enrollment withdrawn successfully');
        } catch (\Exception $e) {
            Log::error('Failed to withdraw enrollment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to withdraw enrollment: ' . $e->getMessage(), 500);
        }
    }

    public function getEnrollmentStatistics(Course $course): JsonResponse
    {
        Log::info('Fetching enrollment statistics', ['course_id' => $course->id]);

        try {
            $statistics = $this->enrollmentService->getEnrollmentStatistics($course);
            Log::info('Enrollment statistics fetched successfully', ['course_id' => $course->id, 'statistics' => $statistics]);
            return $this->successResponse($statistics);
        } catch (\Exception $e) {
            Log::error('Failed to fetch enrollment statistics', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch enrollment statistics: ' . $e->getMessage(), 500);
        }
    }
}
