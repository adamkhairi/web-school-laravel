<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use App\Enums\CourseStatus;
use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run()
    {
        $teacherRole = Role::where('name', RoleType::Teacher->value)->first();
        $teachers = User::whereHas('roles', function ($query) use ($teacherRole) {
            $query->where('roles.id', $teacherRole->id);
        })->get();

        foreach ($teachers as $teacher) {
            Course::factory()->create([
                'teacher_id' => $teacher->id,
            ]);
        }


        // Create some additional random courses
        Course::factory(10)->create();
    }
}
