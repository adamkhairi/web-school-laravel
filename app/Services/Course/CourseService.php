<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Enums\CourseStatus;
use Illuminate\Http\Request;

class CourseService implements CourseInterface
{
    public function getCourses(Request $request)
    {
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
        return $query->paginate($perPage);
    }

    public function getCourse(Course $course)
    {
        return $course->load('teacher');
    }

    public function createCourse(array $data)
    {
        $data['status'] = $data['status'] ?? CourseStatus::Planned->value;
        return Course::create($data);
    }

    public function updateCourse(Course $course, array $data)
    {
        $course->update($data);
        return $course;
    }

    public function deleteCourse(Course $course)
    {
        return $course->delete();
    }
}
