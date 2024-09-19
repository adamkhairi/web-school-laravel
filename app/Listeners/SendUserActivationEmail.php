<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\UserActivationToggled;
use App\Mail\UserActivationStatus;
use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendUserActivationEmail
{
    /**
     * Create the event listener.
     */
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserActivationToggled $event)
    {
        $user = $event->user;
        $status = $user->is_active ? 'activated' : 'deactivated';

        // Send email
        Mail::to($user->email)->send(new UserActivationStatus($user, $status));

        // Create notification
        $this->notificationService->createNotification(
            $user,
            NotificationType::UserActivation->value,
            "Your account has been {$status}.",
            ['user_id' => $user->id]
        );
    }
}
