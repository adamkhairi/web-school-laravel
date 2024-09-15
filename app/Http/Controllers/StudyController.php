<?php

namespace App\Http\Controllers;

use App\Models\Study;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudyController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $classes = Study::with('course')->paginate(15);
            return response()->json($classes);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to fetch classes: ' . $e->getMessage(), 500);
        }
    }

    public function show(Study $class): JsonResponse
    {
        try {
            return response()->json($class->load('course'));
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to retrieve class: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $class = Study::create($validatedData);
            return response()->json($class, 201);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to create class: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Study $class): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $class->update($validatedData);
            return response()->json($class);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to update class: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Study $class): JsonResponse
    {
        try {
            $class->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to delete class: ' . $e->getMessage(), 500);
        }
    }
}
