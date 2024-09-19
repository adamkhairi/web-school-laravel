<?php

namespace App\Http\Controllers;

use App\Events\NewAssignmentCreated;
use App\Events\SubmissionGraded;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\Assignment\AssignmentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(AssignmentServiceInterface $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function index(Request $request, $courseId): JsonResponse
    {
        try {
            $assignments = $this->assignmentService->getAssignments($request, $courseId);
            return $this->successResponse($assignments);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch assignments: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $courseId): JsonResponse
    {
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

            return $this->successResponse($assignment, 'Assignment created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create assignment: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Assignment $assignment): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'due_date' => 'sometimes|required|date',
                'max_score' => 'sometimes|required|integer|min:0',
            ]);

            $updatedAssignment = $this->assignmentService->updateAssignment($assignment, $validatedData);
            return $this->successResponse($updatedAssignment, 'Assignment updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update assignment: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        try {
            $this->assignmentService->deleteAssignment($assignment);
            return $this->successResponse(null, 'Assignment deleted successfully', 204);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete assignment: ' . $e->getMessage(), 500);
        }
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,zip|max:20480',
            ]);

            $submission = $this->assignmentService->submitAssignment($request, $assignment);
            return $this->successResponse($submission, 'Assignment submitted successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit assignment: ' . $e->getMessage(), 500);
        }
    }

    public function grade(Request $request, Submission $submission): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'grade' => 'required|integer|min:0|max:' . $submission->assignment->max_score,
                'feedback' => 'nullable|string',
            ]);

            $gradedSubmission = $this->assignmentService->gradeSubmission($submission, $validatedData);

            // Trigger event for submission graded
            event(new SubmissionGraded($gradedSubmission));

            return $this->successResponse($gradedSubmission, 'Submission graded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }
}
