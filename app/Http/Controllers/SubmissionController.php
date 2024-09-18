<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Submission::class, 'submission');
    }
    public function index($assignmentId)
    {
        $submissions = Submission::where('assignment_id', $assignmentId)->get();
        return response()->json($submissions);
    }

    public function store(Request $request, $assignmentId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,zip|max:20480',
        ]);

        $filePath = $request->file('file')->store('submissions', 'public');

        $submission = Submission::create([
            'assignment_id' => $assignmentId,
            'student_id' => $request->user()->id,
            'file_path' => $filePath,
        ]);

        return response()->json($submission, 201);
    }

    public function grade(Request $request, Submission $submission)
    {
        $request->validate([
            'grade' => 'required|integer|min:0|max:100',
        ]);

        $submission->update(['grade' => $request->grade]);

        return response()->json($submission);
    }
}
