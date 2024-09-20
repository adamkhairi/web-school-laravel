<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Events\CourseStatusChanged;
use App\Models\Course;
use App\Services\Course\CourseServiceInterface;
use App\Services\Enrollment\EnrollmentServiceInterface;
use App\Services\Progress\ProgressServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseServiceInterface $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * Get a list of courses.
     * 
     * @param Request $request
     * @return JsonResponse
     *
     * @example: GET /api/courses?search=programming&teacher_id=5&status=active&start_date=2023-09-01&end_date=2023-12-31&min_capacity=10&max_capacity=50&sort_by=name&sort_direction=asc&per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('Fetching courses', ['request' => $request->all()]);
        try {
            $courses = $this->courseService->getCourses($request);
            Log::info('Courses fetched successfully', ['courses_count' => count($courses)]);
            return $this->successResponse($courses);
        } catch (Exception $e) {
            Log::error('Failed to fetch courses', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch courses', 500);
        }
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        Log::info('Fetching course details', ['course_id' => $course->id]);
        try {
            $course = $this->courseService->getCourse($course);
            $progress = null;

            if ($request->user()) {
                $progressService = app(ProgressServiceInterface::class);
                $progress = $progressService->getCourseProgress($request->user(), $course);
            }

            Log::info('Course details retrieved successfully', ['course_id' => $course->id]);
            return $this->successResponse([
                'course' => $course,
                'progress' => $progress,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve course', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve course', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        Log::info('Creating a new course', ['request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'teacher_id' => 'required|exists:users,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'status' => 'nullable|string|in:' . implode(',', CourseStatus::values()),
                'capacity' => 'required|integer|min:1',
            ]);

            $course = $this->courseService->createCourse($validatedData);
            Log::info('Course created successfully', ['course_id' => $course->id]);
            return $this->successResponse($course, 'Course created successfully', 201);
        } catch (ValidationException $e) {
            Log::error('Validation error during course creation', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('Failed to create course', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create course', 500);
        }
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        Log::info('Updating course', ['course_id' => $course->id, 'request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'teacher_id' => 'required|exists:users,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'status' => 'required|string|in:' . implode(',', CourseStatus::values()),
                'capacity' => 'required|integer|min:1',
            ]);

            $updatedCourse = $this->courseService->updateCourse($course, $validatedData);

            // Fire course status changed event
            if ($course->wasChanged('status')) {
                event(new CourseStatusChanged($course));
            }

            Log::info('Course updated successfully', ['course_id' => $updatedCourse->id]);
            return $this->successResponse($updatedCourse, 'Course updated successfully');
        } catch (ValidationException $e) {
            Log::error('Validation error during course update', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Failed to update course', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to update course', 500);
        }
    }

    public function destroy(Course $course): JsonResponse
    {
        Log::info('Deleting course', ['course_id' => $course->id]);
        try {
            $this->courseService->deleteCourse($course);
            Log::info('Course deleted successfully', ['course_id' => $course->id]);
            return $this->successResponse(null, 'Course deleted successfully', 204);
        } catch (Exception $e) {
            Log::error('Failed to delete course', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete course', 500);
        }
    }

    public function generateAccessCode(Course $course): JsonResponse
    {
        Log::info('Generating access code for course', ['course_id' => $course->id]);
        try {
            $course = $this->courseService->setAccessCode($course);
            Log::info('Access code generated successfully', ['course_id' => $course->id, 'access_code' => $course->access_code]);
            return $this->successResponse(['access_code' => $course->access_code], 'Access code generated successfully');
        } catch (Exception $e) {
            Log::error('Failed to generate access code', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to generate access code', 500);
        }
    }


    public function removeAccessCode(Course $course): JsonResponse
    {
        Log::info('Removing access code for course', ['course_id' => $course->id]);
        try {
            $course = $this->courseService->removeAccessCode($course);
            Log::info('Access code removed successfully', ['course_id' => $course->id]);
            return $this->successResponse(null, 'Access code removed successfully');
        } catch (Exception $e) {
            Log::error('Failed to remove access code', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to remove access code', 500);
        }
    }

    public function joinCourse(Request $request): JsonResponse
    {
        Log::info('Student attempting to join course', ['request' => $request->all()]);
        $request->validate([
            'access_code' => 'required|string',
        ]);
        try {

            $course = Course::where('access_code', $request->access_code)->firstOrFail();
            $enrollmentService = app(EnrollmentServiceInterface::class);
            $result = $enrollmentService->enrollInCourse($course);
            return $this->successResponse($result, 'Successfully joined the course', 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Invalid access code', 404);
        } catch (Exception $e) {
            Log::error('Failed to join course', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to join course', 500);
        }
    }
}
