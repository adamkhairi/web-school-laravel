<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\Progress\ProgressServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    protected $progressService;

    public function __construct(ProgressServiceInterface $progressService)
    {
        $this->progressService = $progressService;
    }

    public function markLessonAsCompleted(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $progress = $this->progressService->markLessonAsCompleted($request->user(), $course, $lesson);
        return response()->json($progress);
    }

    public function markLessonAsIncomplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $progress = $this->progressService->markLessonAsIncomplete($request->user(), $course, $lesson);
        return response()->json($progress);
    }

    public function getCourseProgress(Request $request, Course $course): JsonResponse
    {
        $progress = $this->progressService->getCourseProgress($request->user(), $course);
        return response()->json($progress);
    }
}
