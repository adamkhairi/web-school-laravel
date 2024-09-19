<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

interface NotificationServiceInterface
{
  public function createNotification(User $user, string $type, string $message, array $data = []): Notification;

  public function getUserNotifications(Request $request);


  public function markAsRead(Notification $notification);
}
