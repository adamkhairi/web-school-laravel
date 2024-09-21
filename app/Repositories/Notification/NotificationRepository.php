<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationRepository implements NotificationRepoInterface
{
    public function getUserNotifications($userId, Request $request)
    {
        $query = Notification::where('user_id', $userId);

        // Apply filters
        $query->when($request->boolean('unread', false), function ($q) {
            $q->whereNull('read_at');
        });

        $query->when($request->input('filters'), function ($q, $filters) {
            foreach ($filters as $field => $value) {
                $q->where($field, $value);
            }
        });

        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($subQ) use ($search) {
                $subQ->where('message', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        });

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $request->input('per_page', 15);
        return $query->paginate($perPage);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->read_at = now();
        $notification->save();
        return $notification;
    }

    public function createNotification(array $data): Notification // New method implementation
    {
        return Notification::create($data);
    }
}
