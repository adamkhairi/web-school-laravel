<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Policies\EnrollmentPolicy;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthServiceInterface;
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use App\Services\Course\CourseServiceInterface;
use App\Services\Course\CourseService;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\EnrollmentServiceInterface;
use App\Services\Lesson\LessonService;
use App\Services\Lesson\LessonServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Enrollment::class => EnrollmentPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(CourseServiceInterface::class, CourseService::class);
        $this->app->bind(LessonServiceInterface::class, LessonService::class);
        $this->app->bind(EnrollmentServiceInterface::class, EnrollmentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
