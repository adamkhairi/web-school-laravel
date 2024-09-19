<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use App\Services\Submission\SubmissionServiceInterface;
use Exception;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Log;

class SubmissionController extends Controller
{
    protected $submissionService;

    public function __construct(SubmissionServiceInterface $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    public function index($assignmentId): JsonResponse
    {
        Log::info('Fetching submissions for assignment ID: ' . $assignmentId);
        try {
            $submissions = $this->submissionService->getSubmissions($assignmentId);
            return $this->successResponse($submissions);
        } catch (Exception $e) {
            Log::error('Failed to fetch submissions: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch submissions: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $assignmentId): JsonResponse
    {
        Log::info('Creating submission for assignment ID: ' . $assignmentId);
        try {
            $submission = $this->submissionService->createSubmission($request, $assignmentId);
            return $this->successResponse($submission, 'Submission created successfully', 201);
        } catch (Exception $e) {
            Log::error('Failed to create submission: ' . $e->getMessage());
            return $this->errorResponse('Failed to create submission: ' . $e->getMessage(), 500);
        }
    }

    public function grade(Request $request, Submission $submission): JsonResponse
    {
        Log::info('Grading submission ID: ' . $submission->id);
        try {
            $gradedSubmission = $this->submissionService->gradeSubmission($request, $submission);
            return $this->successResponse($gradedSubmission, 'Submission graded successfully');
        } catch (Exception $e) {
            Log::error('Failed to grade submission: ' . $e->getMessage());
            return $this->errorResponse('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }
}
