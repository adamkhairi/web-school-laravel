<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\LessonCompleted;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Progress\ProgressServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateUserProgress
{
    /**
     * Create the event listener.
     */
    protected $progressService;
    protected $notificationService;

    public function __construct(
        ProgressServiceInterface $progressService,
        NotificationServiceInterface $notificationService
    ) {
        $this->progressService = $progressService;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(LessonCompleted $event): void
    {
        $user = $event->user;
        $lesson = $event->lesson;
        $course = $lesson->course;

        // Update progress
        $progress = $this->progressService->markLessonAsCompleted($user, $course, $lesson);

        // Calculate new progress percentage
        $newProgressPercentage = $course->progressPercentage($user);

        // Notify user
        $message = "You've completed the lesson '{$lesson->title}'. Your course progress is now {$newProgressPercentage}%.";

        $this->notificationService->createNotification(
            $user,
            NotificationType::LessonCompleted->value,
            $message,
            ['course_id' => $course->id, 'lesson_id' => $lesson->id]
        );
    }
}
