<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\SubmissionGraded;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendGradeNotificationToStudent
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
    public function handle(SubmissionGraded $event)
    {
        $submission = $event->submission;
        $assignment = $submission->assignment;
        $user = $submission->user;

        $message = "Your submission for assignment '{$assignment->title}' has been graded. Your score is {$submission->score}.";

        $this->notificationService->createNotification(
            $user,
            NotificationType::SubmissionGraded->value,
            $message,
            ['assignment_id' => $assignment->id, 'submission_id' => $submission->id]
        );
    }
}
