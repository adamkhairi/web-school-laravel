<?php

namespace App\Services\Submission;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubmissionService implements SubmissionServiceInterface
{
    public function getSubmissions($assignmentId)
    {
        return Submission::where('assignment_id', $assignmentId)->get();
    }

    public function createSubmission(Request $request, $assignmentId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,zip|max:20480',
        ]);

        $filePath = $request->file('file')->store('submissions', 'public');

        return Submission::create([
            'assignment_id' => $assignmentId,
            'student_id' => $request->user()->id,
            'file_path' => $filePath,
        ]);
    }

    public function gradeSubmission(Request $request, Submission $submission)
    {
        $request->validate([
            'grade' => 'required|integer|min:0|max:100',
        ]);

        $submission->update(['grade' => $request->grade]);

        return $submission;
    }
}
