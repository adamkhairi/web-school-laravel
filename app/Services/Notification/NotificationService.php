<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationService implements NotificationServiceInterface
{
  public function createNotification(User $user, string $type, string $message, array $data = []): Notification
  {
    return Notification::create([
      'user_id' => $user->id,
      'type' => $type,
      'message' => $message,
      'data' => $data,
    ]);
  }

  public function getUserNotifications(Request $request)
  {
    $user = auth()->user();
    $query = $user->notifications();

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

  public function markAsRead(Notification $notification)
  {
    $notification = Notification::findOrFail($notification->id);
    $notification->update(['read_at' => now()]);
    return $notification;
  }
}
