<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Policies\AssignmentPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\SubmissionPolicy;
use App\Services\Assignment\AssignmentServiceInterface;
use App\Services\Assignment\AssignmentService;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthServiceInterface;
use App\Services\Auth\OAuthService;
use App\Services\Auth\OAuthServiceInterface;
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use App\Services\Course\CourseServiceInterface;
use App\Services\Course\CourseService;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\EnrollmentServiceInterface;
use App\Services\Lesson\LessonService;
use App\Services\Lesson\LessonServiceInterface;
use App\Services\Notification\NotificationService;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Progress\ProgressService;
use App\Services\Progress\ProgressServiceInterface;
use App\Services\Submission\SubmissionService;
use App\Services\Submission\SubmissionServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Enrollment::class => EnrollmentPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Submission::class => SubmissionPolicy::class
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Inject dependencies
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(OAuthServiceInterface::class, OAuthService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(CourseServiceInterface::class, CourseService::class);
        $this->app->bind(LessonServiceInterface::class, LessonService::class);
        $this->app->bind(EnrollmentServiceInterface::class, EnrollmentService::class);
        $this->app->bind(AssignmentServiceInterface::class, AssignmentService::class);
        $this->app->bind(SubmissionServiceInterface::class, SubmissionService::class);
        $this->app->bind(ProgressServiceInterface::class, ProgressService::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
