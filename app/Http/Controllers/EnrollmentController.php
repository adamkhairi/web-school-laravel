<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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

    public function enroll(Request $request): JsonResponse
    {
        if (Gate::denies('enroll', Enrollment::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,id',
            ]);

            $user = auth()->user();
            $course = Course::findOrFail($validatedData['course_id']);

            // Check if the user is already enrolled
            $existingEnrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingEnrollment) {
                return response()->json(['message' => 'You are already enrolled in this course'], 422);
            }

            // Check if the course has reached its capacity
            $enrolledCount = $course->enrollments()->count();
            if ($enrolledCount >= $course->capacity) {
                return response()->json(['message' => 'This course has reached its maximum capacity'], 422);
            }

            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::Pending,
                'enrolled_at' => now(),
            ]);

            return response()->json(['message' => 'Enrollment request submitted successfully', 'enrollment' => $enrollment], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit enrollment request: ' . $e->getMessage()], 500);
        }
    }

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment): JsonResponse
    {
        if (Gate::denies('updateStatus', $enrollment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $validatedData = $request->validate([
               'status' => ['required', Rule::in(EnrollmentStatus::values())],
            ]);

            $enrollment->update(['status' => EnrollmentStatus::from($validatedData['status'])]);

            if ($enrollment->status === EnrollmentStatus::Approved) {
                // Notify the student that their enrollment was approved
                // You can implement a notification system here
            }

            return response()->json(['message' => 'Enrollment status updated successfully', 'enrollment' => $enrollment]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update enrollment status: ' . $e->getMessage()], 500);
        }
    }

    /* TODO Add pagination, sorting, filtering and search */
    public function getStudentEnrollments(): JsonResponse
    {
        $user = auth()->user();
        if (Gate::denies('view', Enrollment::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        try {
            $enrollments = $user->enrollments()->with('course')->get();
            return response()->json($enrollments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch enrollments: ' . $e->getMessage()], 500);
        }
    }

    /* TODO Add pagination, sorting, filtering and search */
    public function getCourseEnrollments(Course $course): JsonResponse
    {
        if (Gate::denies('view', [Enrollment::class, $course])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $enrollments = $course->enrollments()->with('user')->get();

            return response()->json($enrollments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch course enrollments: ' . $e->getMessage()], 500);
        }
    }

    public function withdrawEnrollment(Enrollment $enrollment): JsonResponse
    {
        if (Gate::denies('withdraw', $enrollment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        try {
            if ($enrollment->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $enrollment->delete();

            return response()->json(['message' => 'Enrollment withdrawn successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to withdraw enrollment: ' . $e->getMessage()], 500);
        }
    }

    public function getEnrollmentStatistics(Course $course): JsonResponse
    {
        try {
            $stats = [
                'total_enrollments' => $course->enrollments()->count(),
                'approved_enrollments' => $course->enrollments()->approved()->count(),
                'pending_enrollments' => $course->enrollments()->pending()->count(),
                'available_slots' => $course->capacity - $course->enrollments()->approved()->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch enrollment statistics: ' . $e->getMessage()], 500);
        }
    }
}
