<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Notification;
use App\Repositories\Notification\NotificationRepoInterface;
use Illuminate\Http\Request;

class NotificationService implements NotificationServiceInterface
{
    protected $notificationRepository;

    public function __construct(NotificationRepoInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function getUserNotifications(Request $request)
    {
        $user = auth()->user();
        return $this->notificationRepository->getUserNotifications($user->id, $request);
    }

    public function markAsRead($id)
    {
        return $this->notificationRepository->markAsRead($id);
    }

    public function createNotification(User $user, string $type, string $message, array $data = []): Notification
    {
        $notificationData = [
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ];
        return $this->notificationRepository->createNotification($notificationData);
    }
}
