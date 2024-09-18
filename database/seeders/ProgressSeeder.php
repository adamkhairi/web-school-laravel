<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use Illuminate\Database\Seeder;
use App\Models\Progress;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;

class ProgressSeeder extends Seeder
{
    public function run()
    {
        $studentRole = Role::where('name', RoleType::Student->value)->first();
        $students = User::whereHas('roles', function ($query) use ($studentRole) {
            $query->where('roles.id', $studentRole->id);
        })->inRandomOrder()->limit(10)->get();

        $courses = Course::all();

        foreach ($students as $student) {
            foreach ($courses as $course) {
                $lessons = $course->lessons;

                foreach ($lessons as $lesson) {
                    Progress::create([
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                        'lesson_id' => $lesson->id,
                        'completed' => rand(0, 1),
                    ]);
                }
            }
        }
    }
}
