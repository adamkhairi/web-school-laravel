<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
    /**
     * Get a list of lessons.
     * 
     * @param Request $request
     * @param Course $course
     * @return JsonResponse
     * 
     * @example: GET /api/courses/1/lessons?search=introduction&order=2&created_after=2023-01-01&created_before=2023-12-31&sort_by=title&sort_direction=desc&per_page=10
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        try {
            $query = $course->lessons();

            // Search
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%$searchTerm%")
                        ->orWhere('description', 'like', "%$searchTerm%")
                        ->orWhere('content', 'like', "%$searchTerm%");
                });
            }

            // Filtering
            if ($request->has('order')) {
                $query->where('order', $request->input('order'));
            }

            // Additional filtering options
            if ($request->has('created_after')) {
                $query->where('created_at', '>=', $request->input('created_after'));
            }

            if ($request->has('created_before')) {
                $query->where('created_at', '<=', $request->input('created_before'));
            }

            // Sorting
            $sortField = $request->input('sort_by', 'order');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $lessons = $query->paginate($perPage);

            return response()->json($lessons);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to fetch lessons: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string',
                'order' => 'required|integer|min:1',
            ]);

            $lesson = $course->lessons()->create($validatedData);
            return response()->json($lesson, 201);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to create lesson: ' . $e->getMessage(), 500);
        }
    }

    // Implement other CRUD methods as needed

    public function update(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string',
                'order' => 'required|integer|min:1',
            ]);

            $lesson->update($validatedData);
            return response()->json($lesson);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to update lesson: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Course $course, Lesson $lesson): JsonResponse
    {
        try {
            $lesson->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to delete lesson: ' . $e->getMessage(), 500);
        }
    }
}
