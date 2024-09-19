<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;

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
        try {
            $notifications = $this->notificationService->getUserNotifications($request);
            return $this->successResponse($notifications);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }

    public function markAsRead($id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead($id);
            return $this->successResponse($notification, 'Notification marked as read');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to mark notification as read: ' . $e->getMessage(), 500);
        }
    }
}
