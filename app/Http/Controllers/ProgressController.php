<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\Progress\ProgressServiceInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    protected $progressService;

    public function __construct(ProgressServiceInterface $progressService)
    {
        $this->progressService = $progressService;
    }

    public function markLessonAsCompleted(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        try {
            Log::info('Marking lesson as completed', [
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
            ]);
            $progress = $this->progressService->markLessonAsCompleted($request->user(), $course, $lesson);
            return $this->successResponse($progress, 'Lesson marked as completed successfully');
        } catch (Exception $e) {
            Log::error('Failed to mark lesson as completed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
            ]);
            return $this->errorResponse('Failed to mark lesson as completed: ' . $e->getMessage(), 500);
        }
    }

    public function markLessonAsIncomplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        try {
            Log::info('Marking lesson as incomplete', [
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
            ]);
            $progress = $this->progressService->markLessonAsIncomplete($request->user(), $course, $lesson);
            return $this->successResponse($progress, 'Lesson marked as incomplete successfully');
        } catch (Exception $e) {
            Log::error('Failed to mark lesson as incomplete', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
            ]);
            return $this->errorResponse('Failed to mark lesson as incomplete: ' . $e->getMessage(), 500);
        }
    }

    public function getCourseProgress(Request $request, Course $course): JsonResponse
    {
        try {
            Log::info('Fetching course progress', [
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
            ]);
            $progress = $this->progressService->getCourseProgress($request->user(), $course);
            return $this->successResponse($progress);
        } catch (Exception $e) {
            Log::error('Failed to fetch course progress', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
            ]);
            return $this->errorResponse('Failed to fetch course progress: ' . $e->getMessage(), 500);
        }
    }
}
