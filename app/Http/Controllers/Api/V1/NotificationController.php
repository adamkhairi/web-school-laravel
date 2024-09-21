<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * event(new NewLessonCreated($lesson));
     *
     * This implementation provides a solid foundation for a notification system in your Laravel API project.
     * You can expand on this by adding more notification types, implementing real-time notifications using WebSockets,
     * or integrating with external notification services as needed.
     *
     **/

    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        Log::info('Fetching user notifications', ['user_id' => $request->user()->id]);
        try {
            $notifications = $this->notificationService->getUserNotifications($request);
            Log::info('User notifications fetched successfully', ['notifications_count' => count($notifications)]);
            return $this->successResponse($notifications);
        } catch (Exception $e) {
            Log::error('Failed to fetch notifications', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }

    public function markAsRead($id): JsonResponse
    {
        Log::info('Marking notification as read', ['notification_id' => $id]);
        try {
            $notification = $this->notificationService->markAsRead($id);
            Log::info('Notification marked as read successfully', ['notification_id' => $id]);
            return $this->successResponse($notification, 'Notification marked as read');
        } catch (Exception $e) {
            Log::error('Failed to mark notification as read', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to mark notification as read: ' . $e->getMessage(), 500);
        }
    }
}
