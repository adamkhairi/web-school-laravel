<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CourseController extends Controller
{
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
            $query = Course::with('teacher');

            // Search
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Filter by teacher
            if ($request->has('teacher_id')) {
                $query->where('teacher_id', $request->input('teacher_id'));
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Filter by start and end dates
            if ($request->has('start_date')) {
                $query->whereDate('start_date', '>=', $request->input('start_date'));
            }
            if ($request->has('end_date')) {
                $query->whereDate('end_date', '<=', $request->input('end_date'));
            }

            // Filter by capacity
            if ($request->has('min_capacity')) {
                $query->where('capacity', '>=', $request->input('min_capacity'));
            }
            if ($request->has('max_capacity')) {
                $query->where('capacity', '<=', $request->input('max_capacity'));
            }

            // Sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $courses = $query->paginate($perPage);

            return response()->json($courses);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to fetch courses: ' . $e->getMessage(), 500);
        }
    }

    public function show(Course $course): JsonResponse
    {
        try {
            return response()->json($course->load('teacher'));
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

            // Set default status to 'planned' if not provided
            $validatedData['status'] = $validatedData['status'] ?? CourseStatus::Planned->value;

            $course = Course::create($validatedData);
            return response()->json($course, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendFailedResponse($e->errors(), 422);
        } catch (\Exception $e) {
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

            $course->update($validatedData);
            return response()->json($course);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to update course: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Course $course): JsonResponse
    {
        try {
            $course->delete();
            return response()->json(null, 204);
        } catch (Exception $e) {
            return $this->sendFailedResponse('Failed to delete course: ' . $e->getMessage(), 500);
        }
    }
}
