<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NewAssignmentCreated;
use App\Events\SubmissionGraded;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\Assignment\AssignmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(AssignmentServiceInterface $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index(Request $request, $courseId): JsonResponse
    {
        Log::info('Fetching assignments for course ID: ' . $courseId);
        try {
            $assignments = $this->assignmentService->getAssignments($request, $courseId);
            Log::info('Assignments fetched successfully', ['assignments_count' => count($assignments)]);
            return $this->successResponse($assignments);
        } catch (\Exception $e) {
            Log::error('Failed to fetch assignments', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch assignments: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $courseId): JsonResponse
    {
        Log::info('Creating assignment for course ID: ' . $courseId);
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'max_score' => 'required|integer|min:0',
            ]);

            $validatedData['course_id'] = $courseId;
            $assignment = $this->assignmentService->createAssignment($validatedData);

            // Trigger event for new assignment
            event(new NewAssignmentCreated($assignment));

            Log::info('Assignment created successfully', ['assignment_id' => $assignment->id]);
            return $this->successResponse($assignment, 'Assignment created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create assignment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create assignment: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Assignment $assignment): JsonResponse
    {
        Log::info('Updating assignment ID: ' . $assignment->id);
        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'due_date' => 'sometimes|required|date',
                'max_score' => 'sometimes|required|integer|min:0',
            ]);

            $updatedAssignment = $this->assignmentService->updateAssignment($assignment, $validatedData);
            Log::info('Assignment updated successfully', ['assignment_id' => $updatedAssignment->id]);
            return $this->successResponse($updatedAssignment, 'Assignment updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update assignment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to update assignment: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        Log::info('Deleting assignment ID: ' . $assignment->id);
        try {
            $this->assignmentService->deleteAssignment($assignment);
            Log::info('Assignment deleted successfully', ['assignment_id' => $assignment->id]);
            return $this->successResponse(null, 'Assignment deleted successfully', 204);
        } catch (\Exception $e) {
            Log::error('Failed to delete assignment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete assignment: ' . $e->getMessage(), 500);
        }
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        Log::info('Submitting assignment ID: ' . $assignment->id);
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,zip|max:20480',
            ]);

            $submission = $this->assignmentService->submitAssignment($request, $assignment);
            Log::info('Assignment submitted successfully', ['submission_id' => $submission->id]);
            return $this->successResponse($submission, 'Assignment submitted successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to submit assignment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to submit assignment: ' . $e->getMessage(), 500);
        }
    }

    public function grade(Request $request, Submission $submission): JsonResponse
    {
        Log::info('Grading submission ID: ' . $submission->id);
        try {
            $validatedData = $request->validate([
                'grade' => 'required|integer|min:0|max:' . $submission->assignment->max_score,
                'feedback' => 'nullable|string|max:500', // Validate feedback
            ]);

            $gradedSubmission = $this->assignmentService->gradeSubmission($submission, $validatedData);

            // Trigger event for submission graded
            event(new SubmissionGraded($gradedSubmission));

            Log::info('Submission graded successfully', ['submission_id' => $gradedSubmission->id]);
            return $this->successResponse($gradedSubmission, 'Submission graded successfully');
        } catch (ValidationException $e) {
            Log::error('Validation failed while grading submission', ['errors' => $e->errors()]);
            return $this->errorResponse('Validation failed: ' . implode(', ', $e->errors()), 422);
        } catch (\Exception $e) {
            Log::error('Failed to grade submission', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }
}
