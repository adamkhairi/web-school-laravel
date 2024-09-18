<?php

namespace App\Services\Lesson;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Http\Request;

class LessonService implements LessonServiceInterface
{
  public function getLessons(Request $request, Course $course)
  {
    $query = $course->lessons();

    if ($request->has('search')) {
      $searchTerm = $request->input('search');
      $query->where(function ($q) use ($searchTerm) {
        $q->where('title', 'like', "%$searchTerm%")
          ->orWhere('description', 'like', "%$searchTerm%")
          ->orWhere('content', 'like', "%$searchTerm%");
      });
    }

    if ($request->has('order')) {
      $query->where('order', $request->input('order'));
    }

    if ($request->has('created_after')) {
      $query->where('created_at', '>=', $request->input('created_after'));
    }

    if ($request->has('created_before')) {
      $query->where('created_at', '<=', $request->input('created_before'));
    }

    $sortField = $request->input('sort_by', 'order');
    $sortDirection = $request->input('sort_direction', 'asc');
    $query->orderBy($sortField, $sortDirection);

    $perPage = $request->input('per_page', 15);
    return $query->paginate($perPage);
  }

  public function createLesson(Course $course, array $data)
  {
    return $course->lessons()->create($data);
  }

  public function updateLesson(Lesson $lesson, array $data)
  {
    $lesson->update($data);
    return $lesson;
  }

  public function deleteLesson(Lesson $lesson)
  {
    return $lesson->delete();
  }

  public function markLessonAsCompleted(User $user, Course $course, Lesson $lesson)
  {
    return Progress::updateOrCreate(
      ['user_id' => $user->id, 'course_id' => $course->id, 'lesson_id' => $lesson->id],
      ['completed' => true]
    );
  }

  public function markLessonAsIncomplete(User $user, Course $course, Lesson $lesson)
  {
    return Progress::updateOrCreate(
      ['user_id' => $user->id, 'course_id' => $course->id, 'lesson_id' => $lesson->id],
      ['completed' => false]
    );
  }
}
