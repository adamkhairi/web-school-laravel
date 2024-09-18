<?php

namespace App\Services\Assignment;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

interface AssignmentServiceInterface
{
  public function getAssignments(Request $request, $courseId);

  public function createAssignment(array $data);

  public function updateAssignment(Assignment $assignment, array $data);

  public function deleteAssignment(Assignment $assignment);

  public function submitAssignment(Request $request, Assignment $assignment);

  public function gradeSubmission(Submission $submission, array $data);

  public function getSubmissions(Assignment $assignment);
}
