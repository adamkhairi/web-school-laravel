<?php

namespace App\Http\Controllers;

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
        $this->authorizeResource(Assignment::class, 'assignment');
    }

    public function index(Request $request, $courseId): JsonResponse
    {
        try {
            $assignments = $this->assignmentService->getAssignments($request, $courseId);
            return response()->json($assignments);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to fetch assignments: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'due_date' => 'required|date',
                'max_score' => 'required|integer|min:0',
            ]);

            $validatedData['course_id'] = $courseId;
            $assignment = $this->assignmentService->createAssignment($validatedData);
            return response()->json($assignment, 201);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to create assignment: ' . $e->getMessage(), 500);
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
            return response()->json($updatedAssignment);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to update assignment: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        try {
            $this->assignmentService->deleteAssignment($assignment);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to delete assignment: ' . $e->getMessage(), 500);
        }
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,zip|max:20480',
            ]);

            $submission = $this->assignmentService->submitAssignment($request, $assignment);
            return response()->json($submission, 201);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to submit assignment: ' . $e->getMessage(), 500);
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
            return response()->json($gradedSubmission);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }

    public function submissions(Assignment $assignment): JsonResponse
    {
        try {
            $submissions = $this->assignmentService->getSubmissions($assignment);
            return response()->json($submissions);
        } catch (\Exception $e) {
            return $this->sendFailedResponse('Failed to fetch submissions: ' . $e->getMessage(), 500);
        }
    }
}
