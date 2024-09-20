<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseMaterialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\SubmissionController;
use App\Models\Assignment;
use Illuminate\Support\Facades\Route;

/* 
    Route::prefix('v1')->group(function () {
        // Your existing routes go here
    });
 *///

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Public Routes

    // Login
    // POST /api/auth/login
    // Example: {"email": "user@example.com", "password": "password123"}
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // Register
    // POST /api/auth/register
    // Example: {"name": "John Doe", "email": "john@example.com", "password": "password123", "password_confirmation": "password123"}
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // Protected Routes
    // Update User Profile
    // PUT /api/auth/profile
    // Example: {"name": "John Updated", "email": "john_updated@example.com"}
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update')->middleware('auth:sanctum');

    // Get Authenticated User
    // GET /api/auth/user
    Route::get('/user', [AuthController::class, 'user'])->name('auth.user')->middleware('auth:sanctum');

    // Logout
    // POST /api/auth/logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth:sanctum');

    // Refresh Token
    // POST /api/auth/refresh
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh')->middleware('auth:sanctum');

    // Password Reset
    // POST /api/auth/password/email
    // Example: {"email": "user@example.com"}
    Route::post('/password/email', [AuthController::class, 'sendPasswordResetEmail'])->name('auth.password.email')->middleware('auth:sanctum');

    // POST /api/auth/password/reset
    // Example: {"email": "user@example.com", "password": "newpassword123", "password_confirmation": "newpassword123", "token": "reset_token"}
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('auth.password.reset')->middleware('auth:sanctum');

    // Two-Factor Authentication
    // POST /api/auth/two-factor-auth/enable
    Route::post('/two-factor-auth/enable', [AuthController::class, 'enableTwoFactorAuth'])->name('auth.two-factor.enable')->middleware('auth:sanctum');

    // POST /api/auth/two-factor-auth/disable
    Route::post('/two-factor-auth/disable', [AuthController::class, 'disableTwoFactorAuth'])->name('auth.two-factor.disable')->middleware('auth:sanctum');

    // POST /api/auth/two-factor-auth/verify
    // Example: {"code": "123456"}
    Route::post('/two-factor-auth/verify', [AuthController::class, 'verifyTwoFactorAuth'])->name('auth.two-factor.verify')->middleware('auth:sanctum');

    // Email Verification
    // GET /api/auth/email/verify/{id}/{hash}
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('auth.email.verify')->middleware('auth:sanctum');
});

// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {

    // User Management
    Route::prefix('users')->group(function () {
        // CRUD Operations
        // GET /api/users?search=john&role=student&active=true&created_after=2023-01-01&created_before=2023-12-31&sort_by=name&sort_direction=asc&per_page=20
        Route::get('/', [UserController::class, 'index'])->name('users.index');

        // POST /api/users
        // Example: {"name": "New User", "email": "newuser@example.com", "password": "password123", "role": "student"}
        Route::post('/', [UserController::class, 'store'])->name('users.store');

        // GET /api/users/{user}
        Route::get('/{user}', [UserController::class, 'show'])->name('users.show');

        // PUT /api/users/{user}
        // Example: {"name": "Updated User", "email": "updateduser@example.com"}
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');

        // DELETE /api/users/{user}
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // User Actions
        // POST /api/users/{user}/toggle-activation
        Route::post('/{user}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');

        // POST /api/users/{user}/assign-role
        // Example: {"role": "teacher"}
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assignRole');

        // POST /api/users/{user}/remove-role
        // Example: {"role": "student"}
        Route::post('/{user}/remove-role', [UserController::class, 'removeRole'])->name('users.removeRole');

        // GET /api/users/{user}/activity
        Route::get('/{user}/activity', [UserController::class, 'getUserActivity'])->name('users.activity');

        // Bulk Operations
        // POST /api/users/bulk-delete
        // Example: {"user_ids": [1, 2, 3]}
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');

        // Reporting and Statistics
        // GET /api/users/export
        Route::get('/export', [UserController::class, 'exportUsers'])->name('users.export');

        // GET /api/users/stats
        Route::get('/stats', [UserController::class, 'getUserStats'])->name('users.stats');
    });

    // Course Management
    Route::prefix('courses')->group(function () {
        // GET /api/courses?search=math&teacher=john&status=active&created_after=2023-01-01&created_before=2023-12-31&sort_by=name&sort_direction=asc&per_page=20
        Route::get('/', [CourseController::class, 'index'])->name('courses.index');

        // POST /api/courses
        // Example: {"title": "Introduction to Mathematics", "description": "A beginner's course in mathematics", "teacher_id": 1}
        Route::post('/', [CourseController::class, 'store'])->name('courses.store');

        // GET /api/courses/{course}
        Route::get('/{course}', [CourseController::class, 'show'])->name('courses.show');

        // PUT /api/courses/{course}
        // Example: {"title": "Advanced Mathematics", "description": "An advanced course in mathematics"}
        Route::put('/{course}', [CourseController::class, 'update'])->name('courses.update');

        // DELETE /api/courses/{course}
        Route::delete('/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        // Code Access Management
        // POST /api/courses/{course}/access-code
        Route::post('/{course}/access-code', [CourseController::class, 'generateAccessCode'])->name('courses.accessCode.generate');

        // DELETE /api/courses/{course}/access-code
        Route::delete('/{course}/access-code', [CourseController::class, 'removeAccessCode'])->name('courses.accessCode.remove');

        // POST /api/courses/{course}/join
        // Example: {"access_code": "123456"}
        Route::post('/join', [CourseController::class, 'joinCourse'])->name('courses.join');

        // Lesson Management
        // GET /api/courses/{course}/lessons
        Route::get('/{course}/lessons', [LessonController::class, 'index'])->name('courses.lessons.index');

        // POST /api/courses/{course}/lessons
        // Example: {"title": "Lesson 1: Introduction", "content": "This is the content of lesson 1"}
        Route::post('/{course}/lessons', [LessonController::class, 'store'])->name('courses.lessons.store');

        // PUT /api/courses/{course}/lessons/{lesson}
        // Example: {"title": "Updated Lesson 1", "content": "This is the updated content of lesson 1"}
        Route::put('/{course}/lessons/{lesson}', [LessonController::class, 'update'])->name('courses.lessons.update');

        // DELETE /api/courses/{course}/lessons/{lesson}
        Route::delete('/{course}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('courses.lessons.destroy');

        // Lesson progress routes Actions
        // GET /api/courses/{course}/progress
        Route::get('/{course}/progress', [ProgressController::class, 'getCourseProgress'])->name('courses.progress');

        // POST /api/courses/{course}/lessons/{lesson}/complete
        Route::post('{course}/lessons/{lesson}/complete', [LessonController::class, 'markAsCompleted'])->name('courses.lessons.complete');

        // POST /api/courses/{course}/lessons/{lesson}/incomplete
        Route::post('{course}/lessons/{lesson}/incomplete', [LessonController::class, 'markAsIncomplete'])->name('courses.lessons.incomplete');

        // Course Material Management
        // POST /api/courses/{course}/materials
        // Example: {"title": "Chapter 1 Notes", "file": <file_upload>}
        Route::post('/{course}/materials', [CourseMaterialController::class, 'store'])->name('courses.materials.store');

        // GET /api/courses/{course}/materials
        Route::get('/{course}/materials', [CourseMaterialController::class, 'index'])->name('courses.materials.index');

        // DELETE /api/materials/{material}
        Route::delete('/materials/{material}', [CourseMaterialController::class, 'destroy'])->name('courses.materials.destroy');

        // Assignments and Grading Management
        // Routes accessible by both teachers and students
        // GET /api/courses/{course}/assignments
        Route::get('/{course}/assignments', [AssignmentController::class, 'index'])->can('viewAny', Assignment::class)->name('courses.assignments.index');

        // GET /api/courses/{course}/grade-report
        Route::get('/assignments/{assignment}/grade-report', [SubmissionController::class, 'gradeReport'])->name('assignments.gradeReport');

        // Routes accessible by Teacher only
        Route::middleware(['role:teacher'])->group(function () {
            // POST /api/courses/{course}/assignments
            // Example: {"title": "Homework 1", "description": "Complete exercises 1-5", "due_date": "2023-12-31"}
            Route::post('/{course}/assignments', [AssignmentController::class, 'store'])->can('create', Assignment::class)->name('courses.assignments.store');

            // PUT /api/assignments/{assignment}
            // Example: {"title": "Updated Homework 1", "description": "Complete exercises 1-10", "due_date": "2024-01-15"}
            Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->can('update', Assignment::class)->name('assignments.update');

            // DELETE /api/assignments/{assignment}
            Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->can('delete', Assignment::class)->name('assignments.destroy');

            // GET /api/assignments/{assignment}/submissions
            Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])->can('viewSubmissions', Assignment::class)->name('assignments.submissions');

            // POST /api/submissions/{submission}/grade
            // Example: {"grade": 95, "feedback": "Excellent work!"}
            Route::post('/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->can('grade', Assignment::class)->name('assignments.grade');
        });

        //  Routes accessible by  Student only
        Route::middleware(['role:student'])->group(function () {
            // POST /api/assignments/{assignment}/submit
            // Example: {"content": "Here's my submission for Homework 1", "file": <file_upload>}
            Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->can('submit', Assignment::class)->name('assignments.submit');
        });
    });

    // Enrollment Management
    Route::prefix('enrollments')->group(function () {
        // POST /api/enrollments
        // Example: {"course_id": 1, "user_id": 2}
        Route::post('/', [EnrollmentController::class, 'enroll'])->name('enrollments.enroll');

        // PUT /api/enrollments/{enrollment}
        // Example: {"status": "completed"}
        Route::put('/{enrollment}', [EnrollmentController::class, 'updateEnrollmentStatus'])->name('enrollments.updateStatus');

        // GET /api/enrollments/student
        Route::get('/student', [EnrollmentController::class, 'getStudentEnrollments'])->name('enrollments.student');

        // GET /api/enrollments/course/{course}
        Route::get('/course/{course}', [EnrollmentController::class, 'getCourseEnrollments'])->name('enrollments.course');

        // DELETE /api/enrollments/{enrollment}
        Route::delete('/{enrollment}', [EnrollmentController::class, 'withdrawEnrollment'])->name('enrollments.withdraw');

        // GET /api/enrollments/statistics/{course}
        Route::get('/statistics/{course}', [EnrollmentController::class, 'getEnrollmentStatistics'])->name('enrollments.statistics');
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        // GET /api/notifications
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        // POST /api/notifications/{id}/read
        // Example: {"read": true}
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    });
});

// Protected Endpoint (Using auth:api middleware)
// GET /api/protected-endpoint
Route::get('/protected-endpoint', [AuthController::class, 'protectedEndpoint'])->middleware('auth:api')->name('protected.endpoint');
