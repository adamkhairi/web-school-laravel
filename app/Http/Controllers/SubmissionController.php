<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use App\Services\Submission\SubmissionServiceInterface;

class SubmissionController extends Controller
{
    protected $submissionService;

    public function __construct(SubmissionServiceInterface $submissionService)
    {
        $this->submissionService = $submissionService;
        $this->authorizeResource(Submission::class, 'submission');
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
