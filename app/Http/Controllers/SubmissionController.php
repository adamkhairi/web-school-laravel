<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use App\Services\Submission\SubmissionServiceInterface;
use Exception;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    protected $submissionService;

    public function __construct(SubmissionServiceInterface $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    public function index($assignmentId): JsonResponse
    {
        try {
            $submissions = $this->submissionService->getSubmissions($assignmentId);
            return $this->successResponse($submissions);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch submissions: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $assignmentId): JsonResponse
    {
        try {
            $submission = $this->submissionService->createSubmission($request, $assignmentId);
            return $this->successResponse($submission, 'Submission created successfully', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create submission: ' . $e->getMessage(), 500);
        }
    }

    public function grade(Request $request, Submission $submission): JsonResponse
    {
        try {
            $gradedSubmission = $this->submissionService->gradeSubmission($request, $submission);
            return $this->successResponse($gradedSubmission, 'Submission graded successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }
}
