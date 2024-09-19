<?php

namespace App\Enums;

enum NotificationType: string
{
    case NewLesson = 'new_lesson';
    case AssignmentDeadline = 'assignment_deadline';
    case CourseEnrollment = 'course_enrollment';
    case NewAssignment = 'new_assignment';
    case SubmissionGraded = 'submission_graded';
    case CourseStatusChanged = 'course_status_changed';
    case LessonCompleted = 'lesson_completed';
    case UserActivation = 'user_activation';
}
