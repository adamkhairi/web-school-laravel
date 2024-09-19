<?php

namespace App\Http\Controllers;

use App\Events\LessonCompleted;
use App\Events\NewLessonCreated;
use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Lesson\LessonServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
     * @example: GET /api/courses/1/lessons?search=introduction&order=2&created_after=2023-01-01&created_before=2023-12-31&sort_by=title&sort_direction=desc&per_page=10
     */

    public function index(Request $request, Course $course): JsonResponse
    {
        Log::info('Fetching lessons for course', ['course_id' => $course->id, 'request' => $request->all()]);
        try {
            $lessons = $this->lessonService->getLessons($request, $course);
            Log::info('Lessons fetched successfully', ['lessons_count' => count($lessons)]);
            return $this->successResponse($lessons);
        } catch (Exception $e) {
            Log::error('Failed to fetch lessons', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch lessons: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        Log::info('Creating a new lesson for course', ['course_id' => $course->id, 'request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string',
                'order' => 'required|integer|min:1',
            ]);

            $lesson = $this->lessonService->createLesson($course, $validatedData);
            Log::info('Lesson created successfully', ['lesson_id' => $lesson->id]);

            // Trigger event for new lesson
            event(new NewLessonCreated($lesson));

            return $this->successResponse($lesson, 'Lesson created successfully', 201);
        } catch (ValidationException $e) {
            Log::error('Validation failed while creating lesson', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Failed to create lesson', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create lesson: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        Log::info('Updating lesson', ['lesson_id' => $lesson->id, 'request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'content' => 'required|string',
                'order' => 'required|integer|min:1',
            ]);

            $updatedLesson = $this->lessonService->updateLesson($lesson, $validatedData);
            Log::info('Lesson updated successfully', ['lesson_id' => $updatedLesson->id]);
            return $this->successResponse($updatedLesson, 'Lesson updated successfully');
        } catch (ValidationException $e) {
            Log::error('Validation failed while updating lesson', ['errors' => $e->errors()]);
            return $this->errorResponse($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Failed to update lesson', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to update lesson: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Course $course, Lesson $lesson): JsonResponse
    {
        Log::info('Deleting lesson', ['lesson_id' => $lesson->id]);
        try {
            $this->lessonService->deleteLesson($lesson);
            Log::info('Lesson deleted successfully', ['lesson_id' => $lesson->id]);
            return $this->successResponse(null, 'Lesson deleted successfully', 204);
        } catch (Exception $e) {
            Log::error('Failed to delete lesson', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete lesson: ' . $e->getMessage(), 500);
        }
    }

    public function markAsCompleted(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        Log::info('Marking lesson as completed', ['lesson_id' => $lesson->id, 'user_id' => $request->user()->id]);
        try {
            $progress = $this->lessonService->markLessonAsCompleted($request->user(), $course, $lesson);

            // Trigger event for lesson completion
            event(new LessonCompleted($request->user(), $lesson));
            Log::info('Lesson marked as completed successfully', ['lesson_id' => $lesson->id]);

            return $this->successResponse($progress, 'Lesson marked as completed successfully');
        } catch (Exception $e) {
            Log::error('Failed to mark lesson as completed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to mark lesson as completed: ' . $e->getMessage(), 500);
        }
    }

    public function markAsIncomplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        Log::info('Marking lesson as incomplete', ['lesson_id' => $lesson->id, 'user_id' => $request->user()->id]);
        try {
            $progress = $this->lessonService->markLessonAsIncomplete($request->user(), $course, $lesson);
            Log::info('Lesson marked as incomplete successfully', ['lesson_id' => $lesson->id]);
            return $this->successResponse($progress, 'Lesson marked as incomplete successfully');
        } catch (Exception $e) {
            Log::error('Failed to mark lesson as incomplete', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to mark lesson as incomplete: ' . $e->getMessage(), 500);
        }
    }
}
