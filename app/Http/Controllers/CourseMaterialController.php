<?php

namespace App\Http\Controllers;

use App\Models\CourseMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseMaterialController extends Controller
{
    public function index($courseId): JsonResponse
    {
        Log::info('Fetching course materials', ['course_id' => $courseId]);
        try {
            $materials = CourseMaterial::where('course_id', $courseId)->get();
            Log::info('Course materials fetched successfully', ['materials_count' => count($materials)]);
            return $this->successResponse($materials);
        } catch (\Exception $e) {
            Log::error('Failed to fetch course materials', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch course materials: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $courseId): JsonResponse
    {
        Log::info('Creating course material', ['course_id' => $courseId, 'request' => $request->all()]);
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

            Log::info('Course material created successfully', ['material_id' => $material->id]);
            return $this->successResponse($material, 'Course material created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create course material', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create course material: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(CourseMaterial $material): JsonResponse
    {
        Log::info('Deleting course material', ['material_id' => $material->id]);
        try {
            Storage::disk('public')->delete($material->file_path);
            $material->delete();

            Log::info('Course material deleted successfully', ['material_id' => $material->id]);
            return $this->successResponse(null, 'Course material deleted successfully', 204);
        } catch (\Exception $e) {
            Log::error('Failed to delete course material', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete course material: ' . $e->getMessage(), 500);
        }
    }
}
