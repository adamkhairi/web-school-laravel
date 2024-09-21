<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\CourseMaterialController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Models\Assignment;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    /*
     * Your existing routes go here
     */


    // Authentication Routes
    Route::prefix('auth')->group(function () {
        // Public Routes

        // Login
        // POST /api/v1/auth/login
        // Example: {"email": "user@example.com", "password": "password123"}
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

        // Register
        // POST /api/v1/auth/register
        // Example: {"name": "John Doe", "email": "john@example.com", "password": "password123", "password_confirmation": "password123"}
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

        // Protected Routes
        // Update User Profile
        // PUT /api/v1/auth/profile
        // Example: {"name": "John Updated", "email": "john_updated@example.com"}
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update')->middleware('auth:sanctum');

        // Get Authenticated User
        // GET /api/v1/auth/user
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user')->middleware('auth:sanctum');

        // Logout
        // POST /api/v1/auth/logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth:sanctum');

        // Refresh Token
        // POST /api/v1/auth/refresh
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh')->middleware('auth:sanctum');

        // Password Reset
        // POST /api/v1/auth/password/email
        // Example: {"email": "user@example.com"}
        Route::post('/password/email', [AuthController::class, 'sendPasswordResetEmail'])->name('auth.password.email')->middleware('auth:sanctum');

        // POST /api/v1/auth/password/reset
        // Example: {"email": "user@example.com", "password": "newpassword123", "password_confirmation": "newpassword123", "token": "reset_token"}
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('auth.password.reset')->middleware('auth:sanctum');

        // Two-Factor Authentication
        // POST /api/v1/auth/two-factor-auth/enable
        Route::post('/two-factor-auth/enable', [AuthController::class, 'enableTwoFactorAuth'])->name('auth.two-factor.enable')->middleware('auth:sanctum');

        // POST /api/v1/auth/two-factor-auth/disable
        Route::post('/two-factor-auth/disable', [AuthController::class, 'disableTwoFactorAuth'])->name('auth.two-factor.disable')->middleware('auth:sanctum');

        // POST /api/v1/auth/two-factor-auth/verify
        // Example: {"code": "123456"}
        Route::post('/two-factor-auth/verify', [AuthController::class, 'verifyTwoFactorAuth'])->name('auth.two-factor.verify')->middleware('auth:sanctum');

        // Email Verification
        // GET /api/v1/auth/email/verify/{id}/{hash}
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('auth.email.verify')->middleware('auth:sanctum');
    });

    // Protected Routes (Require Authentication)
    Route::middleware('auth:sanctum')->group(function () {

        // User Management
        Route::prefix('users')->group(function () {
            // CRUD Operations
            // GET /api/v1/users?search=john&role=student&active=true&created_after=2023-01-01&created_before=2023-12-31&sort_by=name&sort_direction=asc&per_page=20
            Route::get('/', [UserController::class, 'index'])->name('users.index');

            // POST /api/v1/users
            // Example: {"name": "New User", "email": "newuser@example.com", "password": "password123", "role": "student"}
            Route::post('/', [UserController::class, 'store'])->name('users.store');

            // GET /api/v1/users/{user}
            Route::get('/{user}', [UserController::class, 'show'])->name('users.show');

            // PUT /api/v1/users/{user}
            // Example: {"name": "Updated User", "email": "updateduser@example.com"}
            Route::put('/{user}', [UserController::class, 'update'])->name('users.update');

            // DELETE /api/v1/users/{user}
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // User Actions
            // POST /api/v1/users/{user}/toggle-activation
            Route::post('/{user}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');

            // POST /api/v1/users/{user}/assign-role
            // Example: {"role": "teacher"}
            Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assignRole');

            // POST /api/v1/users/{user}/remove-role
            // Example: {"role": "student"}
            Route::post('/{user}/remove-role', [UserController::class, 'removeRole'])->name('users.removeRole');

            // GET /api/v1/users/{user}/activity
            Route::get('/{user}/activity', [UserController::class, 'getUserActivity'])->name('users.activity');

            // Bulk Operations
            // POST /api/v1/users/bulk-delete
            // Example: {"user_ids": [1, 2, 3]}
            Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');

            // Reporting and Statistics
            // GET /api/v1/users/export
            Route::get('/export', [UserController::class, 'exportUsers'])->name('users.export');

            // Role Management
            // POST /api/v1/users/roles
            // Example: {"name": "New Role"}
            Route::post('/roles', [UserController::class, 'storeRole'])->name('roles.store');

            // GET /api/v1/users/roles/{role}
            Route::delete('/roles/{role}', [UserController::class, 'destroyRole'])->name('roles.destroy');

            // Todo: Need To Fix this function
            // GET /api/v1/users/stats
            Route::get('/stats', [UserController::class, 'getUserStats'])->name('users.stats');
        });

        // Course Management
        Route::prefix('courses')->group(function () {
            // GET /api/v1/courses?search=math&teacher=john&status=active&created_after=2023-01-01&created_before=2023-12-31&sort_by=name&sort_direction=asc&per_page=20
            Route::get('/', [CourseController::class, 'index'])->name('courses.index');

            // POST /api/v1/courses
            // Example: {"title": "Introduction to Mathematics", "description": "A beginner's course in mathematics", "teacher_id": 1}
            Route::post('/', [CourseController::class, 'store'])->name('courses.store');

            // GET /api/v1/courses/{course}
            Route::get('/{course}', [CourseController::class, 'show'])->name('courses.show');

            // PUT /api/v1/courses/{course}
            // Example: {"title": "Advanced Mathematics", "description": "An advanced course in mathematics"}
            Route::put('/{course}', [CourseController::class, 'update'])->name('courses.update');

            // DELETE /api/v1/courses/{course}
            Route::delete('/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

            // Code Access Management
            // POST /api/v1/courses/{course}/access-code
            Route::post('/{course}/access-code', [CourseController::class, 'generateAccessCode'])->name('courses.accessCode.generate');

            // DELETE /api/v1/courses/{course}/access-code
            Route::delete('/{course}/access-code', [CourseController::class, 'removeAccessCode'])->name('courses.accessCode.remove');

            // POST /api/v1/courses/{course}/join
            // Example: {"access_code": "123456"}
            Route::post('/join', [CourseController::class, 'joinCourse'])->name('courses.join');

            // Lesson Management
            // GET /api/v1/courses/{course}/lessons
            Route::get('/{course}/lessons', [LessonController::class, 'index'])->name('courses.lessons.index');

            // POST /api/v1/courses/{course}/lessons
            // Example: {"title": "Lesson 1: Introduction", "content": "This is the content of lesson 1"}
            Route::post('/{course}/lessons', [LessonController::class, 'store'])->name('courses.lessons.store');

            // PUT /api/v1/courses/{course}/lessons/{lesson}
            // Example: {"title": "Updated Lesson 1", "content": "This is the updated content of lesson 1"}
            Route::put('/{course}/lessons/{lesson}', [LessonController::class, 'update'])->name('courses.lessons.update');

            // DELETE /api/v1/courses/{course}/lessons/{lesson}
            Route::delete('/{course}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('courses.lessons.destroy');

            // Lesson progress routes Actions
            // GET /api/v1/courses/{course}/progress
            Route::get('/{course}/progress', [ProgressController::class, 'getCourseProgress'])->name('courses.progress');

            // POST /api/v1/courses/{course}/lessons/{lesson}/complete
            Route::post('{course}/lessons/{lesson}/complete', [LessonController::class, 'markAsCompleted'])->name('courses.lessons.complete');

            // POST /api/v1/courses/{course}/lessons/{lesson}/incomplete
            Route::post('{course}/lessons/{lesson}/incomplete', [LessonController::class, 'markAsIncomplete'])->name('courses.lessons.incomplete');

            // Course Material Management
            // POST /api/v1/courses/{course}/materials
            // Example: {"title": "Chapter 1 Notes", "file": <file_upload>}
            Route::post('/{course}/materials', [CourseMaterialController::class, 'store'])->name('courses.materials.store');

            // GET /api/v1/courses/{course}/materials
            Route::get('/{course}/materials', [CourseMaterialController::class, 'index'])->name('courses.materials.index');

            // DELETE /api/v1/materials/{material}
            Route::delete('/materials/{material}', [CourseMaterialController::class, 'destroy'])->name('courses.materials.destroy');

            // Assignments and Grading Management
            // Routes accessible by both teachers and students
            // GET /api/v1/courses/{course}/assignments
            Route::get('/{course}/assignments', [AssignmentController::class, 'index'])->can('viewAny', Assignment::class)->name('courses.assignments.index');

            // GET /api/v1/courses/{course}/grade-report
            Route::get('/assignments/{assignment}/grade-report', [SubmissionController::class, 'gradeReport'])->name('assignments.gradeReport');

            // Routes accessible by Teacher only
            Route::middleware(['role:teacher'])->group(function () {
                // POST /api/v1/courses/{course}/assignments
                // Example: {"title": "Homework 1", "description": "Complete exercises 1-5", "due_date": "2023-12-31"}
                Route::post('/{course}/assignments', [AssignmentController::class, 'store'])->can('create', Assignment::class)->name('courses.assignments.store');

                // PUT /api/v1/assignments/{assignment}
                // Example: {"title": "Updated Homework 1", "description": "Complete exercises 1-10", "due_date": "2024-01-15"}
                Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->can('update', Assignment::class)->name('assignments.update');

                // DELETE /api/v1/assignments/{assignment}
                Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->can('delete', Assignment::class)->name('assignments.destroy');

                // GET /api/v1/assignments/{assignment}/submissions
                Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])->can('viewSubmissions', Assignment::class)->name('assignments.submissions');

                //TODO: Fix this 404 Not found error
                // POST /api/v1/submissions/{submission}/grade
                // Example: {"grade": 95, "feedback": "Excellent work!"}
                Route::post('/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->can('grade', Assignment::class)->name('assignments.grade');
            });

            //  Routes accessible by  Student only
            Route::middleware(['role:student'])->group(function () {
                // POST /api/v1/assignments/{assignment}/submit
                // Example: {"content": "Here's my submission for Homework 1", "file": <file_upload>}
                Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->can('submit', Assignment::class)->name('assignments.submit');
            });
        });

        // Enrollment Management
        Route::prefix('enrollments')->group(function () {
            // POST /api/v1/enrollments
            // Example: {"course_id": 1, "user_id": 2}
            Route::post('/', [EnrollmentController::class, 'enroll'])->name('enrollments.enroll');

            // PUT /api/v1/enrollments/{enrollment}
            // Example: {"status": "completed"}
            Route::put('/{enrollment}', [EnrollmentController::class, 'updateEnrollmentStatus'])->name('enrollments.updateStatus');

            // GET /api/v1/enrollments/courses/{course}/waitlist
            Route::get('/courses/{course}/waitlist', [EnrollmentController::class, 'getWaitlistedStudents'])->name('enrollments.waitlist');
            
            // GET /api/v1/enrollments/student
            Route::get('/student', [EnrollmentController::class, 'getStudentEnrollments'])->name('enrollments.student');

            // GET /api/v1/enrollments/course/{course}
            Route::get('/courses/{course}', [EnrollmentController::class, 'getCourseEnrollments'])->name('enrollments.course');

            // DELETE /api/v1/enrollments/{enrollment}
            Route::delete('/{enrollment}', [EnrollmentController::class, 'withdrawEnrollment'])->name('enrollments.withdraw');

            // GET /api/v1/enrollments/statistics/{course}
            Route::get('/statistics/{course}', [EnrollmentController::class, 'getEnrollmentStatistics'])->name('enrollments.statistics');
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            // GET /api/v1/notifications
            Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
            // POST /api/v1/notifications/{id}/read
            // Example: {"read": true}
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        });
    });

});


// Protected Endpoint (Using auth:api middleware)
// GET /api/v1/protected-endpoint
Route::get('/protected-endpoint', [AuthController::class, 'protectedEndpoint'])->middleware('auth:api')->name('protected.endpoint');
