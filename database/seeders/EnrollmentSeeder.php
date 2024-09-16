<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run()
    {
        $studentRole = Role::where('name', RoleType::Student)->first();
        $students = User::whereHas('roles', function ($query) use ($studentRole) {
            $query->where('roles.id', $studentRole->id);
        })->get();
        $courses = Course::all();

        foreach ($students as $student) {
            $enrollmentCount = fake()->numberBetween(1, 5);
            $randomCourses = $courses->random($enrollmentCount);

            foreach ($randomCourses as $course) {
                Enrollment::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'status' => fake()->randomElement(EnrollmentStatus::cases()),
                ]);
            }
        }
    }
}
