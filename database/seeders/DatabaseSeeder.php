<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $this->call([
        RoleSeeder::class,
        UserSeeder::class,
        CourseSeeder::class,
        LessonSeeder::class,
        EnrollmentSeeder::class,
        AssignmentSeeder::class,
        SubmissionSeeder::class,
        ProgressSeeder::class
    ]);
}
}
