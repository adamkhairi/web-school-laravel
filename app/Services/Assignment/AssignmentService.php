<?php

namespace App\Services\Assignment;

use App\Models\Assignment;
use App\Models\Submission;
use App\Services\Assignement\AssignmentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentService implements AssignmentServiceInterface
{
    public function getAssignments(Request $request, $courseId)
    {
        $query = Assignment::where('course_id', $courseId);

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%$searchTerm%")
                    ->orWhere('description', 'like', "%$searchTerm%");
            });
        }

        if ($request->has('due_date_start')) {
            $query->where('due_date', '>=', $request->input('due_date_start'));
        }

        if ($request->has('due_date_end')) {
            $query->where('due_date', '<=', $request->input('due_date_end'));
        }

        $sortField = $request->input('sort_by', 'due_date');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->input('per_page', 15);
        return $query->paginate($perPage);
    }

    public function createAssignment(array $data)
    {
        return Assignment::create($data);
    }

    public function updateAssignment(Assignment $assignment, array $data)
    {
        $assignment->update($data);
        return $assignment;
    }

    public function deleteAssignment(Assignment $assignment)
    {
        return $assignment->delete();
    }

    public function submitAssignment(Request $request, Assignment $assignment)
    {
        $filePath = $request->file('file')->store('submissions', 'public');

        return Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $request->user()->id,
            'file_path' => $filePath,
        ]);
    }

    public function gradeSubmission(Submission $submission, array $data)
    {
        $submission->update($data);
        return $submission;
    }

    public function getSubmissions(Assignment $assignment)
    {
        return $assignment->submissions()->with('student')->get();
    }
}
