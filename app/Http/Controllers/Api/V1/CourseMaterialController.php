<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CourseMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseMaterialController extends Controller
{
    public function index($courseId)
    {
        $materials = CourseMaterial::where('course_id', $courseId)->get();
        return response()->json($materials);
    }

    public function store(Request $request, $courseId)
    {
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

        return response()->json($material, 201);
    }

    public function destroy(CourseMaterial $material)
    {
        Storage::disk('public')->delete($material->file_path);
        $material->delete();

        return response()->json(null, 204);
    }
}
