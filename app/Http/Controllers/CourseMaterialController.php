<?php

namespace App\Http\Controllers;

use App\Models\CourseMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseMaterialController extends Controller
{
    public function index($courseId): JsonResponse
    {
        try {
            $materials = CourseMaterial::where('course_id', $courseId)->get();
            return $this->successResponse($materials);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch course materials: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf,doc,docx,mp4,mov,avi|max:20480',
            ]);

            $filePath = $request->file('file')->store('course_materials', 'public');

            $material = CourseMaterial::create([
                'course_id' => $courseId,
                'title' => $request->title,
                'file_path' => $filePath,
            ]);

            return $this->successResponse($material, 'Course material created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create course material: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(CourseMaterial $material): JsonResponse
    {
        try {
            Storage::disk('public')->delete($material->file_path);
            $material->delete();

            return $this->successResponse(null, 'Course material deleted successfully', 204);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete course material: ' . $e->getMessage(), 500);
        }
    }
}
