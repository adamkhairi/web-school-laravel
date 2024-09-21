<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Submission;
use App\Services\Submission\SubmissionServiceInterface;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    protected $submissionService;

    public function __construct(SubmissionServiceInterface $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    public function index($assignmentId)
    {
        $submissions = $this->submissionService->getSubmissions($assignmentId);
        return response()->json($submissions);
    }

    public function store(Request $request, $assignmentId)
    {
        $submission = $this->submissionService->createSubmission($request, $assignmentId);
        return response()->json($submission, 201);
    }

    public function grade(Request $request, Submission $submission)
    {
        $gradedSubmission = $this->submissionService->gradeSubmission($request, $submission);
        return response()->json($gradedSubmission);
    }
}
