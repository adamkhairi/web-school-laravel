<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\NewAssignmentCreated;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyStudentsAboutNewAssignment
{
    /**
     * Create the event listener.
     */
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(NewAssignmentCreated $event): void
    {
        $assignment = $event->assignment;
        $course = $assignment->course;

        foreach ($course->enrolledUsers as $user) {
            $message = "A new assignment '{$assignment->title}' has been added to the course '{$course->name}'.";

            $this->notificationService->createNotification(
                $user,
                NotificationType::NewAssignment->value,
                $message,
                ['course_id' => $course->id, 'assignment_id' => $assignment->id]
            );
        }
    }
}
