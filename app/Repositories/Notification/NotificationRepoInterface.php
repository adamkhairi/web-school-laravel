<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use Illuminate\Http\Request;

interface NotificationRepoInterface
{
    public function getUserNotifications($userId, Request $request);
    public function markAsRead($id);
    public function createNotification(array $data): Notification; // New method    
}
