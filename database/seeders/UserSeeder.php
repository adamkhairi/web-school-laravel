<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create an admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@webschool.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
        ]);

        // Create a teacher user
        User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@webschool.com',
            'password' => Hash::make('password'),
            'role' => 'Teacher',
        ]);

        // Create a student user
        User::create([
            'name' => 'Student User',
            'email' => 'student@webschool.com',
            'password' => Hash::make('password'),
            'role' => 'Student',
        ]);

        User::factory(25)->create();
    }
}
