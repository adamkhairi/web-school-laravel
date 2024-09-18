<?php

namespace App\Services\Submission;

use App\Models\Submission;
use Illuminate\Http\Request;

interface SubmissionServiceInterface
{
    public function getSubmissions($assignmentId);
    public function createSubmission(Request $request, $assignmentId);
    public function gradeSubmission(Request $request, Submission $submission);
}
