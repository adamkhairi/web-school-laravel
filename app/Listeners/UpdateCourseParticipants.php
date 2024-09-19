<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\CourseStatusChanged;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCourseParticipants
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
    public function handle(CourseStatusChanged $event): void
    {
        $course = $event->course;
        $message = "The status of the course '{$course->name}' has been updated to {$course->status->value}.";

        foreach ($course->enrolledUsers as $user) {
            $this->notificationService->createNotification(
                $user,
                NotificationType::CourseStatusChanged->value,
                $message,
                ['course_id' => $course->id]
            );
        }
    }
}
