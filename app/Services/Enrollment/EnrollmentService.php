<?php

namespace App\Services\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function enroll(Request $request)
    {
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
            return ['message' => 'You are already enrolled in this course', 'status' => 422];
        }

        // Check if the course has reached its capacity
        $enrolledCount = $course->enrollments()->count();
        if ($enrolledCount < $course->capacity) {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::Pending,
                'enrolled_at' => now(),
            ]);
            return ['message' => 'Enrollment request submitted successfully', 'enrollment' => $enrollment, 'status' => 201];
        } else {
            // Add to waitlist
            $waitlistEnrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::Waitlisted,
                'enrolled_at' => now(),
            ]);
            return ['message' => 'You have been added to the waitlist', 'enrollment' => $waitlistEnrollment, 'status' => 201];
        }
    }
    
    public function getWaitlistedStudents(Course $course)
    {
        return $course->enrollments()->where('status', EnrollmentStatus::Waitlisted)->with('user')->get();
    }

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment)
    {
        $validatedData = $request->validate([
            'status' => ['required', Rule::in(EnrollmentStatus::values())],
        ]);

        $enrollment->update(['status' => EnrollmentStatus::from($validatedData['status'])]);

        if ($enrollment->status === EnrollmentStatus::Approved) {
            // Notify the student that their enrollment was approved
            // You can implement a notification system here
        }

        return ['message' => 'Enrollment status updated successfully', 'enrollment' => $enrollment];
    }

    public function getStudentEnrollments()
    {
        $user = auth()->user();
        return $user->enrollments()->with('course')->get();
    }

    public function getCourseEnrollments(Course $course)
    {
        return $course->enrollments()->with('user')->get();
    }

    public function withdrawEnrollment(Enrollment $enrollment)
    {
        if ($enrollment->user_id !== auth()->id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        $enrollment->delete();
        return ['message' => 'Enrollment withdrawn successfully'];
    }

    public function getEnrollmentStatistics(Course $course)
    {
        return [
            'total_enrollments' => $course->enrollments()->count(),
            'approved_enrollments' => $course->enrollments()->approved()->count(),
            'pending_enrollments' => $course->enrollments()->pending()->count(),
            'available_slots' => $course->capacity - $course->enrollments()->approved()->count(),
        ];
    }

    public function enrollInCourse(Course $course)
    {
        $user = auth()->user();

        // Check if the user is already enrolled
        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return ['message' => 'You are already enrolled in this course', 'status' => 422];
        }

        // Check if the course has reached its capacity
        $enrolledCount = $course->enrollments()->count();
        if ($enrolledCount >= $course->capacity) {
            return ['message' => 'This course has reached its maximum capacity', 'status' => 422];
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Pending,
            'enrolled_at' => now(),
        ]);

        return ['message' => 'Successfully enrolled', 'enrollment' => $enrollment];
    }
}
