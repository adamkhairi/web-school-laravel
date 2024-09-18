<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Services\Course\CourseServiceInterface;
use App\Services\Progress\ProgressServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        try {
            $courses = $this->courseService->getCourses($request);
            return response()->json($courses);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to fetch courses: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        try {
            $course = $this->courseService->getCourse($course);
            $progress = null;
            
            if ($request->user()) {
                $progressService = app(ProgressServiceInterface::class);
                $progress = $progressService->getCourseProgress($request->user(), $course);
            }
            
            return response()->json([
                'course' => $course,
                'progress' => $progress,
            ]);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to retrieve course: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
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
            return response()->json($course, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to create course: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Course $course): JsonResponse
    {
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
            return response()->json($updatedCourse);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to update course: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Course $course): JsonResponse
    {
        try {
            $this->courseService->deleteCourse($course);
            return response()->json(null, 204);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to delete course: ' . $e->getMessage(), 500);
        }
    }

    public function generateAccessCode(Course $course): JsonResponse
    {
        try {
            $course = $this->courseService->setAccessCode($course);
            return response()->json(['access_code' => $course->access_code]);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to generate access code: ' . $e->getMessage(), 500);
        }
    }

    public function removeAccessCode(Course $course): JsonResponse
    {
        try {
            $course = $this->courseService->removeAccessCode($course);
            return response()->json(['message' => 'Access code removed successfully']);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to remove access code: ' . $e->getMessage(), 500);
        }
    }
}
