<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('name', RoleType::Admin)->first();
        $teacherRole = Role::where('name', RoleType::Teacher)->first();
        $studentRole = Role::where('name', RoleType::Student)->first();

        // Create an admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@webschool.com',
            'password' => Hash::make('password'),
        ]);
        $admin->roles()->attach($adminRole);

        // Create a teacher user
        $teacher = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@webschool.com',
            'password' => Hash::make('password'),
        ]);
        $teacher->roles()->attach($teacherRole);

        // Create a student user
        $student = User::create([
            'name' => 'Student User',
            'email' => 'student@webschool.com',
            'password' => Hash::make('password'),
        ]);
        $student->roles()->attach($studentRole);

        // Create additional users with random roles
        User::factory(25)->create()->each(function ($user) use ($adminRole, $teacherRole, $studentRole) {
            $user->roles()->attach(
                fake()->randomElement([$adminRole, $teacherRole, $studentRole])
            );
        });
    }
}
