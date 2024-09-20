<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use App\Services\Submission\SubmissionServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function gradeReport($assignmentId): JsonResponse
    {
        Log::info('Fetching grade report for assignment ID: ' . $assignmentId);

        // Validate assignmentId
        if (!is_numeric($assignmentId)) {
            return $this->errorResponse('Invalid assignment ID', 400);
        }

        try {
            $assignment = Assignment::findOrFail($assignmentId);

            // Pagination parameters
            $perPage = request()->input('per_page', 10); // Default to 10 submissions per page
            $page = request()->input('page', 1); // Default to the first page

            // Search and sorting parameters
            $searchTerm = request()->input('search');
            $sortField = request()->input('sort_by', 'created_at'); // Default sort field
            $sortDirection = request()->input('sort_direction', 'asc'); // Default sort direction

            $submissionsQuery = $assignment->submissions()->with('student');

            // Apply search if provided
            if ($searchTerm) {
                $submissionsQuery->whereHas('student', function ($query) use ($searchTerm) {
                    $query->where('name', 'like', "%$searchTerm%"); // Assuming 'name' is a field in the User model
                });
            }

            // Apply sorting
            $submissionsQuery->orderBy($sortField, $sortDirection);

            // Paginate results
            $submissions = $submissionsQuery->paginate($perPage, ['*'], 'page', $page);

            $averageGrade = $assignment->averageGrade();
            $report = [
                'assignment_id' => $assignmentId,
                'average_grade' => $averageGrade,
                'submissions' => $submissions,
            ];

            Log::info('Grade report fetched successfully', ['assignment_id' => $assignmentId, 'user_id' => auth()->id()]);
            return $this->successResponse($report);
        } catch (ModelNotFoundException $e) {
            Log::error('Assignment not found', ['error' => $e->getMessage(), 'assignment_id' => $assignmentId]);
            return $this->errorResponse('Assignment not found', 404);
        } catch (Exception $e) {
            Log::error('Failed to fetch grade report', ['error' => $e->getMessage(), 'assignment_id' => $assignmentId]);
            return $this->errorResponse('Failed to fetch grade report: ' . $e->getMessage(), 500);
        }
    }
}
