<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\Lesson\LessonServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    protected $lessonService;

    public function __construct(LessonServiceInterface $lessonService)
    {
        $this->lessonService = $lessonService;
    }

    /**
     * Get a list of lessons.
     *
     * @param Request $request
     * @param Course $course
     * @return JsonResponse
     *
     * @example: GET /api/v1/courses/1/lessons?search=introduction&order=2&created_after=2023-01-01&created_before=2023-12-31&sort_by=title&sort_direction=desc&per_page=10
     */

    public function index(Request $request, Course $course): JsonResponse
    {
        try {
            $lessons = $this->lessonService->getLessons($request, $course);
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

            $lesson = $this->lessonService->createLesson($course, $validatedData);
            return response()->json($lesson, 201);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to create lesson: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string',
                'order' => 'required|integer|min:1',
            ]);

            $updatedLesson = $this->lessonService->updateLesson($lesson, $validatedData);
            return response()->json($updatedLesson);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to update lesson: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Course $course, Lesson $lesson): JsonResponse
    {
        try {
            $this->lessonService->deleteLesson($lesson);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to delete lesson: ' . $e->getMessage(), 500);
        }
    }

    public function markAsCompleted(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        try {
            $progress = $this->lessonService->markLessonAsCompleted($request->user(), $course, $lesson);
            return response()->json($progress);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to mark lesson as completed: ' . $e->getMessage(), 500);
        }
    }

    public function markAsIncomplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        try {
            $progress = $this->lessonService->markLessonAsIncomplete($request->user(), $course, $lesson);
            return response()->json($progress);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to mark lesson as incomplete: ' . $e->getMessage(), 500);
        }
    }
}
