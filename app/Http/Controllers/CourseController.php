<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $courses = Course::with('teacher')->paginate(15);
            return response()->json($courses);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch courses: ' . $e->getMessage()], 500);
        }
    }

    public function show(Course $course): JsonResponse
    {
        try {
            return response()->json($course->load('teacher'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve course: ' . $e->getMessage()], 500);
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
                'status' => 'required|in:planned,active,completed',
            ]);

            $course = Course::create($validatedData);
            return response()->json($course, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create course: ' . $e->getMessage()], 500);
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
                'status' => 'required|in:planned,active,completed',
            ]);

            $course->update($validatedData);
            return response()->json($course);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update course: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Course $course): JsonResponse
    {
        try {
            $course->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete course: ' . $e->getMessage()], 500);
        }
    }
}
