<?php

namespace App\Listeners;

use App\Events\NewLessonCreated;
use App\Services\Notification\NotificationServiceInterface;
use App\Enums\NotificationType;

class SendNewLessonNotification
{
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(NewLessonCreated $event): void
    {
        $lesson = $event->lesson;
        $course = $lesson->course;

        foreach ($course->enrolledUsers as $user) {
            $this->notificationService->createNotification(
                $user,
                NotificationType::NewLesson->value,
                "New lesson '{$lesson->title}' added to course '{$course->name}'",
                ['lesson_id' => $lesson->id, 'course_id' => $course->id]
            );
        }
    }
}
