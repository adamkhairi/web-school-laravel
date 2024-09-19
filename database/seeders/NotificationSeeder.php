<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Notification;
use App\Enums\NotificationType;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create a few notifications for each user
            for ($i = 0; $i < 5; $i++) {
                $notificationType = $this->getRandomNotificationType();
                $message = $this->getMessageForNotificationType($notificationType);
                $data = $this->getDataForNotificationType($notificationType);

                Notification::create([
                    'user_id' => $user->id,
                    'type' => $notificationType,
                    'message' => $message,
                    'data' => $data,
                    'read_at' => $this->getRandomReadAt(),
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    private function getRandomNotificationType()
    {
        $types = NotificationType::cases();
        return $types[array_rand($types)]->value;
    }

    private function getMessageForNotificationType($type)
    {
        switch ($type) {
            case NotificationType::NewLesson->value:
                return 'A new lesson has been added to your course.';
            case NotificationType::AssignmentDeadline->value:
                return 'An assignment deadline is approaching.';
            case NotificationType::CourseEnrollment->value:
                return 'You have been enrolled in a new course.';
            case NotificationType::NewAssignment->value:
                return 'A new assignment has been posted.';
            case NotificationType::SubmissionGraded->value:
                return 'Your submission has been graded.';
            case NotificationType::CourseStatusChanged->value:
                return 'The status of your course has changed.';
            case NotificationType::LessonCompleted->value:
                return 'Congratulations! You have completed a lesson.';
            case NotificationType::UserActivation->value:
                return 'Your account activation status has been updated.';
            default:
                return 'You have a new notification.';
        }
    }

    private function getDataForNotificationType($type)
    {
        switch ($type) {
            case NotificationType::NewLesson->value:
                return ['course_id' => rand(1, 10), 'lesson_id' => rand(1, 50)];
            case NotificationType::AssignmentDeadline->value:
                return ['course_id' => rand(1, 10), 'assignment_id' => rand(1, 30)];
            case NotificationType::CourseEnrollment->value:
                return ['course_id' => rand(1, 10)];
            case NotificationType::NewAssignment->value:
                return ['course_id' => rand(1, 10), 'assignment_id' => rand(1, 30)];
            case NotificationType::SubmissionGraded->value:
                return ['course_id' => rand(1, 10), 'assignment_id' => rand(1, 30), 'grade' => rand(60, 100)];
            case NotificationType::CourseStatusChanged->value:
                return ['course_id' => rand(1, 10), 'new_status' => 'active'];
            case NotificationType::LessonCompleted->value:
                return ['course_id' => rand(1, 10), 'lesson_id' => rand(1, 50)];
            case NotificationType::UserActivation->value:
                return ['new_status' => 'active'];
            default:
                return null;
        }
    }

    private function getRandomReadAt()
    {
        return rand(0, 1) ? Carbon::now()->subDays(rand(0, 7)) : null;
    }
}
