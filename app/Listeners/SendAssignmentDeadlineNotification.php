<?php

namespace App\Listeners;

use App\Events\AssignmentDeadlineApproaching;
use App\Services\Notification\NotificationServiceInterface;
use App\Enums\NotificationType;

class SendAssignmentDeadlineNotification
{
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(AssignmentDeadlineApproaching $event): void
    {
        $assignment = $event->assignment;
        $course = $assignment->course;

        foreach ($course->enrolledUsers as $user) {
            $this->notificationService->createNotification(
                $user,
                NotificationType::AssignmentDeadline->value,
                "Assignment '{$assignment->title}' deadline is approaching",
                ['assignment_id' => $assignment->id, 'course_id' => $course->id]
            );
        }
    }
}
