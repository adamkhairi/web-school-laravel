<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\EnrollmentStatusUpdated;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendEnrollmentStatusNotification
{
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(EnrollmentStatusUpdated $event)
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;
        $course = $enrollment->course;

        $message = "Your enrollment status for the course '{$course->name}' has been updated to {$enrollment->status->value}.";

        $this->notificationService->createNotification(
            $user,
            NotificationType::CourseEnrollment->value,
            $message,
            ['course_id' => $course->id, 'enrollment_id' => $enrollment->id]
        );
    }
}
